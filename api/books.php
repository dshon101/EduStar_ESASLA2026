<?php
// ================================================================
// EduStar API — /api/books.php
// GET  /api/books.php?action=list          — list books (filtered)
// GET  /api/books.php?action=get&id=ke-m7  — single book detail
// POST /api/books.php?action=download&id=X — log & serve download
// POST /api/books.php?action=upload        — admin: upload PDF
// POST /api/books.php?action=create        — admin: create book meta
// PUT  /api/books.php?action=update&id=X   — admin: update book
// DELETE /api/books.php?action=delete&id=X — admin: delete book
// ================================================================
require_once __DIR__ . '/../config/helpers.php';
jsonHeaders();

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];


// ── LIST BOOKS ───────────────────────────────────────────────────
if ($action === 'list' && $method === 'GET') {
    $country  = $_GET['country']  ?? '';
    $subject  = $_GET['subject']  ?? '';
    $grade    = $_GET['grade']    ?? '';
    $search   = $_GET['search']   ?? '';

    $where = ['b.is_active = 1'];
    $vals  = [];

    if ($country) {
        $where[] = '(b.country = ? OR b.country = "continental")';
        $vals[]  = $country;
    }
    if ($subject) { $where[] = 'b.subject = ?';    $vals[] = $subject; }
    if ($grade)   { $where[] = 'b.grade_range = ?'; $vals[] = $grade; }
    if ($search)  {
        $where[] = '(b.title LIKE ? OR b.subject LIKE ? OR b.publisher LIKE ?)';
        $like    = '%' . $search . '%';
        array_push($vals, $like, $like, $like);
    }

    $sql = 'SELECT b.*, GROUP_CONCAT(
                JSON_OBJECT(
                    "num",    bc.chapter_num,
                    "title",  bc.title,
                    "topics", bc.topics
                ) ORDER BY bc.chapter_num SEPARATOR "|||"
            ) AS chapters_raw
            FROM books b
            LEFT JOIN book_chapters bc ON bc.book_id = b.id
            WHERE ' . implode(' AND ', $where) . '
            GROUP BY b.id
            ORDER BY b.country = "continental" ASC, b.title ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($vals);
    $books = $stmt->fetchAll();

    foreach ($books as &$book) {
        $book = formatBook($book);
    }
    ok(['books' => $books]);
}

// ── GET SINGLE BOOK ──────────────────────────────────────────────
if ($action === 'get' && $method === 'GET') {
    $key = $_GET['id'] ?? '';
    if (!$key) fail('Book ID required.');

    $stmt = db()->prepare('
        SELECT b.*, GROUP_CONCAT(
            JSON_OBJECT("num", bc.chapter_num, "title", bc.title, "topics", bc.topics)
            ORDER BY bc.chapter_num SEPARATOR "|||"
        ) AS chapters_raw
        FROM books b
        LEFT JOIN book_chapters bc ON bc.book_id = b.id
        WHERE b.book_key = ? AND b.is_active = 1
        GROUP BY b.id
    ');
    $stmt->execute([$key]);
    $book = $stmt->fetch();
    if (!$book) fail('Book not found.', 404);
    ok(['book' => formatBook($book)]);
}

// ── LOG DOWNLOAD & REDIRECT ──────────────────────────────────────
if ($action === 'download' && $method === 'POST') {
    $key  = $_GET['id'] ?? body()['id'] ?? '';
    if (!$key) fail('Book ID required.');

    $stmt = db()->prepare('SELECT * FROM books WHERE book_key = ? AND is_active = 1');
    $stmt->execute([$key]);
    $book = $stmt->fetch();
    if (!$book) fail('Book not found.', 404);
    if (!$book['file_path']) fail('No file available for this book yet.', 404);

    $user = optionalAuth();

    // Log download
    db()->prepare('
        INSERT INTO book_downloads (book_id, user_id, ip)
        VALUES (?, ?, ?)
    ')->execute([$book['id'], $user ? $user['id'] : null, $_SERVER['REMOTE_ADDR'] ?? null]);

    db()->prepare('UPDATE books SET download_count = download_count + 1 WHERE id = ?')
       ->execute([$book['id']]);

    ok(['url' => UPLOAD_URL_BASE . basename($book['file_path'])]);
}

// ── UPLOAD PDF (admin) ────────────────────────────────────────────
if ($action === 'upload' && $method === 'POST') {
    requireAdmin();

    $bookKey = trim($_POST['bookKey'] ?? '');
    if (!$bookKey) fail('bookKey is required.');

    $stmt = db()->prepare('SELECT id FROM books WHERE book_key = ?');
    $stmt->execute([$bookKey]);
    $book = $stmt->fetch();
    if (!$book) fail('Book not found in database.');

    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK)
        fail('File upload error: ' . ($_FILES['file']['error'] ?? 'no file'));

    $tmp  = $_FILES['file']['tmp_name'];
    $mime = mime_content_type($tmp);
    if ($mime !== 'application/pdf') fail('Only PDF files are accepted.');

    if ($_FILES['file']['size'] > MAX_FILE_BYTES)
        fail('File too large (max 50 MB).');

    if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

    $filename = preg_replace('/[^a-z0-9\-_]/i', '_', $bookKey) . '_' . time() . '.pdf';
    $dest     = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($tmp, $dest)) fail('Failed to save file.');

    db()->prepare('UPDATE books SET file_path = ?, file_size = ? WHERE id = ?')
       ->execute([$dest, (int)$_FILES['file']['size'], $book['id']]);

    ok(['message' => 'File uploaded.', 'filename' => $filename]);
}

