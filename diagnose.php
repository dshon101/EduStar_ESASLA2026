<?php
// ================================================================
// EduStar — Live Diagnostic Tool
// Upload to htdocs/, visit it, then DELETE immediately after.
// URL: https://edustar.my-board.org/diagnose.php
// ================================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<title>EduStar Diagnostics</title>
<style>
  body{font-family:monospace;background:#0d0d1a;color:#d0d0e0;padding:24px;max-width:900px;margin:0 auto}
  h2{color:#FF6B2B;margin-top:24px}
  .ok{color:#00c9a7} .fail{color:#ff6b7a} .warn{color:#ffc107}
  .box{background:#1a1a2e;border:1px solid #333;border-radius:8px;padding:12px 16px;margin:8px 0;font-size:13px}
  pre{margin:0;white-space:pre-wrap;word-break:break-all}
</style>
</head>
<body>
<h1>🔍 EduStar Diagnostics</h1>

<h2>1. PHP Version</h2>
<div class="box">
<?php
$v = phpversion();
$ok = version_compare($v, '7.4', '>=');
echo '<span class="' . ($ok ? 'ok' : 'fail') . '">PHP ' . $v . ' ' . ($ok ? '✅' : '❌ Need 7.4+') . '</span>';
?>
</div>

<h2>2. Required Extensions</h2>
<div class="box">
<?php
$exts = ['pdo', 'pdo_mysql', 'json', 'curl', 'openssl', 'mbstring'];
foreach ($exts as $e) {
    $loaded = extension_loaded($e);
    echo '<span class="' . ($loaded ? 'ok' : 'fail') . '">' . ($loaded ? '✅' : '❌') . ' ' . $e . '</span>&nbsp;&nbsp;';
}
?>
</div>

<h2>3. Database Connection</h2>
<div class="box">
<?php
try {
    $pdo = new PDO(
        'mysql:host=sql303.infinityfree.com;port=3306;dbname=if0_41350612_edustar;charset=utf8mb4',
        'if0_41350612',
        '0EPfOPRSTK',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo '<span class="ok">✅ Connected to database</span><br>';

    // Check tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $required = ['users','sessions','device_sessions','system_logs','support_tickets','community_posts','community_replies','community_likes','books','quiz_scores','completed_lessons'];
    echo '<br><strong>Tables:</strong><br>';
    foreach ($required as $t) {
        $exists = in_array($t, $tables);
        echo '<span class="' . ($exists ? 'ok' : 'fail') . '">' . ($exists ? '✅' : '❌') . ' ' . $t . '</span><br>';
    }

    // Check user accounts
    echo '<br><strong>Admin accounts:</strong><br>';
    $stmt = $pdo->query("SELECT id, name, email, is_admin, is_active FROM users WHERE email IN ('edustarai.info@gmail.com','edustarsupport@gmail.com')");
    $admins = $stmt->fetchAll();
    if ($admins) {
        foreach ($admins as $a) {
            echo '<span class="ok">✅ ' . htmlspecialchars($a['email']) . ' — is_admin=' . $a['is_admin'] . ', is_active=' . $a['is_active'] . '</span><br>';
        }
    } else {
        echo '<span class="fail">❌ No admin accounts found</span><br>';
    }
} catch (Exception $e) {
    echo '<span class="fail">❌ DB Error: ' . htmlspecialchars($e->getMessage()) . '</span>';
}
?>
</div>

<h2>4. File Permissions & Paths</h2>
<div class="box">
<?php
$files = [
    __DIR__ . '/config/db.php',
    __DIR__ . '/config/helpers.php',
    __DIR__ . '/config/mailer.php',
    __DIR__ . '/api/auth.php',
];
foreach ($files as $f) {
    $exists = file_exists($f);
    $readable = $exists && is_readable($f);
    echo '<span class="' . ($readable ? 'ok' : 'fail') . '">' . ($readable ? '✅' : '❌') . ' ' . basename($f) . '</span><br>';
}
?>
</div>

<h2>5. Load config/db.php</h2>
<div class="box">
<?php
try {
    ob_start();
    require_once __DIR__ . '/config/db.php';
    $out = ob_get_clean();
    echo '<span class="ok">✅ db.php loaded OK</span>';
    if ($out) echo '<br><span class="warn">⚠️ Output: ' . htmlspecialchars($out) . '</span>';
} catch (Throwable $e) {
    ob_end_clean();
    echo '<span class="fail">❌ db.php error: ' . htmlspecialchars($e->getMessage()) . '</span>';
}
?>
</div>

<h2>6. Load config/mailer.php</h2>
<div class="box">
<?php
try {
    ob_start();
    require_once __DIR__ . '/config/mailer.php';
    $out = ob_get_clean();
    echo '<span class="ok">✅ mailer.php loaded OK</span>';
    if ($out) echo '<br><span class="warn">⚠️ Output: ' . htmlspecialchars($out) . '</span>';
} catch (Throwable $e) {
    ob_end_clean();
    echo '<span class="fail">❌ mailer.php error: ' . htmlspecialchars($e->getMessage()) . '</span>';
}
?>
</div>

<h2>7. Test Login API directly</h2>
<div class="box">
<?php
try {
    // Simulate what auth.php login does
    $pdo2 = new PDO(
        'mysql:host=sql303.infinityfree.com;port=3306;dbname=if0_41350612_edustar;charset=utf8mb4',
        'if0_41350612', '0EPfOPRSTK',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    $stmt = $pdo2->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    $stmt->execute(['edustarai.info@gmail.com']);
    $user = $stmt->fetch();
    if (!$user) {
        echo '<span class="fail">❌ User not found in DB</span>';
    } elseif (!password_verify('EduStarAI2026!', $user['password_hash'])) {
        echo '<span class="fail">❌ Password hash mismatch — need to reset password</span><br>';
        echo '<span class="warn">Stored hash: ' . htmlspecialchars(substr($user['password_hash'],0,30)) . '...</span>';
    } else {
        echo '<span class="ok">✅ Password verified correctly — login should work</span>';
    }
} catch (Throwable $e) {
    echo '<span class="fail">❌ Test error: ' . htmlspecialchars($e->getMessage()) . '</span>';
}
?>
</div>

<h2>8. Authorization Header Test</h2>
<div class="box">
<?php
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? 'NOT SET';
echo 'HTTP_AUTHORIZATION: <span class="' . ($auth !== 'NOT SET' ? 'ok' : 'warn') . '">' . htmlspecialchars($auth) . '</span><br>';
echo 'X-Auth-Token: <span class="ok">' . htmlspecialchars($_SERVER['HTTP_X_AUTH_TOKEN'] ?? 'NOT SET') . '</span>';
?>
</div>

<div style="background:rgba(255,193,7,0.1);border:1px solid rgba(255,193,7,0.3);border-radius:8px;padding:14px;margin-top:24px">
  <strong style="color:#ffc107">⚠️ DELETE this file after reading the results!</strong>
</div>
</body>
</html>
