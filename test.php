<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<pre style='font-family:monospace;padding:20px'>";

echo "=== FILE VERSION CHECK ===\n";
$authFile = __DIR__ . '/api/auth.php';
$content  = file_get_contents($authFile);
$lines    = explode("\n", $content);
echo "auth.php line 1: " . $lines[0] . "\n";
echo "auth.php line 2: " . $lines[1] . "\n";
echo "auth.php has 'v3': " . (strpos($content, 'v3') !== false ? "YES ✅ (correct file!)" : "NO ❌ (still old file!)") . "\n";
echo "auth.php size: " . strlen($content) . " bytes\n\n";

echo "=== HELPERS CHECK ===\n";
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';
echo "getStr: " . (function_exists('getStr') ? "OK ✅" : "MISSING ❌") . "\n";
echo "ok: "     . (function_exists('ok')     ? "OK ✅" : "MISSING ❌") . "\n";
echo "fail: "   . (function_exists('fail')   ? "OK ✅" : "MISSING ❌") . "\n\n";

echo "=== FULL REGISTER SIMULATION ===\n";
try {
    $testEmail = 'verify' . time() . '@test.com';
    $hash = password_hash('test1234', PASSWORD_BCRYPT);
    db()->prepare('INSERT INTO users (name, email, password_hash, country, grade) VALUES (?,?,?,?,?)')
       ->execute(['Test User', $testEmail, $hash, 'ZW', 'Form 5']);
    $id = (int)db()->lastInsertId();
    echo "Insert OK, ID=$id ✅\n";

    $token = generateToken();
    $exp   = date('Y-m-d H:i:s', strtotime('+30 days'));
    db()->prepare('INSERT INTO sessions (token, user_id, expires_at) VALUES (?,?,?)')->execute([$token, $id, $exp]);
    echo "Session created ✅\n";

    $u = db()->prepare('SELECT * FROM users WHERE id = ?');
    $u->execute([$id]);
    $pub = publicUser($u->fetch());
    $json = json_encode(['ok' => true, 'token' => $token, 'user' => $pub]);
    echo "JSON output OK ✅\n";
    echo "Response: " . substr($json, 0, 120) . "...\n\n";

    db()->prepare('DELETE FROM sessions WHERE user_id = ?')->execute([$id]);
    db()->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    echo "Cleanup done ✅\n";
    echo "\n✅✅✅ ALL PASSED — auth.php should work! ✅✅✅\n";
} catch (Exception $e) {
    echo "ERROR ❌: " . $e->getMessage() . "\n";
}
echo "</pre>";
echo "<p style='color:red;font-family:monospace;padding:20px'><b>DELETE test.php after reading!</b></p>";
