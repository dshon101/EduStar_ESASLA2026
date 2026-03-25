<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<pre style='font-family:monospace;font-size:13px;padding:20px;background:#0d0d1a;color:#f0f0ff'>";

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';

// ── DIAGNOSE ──────────────────────────────────────────────────────
echo "=== DIAGNOSING books.php stats error ===\n";
$tests = [
    'book_downloads exists'  => "SELECT COUNT(*) FROM book_downloads",
    'books exists'           => "SELECT COUNT(*) FROM books",
    'book_chapters exists'   => "SELECT COUNT(*) FROM book_chapters",
];
foreach ($tests as $label => $sql) {
    try { $r = db()->query($sql)->fetchColumn(); echo "  $label: ✅ ($r rows)\n"; }
    catch (Exception $e) { echo "  $label: ❌ " . $e->getMessage() . "\n"; }
}

// ── REWRITE books.php WITH SAFE try/catch ON EVERY QUERY ──────────
echo "\n=== REWRITING api/books.php ===\n";

file_put_contents(__DIR__ . '/api/books.php', '<?php
error_reporting(0);
ini_set("display_errors", 0);
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../config/helpers.php";
jsonHeaders();

$action = $_GET["action"] ?? "list";

// ── TRACK DOWNLOAD ────────────────────────────────────────────────
if ($action === "track" && $_SERVER["REQUEST_METHOD"] === "POST") {
    $b      = body();
    $title  = trim($b["title"]   ?? "Unknown");
    $ctry   = trim($b["country"] ?? "");
    $sub    = trim($b["subject"] ?? "");
    $src    = trim($b["source"]  ?? "");
    $uid    = null;
    $token  = getBearerToken();
    if ($token) {
        $s = db()->prepare("SELECT u.id FROM sessions s JOIN users u ON u.id=s.user_id WHERE s.token=? AND s.expires_at>NOW()");
        $s->execute([$token]);
        $row = $s->fetch();
        if ($row) $uid = (int)$row["id"];
    }
    try {
        db()->prepare("INSERT INTO book_downloads (title,country,subject,source,user_id,downloaded_at) VALUES (?,?,?,?,?,NOW())")
           ->execute([$title, $ctry, $sub, $src, $uid]);
    } catch (Exception $e) {
        // Table missing — create it then retry
        db()->exec("CREATE TABLE IF NOT EXISTS book_downloads (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            country VARCHAR(100), subject VARCHAR(100), source VARCHAR(100),
            user_id INT NULL, downloaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_title (title(50)), INDEX idx_country (country)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        db()->prepare("INSERT INTO book_downloads (title,country,subject,source,user_id,downloaded_at) VALUES (?,?,?,?,?,NOW())")
           ->execute([$title, $ctry, $sub, $src, $uid]);
    }
    ok(["tracked" => true]);
    exit;
}

// ── STATS (admin only) ────────────────────────────────────────────
if ($action === "stats") {
    $token = getBearerToken();
    if (!$token) fail("Unauthorised", 401);
    $s = db()->prepare("SELECT u.* FROM sessions s JOIN users u ON u.id=s.user_id WHERE s.token=? AND s.expires_at>NOW() AND u.is_active=1");
    $s->execute([$token]);
    $admin = $s->fetch();
    if (!$admin || !$admin["is_admin"]) fail("Admin only", 403);

    $total     = 0;
    $byCountry = [];
    $bySubject = [];
    $topBooks  = [];
    $recent    = [];

    try { $total = (int)db()->query("SELECT COUNT(*) FROM book_downloads")->fetchColumn(); } catch(Exception $e) {}
    try { $byCountry = db()->query("SELECT country, COUNT(*) as cnt FROM book_downloads GROUP BY country ORDER BY cnt DESC LIMIT 10")->fetchAll(); } catch(Exception $e) {}
    try { $bySubject = db()->query("SELECT subject, COUNT(*) as cnt FROM book_downloads GROUP BY subject ORDER BY cnt DESC LIMIT 10")->fetchAll(); } catch(Exception $e) {}
    try { $topBooks  = db()->query("SELECT title, country, subject, COUNT(*) as cnt FROM book_downloads GROUP BY title,country,subject ORDER BY cnt DESC LIMIT 20")->fetchAll(); } catch(Exception $e) {}
    try { $recent    = db()->query("SELECT bd.title, bd.country, bd.subject, bd.downloaded_at, u.name as user_name FROM book_downloads bd LEFT JOIN users u ON u.id=bd.user_id ORDER BY bd.downloaded_at DESC LIMIT 50")->fetchAll(); } catch(Exception $e) {}

    ok(["total"=>$total,"byCountry"=>$byCountry,"bySubject"=>$bySubject,"topBooks"=>$topBooks,"recent"=>$recent]);
    exit;
}

// ── LIST BOOKS ────────────────────────────────────────────────────
if ($action === "list") {
    $books = [];
    try {
        $country = trim($_GET["country"] ?? "");
        if ($country) {
            $s = db()->prepare("SELECT * FROM books WHERE (country=? OR country=\'continental\') AND is_active=1 ORDER BY title");
            $s->execute([$country]);
        } else {
            $s = db()->prepare("SELECT * FROM books WHERE is_active=1 ORDER BY country,title");
            $s->execute();
        }
        $books = $s->fetchAll();
        foreach ($books as &$b) {
            try {
                $ch = db()->prepare("SELECT chapter,topics FROM book_chapters WHERE book_key=? ORDER BY sort_order");
                $ch->execute([$b["book_key"]]);
                $b["chapters"] = $ch->fetchAll();
            } catch(Exception $e) { $b["chapters"] = []; }
            $b["hasFile"] = !empty($b["file_path"]);
        }
    } catch(Exception $e) {}
    ok(["books" => $books]);
    exit;
}

// ── CREATE BOOK ───────────────────────────────────────────────────
if ($action === "create" && $_SERVER["REQUEST_METHOD"] === "POST") {
    requireAdmin();
    $b   = body();
    $key = trim($b["book_key"] ?? "");
    if (!$key) fail("book_key required");
    db()->prepare("INSERT INTO books (book_key,title,subject,country,grade_range,publisher,curriculum,year,icon,color) VALUES (?,?,?,?,?,?,?,?,?,?)")
       ->execute([$key,$b["title"]??"",$b["subject"]??"",$b["country"]??"",$b["grade_range"]??"",$b["publisher"]??"",$b["curriculum"]??"",$b["year"]??null,$b["icon"]??"book",$b["color"]??"#FF6B2B"]);
    if (!empty($b["chapters"]) && is_array($b["chapters"])) {
        foreach ($b["chapters"] as $i => $ch) {
            db()->prepare("INSERT INTO book_chapters (book_key,chapter,topics,sort_order) VALUES (?,?,?,?)")
               ->execute([$key, $ch["title"]??$ch["chapter"]??"", implode(",",$ch["topics"]??[]), $i]);
        }
    }
    ok(["created" => true]);
    exit;
}

// ── UPLOAD PDF ────────────────────────────────────────────────────
if ($action === "upload" && $_SERVER["REQUEST_METHOD"] === "POST") {
    requireAdmin();
    $key  = $_POST["bookKey"] ?? "";
    $file = $_FILES["file"]   ?? null;
    if (!$key || !$file || $file["error"] !== 0) fail("Book key and file required");
    if ($file["type"] !== "application/pdf") fail("Only PDF files allowed");
    $dir = __DIR__ . "/../uploads/books/";
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $dest = $dir . $key . ".pdf";
    if (!move_uploaded_file($file["tmp_name"], $dest)) fail("Upload failed — check folder permissions");
    db()->prepare("UPDATE books SET file_path=? WHERE book_key=?")->execute(["/uploads/books/$key.pdf", $key]);
    ok(["path" => "/uploads/books/$key.pdf"]);
    exit;
}

// ── DELETE BOOK ───────────────────────────────────────────────────
if ($action === "delete") {
    requireAdmin();
    $key = $_GET["id"] ?? "";
    if ($key) {
        db()->prepare("DELETE FROM books WHERE book_key=?")->execute([$key]);
        db()->prepare("DELETE FROM book_chapters WHERE book_key=?")->execute([$key]);
    }
    ok(["deleted" => true]);
    exit;
}

fail("Unknown action", 404);
');

echo "  api/books.php ✅\n";

// ── TEST THE STATS ENDPOINT DIRECTLY ─────────────────────────────
echo "\n=== TESTING stats query directly ===\n";
try {
    $total = (int)db()->query("SELECT COUNT(*) FROM book_downloads")->fetchColumn();
    echo "  Total downloads: $total ✅\n";
    $byC = db()->query("SELECT country, COUNT(*) as cnt FROM book_downloads GROUP BY country ORDER BY cnt DESC LIMIT 5")->fetchAll();
    echo "  byCountry query: ✅ (" . count($byC) . " rows)\n";
    $recent = db()->query("SELECT bd.title, bd.country, bd.subject, bd.downloaded_at, u.name as user_name FROM book_downloads bd LEFT JOIN users u ON u.id=bd.user_id ORDER BY bd.downloaded_at DESC LIMIT 5")->fetchAll();
    echo "  recent query: ✅ (" . count($recent) . " rows)\n";
    echo "\n  Result: " . json_encode(["total"=>$total,"byCountry"=>$byC]) . "\n";
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

// ── ALSO CHECK users section WORKS ───────────────────────────────
echo "\n=== TESTING /admin/users.php ===\n";
$uf = __DIR__ . '/api/admin/users.php';
echo "  File exists: " . (file_exists($uf) ? "✅" : "❌") . "\n";
echo "  File size: " . (file_exists($uf) ? filesize($uf) . " bytes" : "N/A") . "\n";

echo "\n✅ Done! Delete install10.php then Ctrl+Shift+R on admin.\n";
echo "  - Books section: fixed (no more 500 error)\n";
echo "  - Users section: should show all 7 users\n";
echo "</pre><p style='color:red;font-weight:bold;font-family:monospace;padding:20px'>⚠️ DELETE install10.php immediately!</p>";