// ── CREATE BOOK META (admin) ──────────────────────────────────────
if ($action === 'create' && $method === 'POST') {
    requireAdmin();
    $b = body();
    saveBookMeta(null, $b);
    ok(['message' => 'Book created.'], 201);
}

// ── UPDATE BOOK META (admin) ──────────────────────────────────────
if ($action === 'update' && $method === 'PUT') {
    requireAdmin();
    $key = $_GET['id'] ?? '';
    $b   = body();
    saveBookMeta($key, $b);
    ok(['message' => 'Book updated.']);
}

// ── DELETE BOOK (admin) ───────────────────────────────────────────
if ($action === 'delete' && $method === 'DELETE') {
    requireAdmin();
    $key = $_GET['id'] ?? '';
    if (!$key) fail('Book ID required.');
    db()->prepare('UPDATE books SET is_active = 0 WHERE book_key = ?')->execute([$key]);
    ok(['message' => 'Book removed.']);
}

// ── HELPERS ──────────────────────────────────────────────────────

function formatBook(array $b): array {
    $chapters = [];
    if ($b['chapters_raw']) {
        foreach (explode('|||', $b['chapters_raw']) as $raw) {
            $ch = json_decode($raw, true);
            if ($ch) {
                $ch['topics'] = json_decode($ch['topics'] ?? '[]', true) ?? [];
                $chapters[] = $ch;
            }
        }
    }
    unset($b['chapters_raw']);
    $b['chapters']       = $chapters;
    $b['download_count'] = (int)$b['download_count'];
    $b['hasFile']        = !empty($b['file_path']);
    unset($b['file_path']); // don't expose server path
    return $b;
}

function saveBookMeta(?string $existingKey, array $b): void {
    $key        = str('book_key', $b)    ?: $existingKey;
    $title      = str('title', $b);
    $subject    = str('subject', $b);
    $country    = str('country', $b);
    $gradeRange = str('grade_range', $b);
    $publisher  = str('publisher', $b);
    $curriculum = str('curriculum', $b);
    $year       = intVal('year', $b)     ?: null;
    $icon       = str('icon', $b)        ?: '📚';
    $color      = str('color', $b)       ?: '#FF6B2B';

    if (!$key || !$title || !$subject) fail('book_key, title, and subject are required.');

    db()->prepare('
        INSERT INTO books (book_key, title, subject, country, grade_range, publisher, curriculum, year, icon, color)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          title = VALUES(title), subject = VALUES(subject), country = VALUES(country),
          grade_range = VALUES(grade_range), publisher = VALUES(publisher),
          curriculum = VALUES(curriculum), year = VALUES(year),
          icon = VALUES(icon), color = VALUES(color)
    ')->execute([$key, $title, $subject, $country, $gradeRange, $publisher, $curriculum, $year, $icon, $color]);

    $bookId = (int)db()->lastInsertId() ?: (function() use ($key) {
        $r = db()->prepare('SELECT id FROM books WHERE book_key = ?');
        $r->execute([$key]); return (int)$r->fetchColumn();
    })();

    // Upsert chapters
    if (isset($b['chapters']) && is_array($b['chapters'])) {
        db()->prepare('DELETE FROM book_chapters WHERE book_id = ?')->execute([$bookId]);
        $ins = db()->prepare('
            INSERT INTO book_chapters (book_id, chapter_num, title, topics)
            VALUES (?, ?, ?, ?)
        ');
        foreach ($b['chapters'] as $i => $ch) {
            $topics = json_encode($ch['topics'] ?? []);
            $ins->execute([$bookId, $i + 1, $ch['title'] ?? '', $topics]);
        }
    }
}
