<?php
// ================================================================
// EduStar API — /api/auth.php
// POST /api/auth.php?action=register
// POST /api/auth.php?action=login
// POST /api/auth.php?action=logout
// GET  /api/auth.php?action=me
// PUT  /api/auth.php?action=update
// ================================================================
require_once __DIR__ . '/../config/helpers.php';
jsonHeaders();

$action = $_GET['action'] ?? '';


// ── REGISTER ─────────────────────────────────────────────────────
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $b = body();
    $name    = str('name', $b);
    $email   = strtolower(str('email', $b));
    $country = str('country', $b);
    $grade   = str('grade', $b);
    $pw      = str('password', $b);

    if (!$name || !$email || !$country || !$grade || !$pw)
        fail('All fields are required.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        fail('Invalid email address.');
    if (strlen($pw) < 6)
        fail('Password must be at least 6 characters.');

    // Check duplicate
    $chk = db()->prepare('SELECT id FROM users WHERE email = ?');
    $chk->execute([$email]);
    if ($chk->fetch()) fail('An account with this email already exists.');

    $hash = password_hash($pw, PASSWORD_BCRYPT, ['cost' => 12]);
    $ins = db()->prepare('
        INSERT INTO users (name, email, password_hash, country, grade)
        VALUES (?, ?, ?, ?, ?)
    ');
    $ins->execute([$name, $email, $hash, $country, $grade]);
    $userId = (int)db()->lastInsertId();

    // Update last login
    db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$userId]);

    $token = createSession($userId);
    $user  = db()->prepare('SELECT * FROM users WHERE id = ?');
    $user->execute([$userId]);
    ok(['token' => $token, 'user' => publicUser($user->fetch())], 201);
}

// ── LOGIN ─────────────────────────────────────────────────────────
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $b     = body();
    $email = strtolower(str('email', $b));
    $pw    = str('password', $b);

    if (!$email || !$pw) fail('Email and password are required.');

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pw, $user['password_hash']))
        fail('Invalid email or password.', 401);

    db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')->execute([$user['id']]);
    $token = createSession((int)$user['id']);
    ok(['token' => $token, 'user' => publicUser($user)]);
}

// ── LOGOUT ───────────────────────────────────────────────────────
if ($action === 'logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
        db()->prepare('DELETE FROM sessions WHERE token = ?')->execute([$m[1]]);
    }
    ok(['message' => 'Logged out.']);
}

// ── GET CURRENT USER ─────────────────────────────────────────────
if ($action === 'me' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $user = requireAuth();

    // Attach completed lessons
    $cls = db()->prepare('SELECT lesson_id FROM completed_lessons WHERE user_id = ?');
    $cls->execute([$user['id']]);
    $completed = $cls->fetchAll(PDO::FETCH_COLUMN);

    $data = publicUser($user);
    $data['completed'] = $completed;
    ok(['user' => $data]);
}

// ── UPDATE PROFILE / PROGRESS ────────────────────────────────────
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'PUT') {
    $user = requireAuth();
    $b    = body();

    $fields = [];
    $vals   = [];

    if (isset($b['name']))    { $fields[] = 'name = ?';    $vals[] = str('name', $b); }
    if (isset($b['country'])) { $fields[] = 'country = ?'; $vals[] = str('country', $b); }
    if (isset($b['grade']))   { $fields[] = 'grade = ?';   $vals[] = str('grade', $b); }
    if (isset($b['points']))  { $fields[] = 'points = ?';  $vals[] = max(0, intVal('points', $b)); }
    if (isset($b['level']))   { $fields[] = 'level = ?';   $vals[] = max(1, intVal('level', $b)); }
    if (isset($b['quizzesTaken'])) { $fields[] = 'quizzes_taken = ?'; $vals[] = max(0, intVal('quizzesTaken', $b)); }

    if ($fields) {
        $vals[] = $user['id'];
        db()->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?')
           ->execute($vals);
    }

    // Persist completed lessons
    if (isset($b['completed']) && is_array($b['completed'])) {
        foreach ($b['completed'] as $lessonId) {
            $lessonId = trim($lessonId);
            if (!$lessonId) continue;
            $subjectId = explode('-', $lessonId)[0];
            $pts = intVal('points', $b, 50);
            db()->prepare('
                INSERT IGNORE INTO completed_lessons (user_id, lesson_id, subject_id, points_earned)
                VALUES (?, ?, ?, ?)
            ')->execute([$user['id'], $lessonId, $subjectId, $pts]);
        }
    }

    // Re-fetch
    $fresh = db()->prepare('SELECT * FROM users WHERE id = ?');
    $fresh->execute([$user['id']]);
    $data = publicUser($fresh->fetch());
    $cls = db()->prepare('SELECT lesson_id FROM completed_lessons WHERE user_id = ?');
    $cls->execute([$user['id']]);
    $data['completed'] = $cls->fetchAll(PDO::FETCH_COLUMN);
    ok(['user' => $data]);
}

fail('Unknown action or method.', 404);
