<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<pre style='font-family:monospace;font-size:13px;padding:20px'>";

require_once __DIR__ . '/config/db.php';

// ── SET YOUR ACCOUNT AS ADMIN ─────────────────────────────────────
$adminEmail = 'dmshoniwa@gmail.com';
$stmt = db()->prepare('UPDATE users SET is_admin = 1 WHERE email = ?');
$stmt->execute([$adminEmail]);
$affected = $stmt->rowCount();
echo "Set is_admin=1 for $adminEmail: " . ($affected ? "✅ Done" : "⚠️ No rows updated (check email)") . "\n\n";

// ── VERIFY ALL USERS + ADMIN STATUS ──────────────────────────────
echo "=== ALL USERS ===\n";
$users = db()->query("SELECT id, name, email, is_admin, is_active FROM users ORDER BY id")->fetchAll();
foreach ($users as $u) {
    $role   = $u['is_admin']  ? '👑 ADMIN' : '👤 User';
    $status = $u['is_active'] ? '✅' : '❌ inactive';
    echo "  ID={$u['id']} $role $status — {$u['name']} ({$u['email']})\n";
}

// ── ALSO MAKE users.php NOT REQUIRE ADMIN (for now) ──────────────
// Instead just require any valid session token — safer debug
echo "\n=== UPDATING users.php to fix auth check ===\n";
$usersFile = __DIR__ . '/api/admin/users.php';
$usersContent = '<?php
error_reporting(0);
ini_set("display_errors", 0);
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../config/helpers.php";
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token");
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { http_response_code(204); exit; }

// Auth check
$token = getBearerToken();
if (!$token) { http_response_code(401); echo json_encode(["ok"=>false,"error"=>"Unauthorised — no token"]); exit; }
$s = db()->prepare("SELECT u.* FROM sessions s JOIN users u ON u.id=s.user_id WHERE s.token=? AND s.expires_at>NOW() AND u.is_active=1");
$s->execute([$token]);
$admin = $s->fetch();
if (!$admin) { http_response_code(401); echo json_encode(["ok"=>false,"error"=>"Session expired"]); exit; }
if (!$admin["is_admin"]) { http_response_code(403); echo json_encode(["ok"=>false,"error"=>"Admin access required. Your is_admin=" . $admin["is_admin"]]); exit; }

$action = $_GET["action"] ?? "list";

if ($action === "list") {
    $search = trim($_GET["search"] ?? "");
    if ($search) {
        $q = db()->prepare("SELECT id,name,email,country,grade,points,level,quizzes_taken,is_admin,is_active,created_at,last_login FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY created_at DESC");
        $like = "%" . $search . "%";
        $q->execute([$like, $like]);
    } else {
        $q = db()->prepare("SELECT id,name,email,country,grade,points,level,quizzes_taken,is_admin,is_active,created_at,last_login FROM users ORDER BY created_at DESC");
        $q->execute();
    }
    $users = $q->fetchAll();
    $stats = [
        "users"     => (int)db()->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        "quizzes"   => (int)db()->query("SELECT COALESCE(SUM(quizzes_taken),0) FROM users")->fetchColumn(),
        "lessons"   => (int)db()->query("SELECT COUNT(*) FROM completed_lessons")->fetchColumn(),
        "downloads" => (int)db()->query("SELECT COALESCE(SUM(download_count),0) FROM books")->fetchColumn(),
    ];
    echo json_encode(["ok"=>true,"users"=>$users,"stats"=>$stats]);
    exit;
}

if ($action === "toggle_admin") {
    $id = (int)($_GET["id"] ?? 0);
    if (!$id || $id === (int)$admin["id"]) { echo json_encode(["ok"=>false,"error"=>"Cannot modify yourself"]); exit; }
    $u = db()->prepare("SELECT is_admin FROM users WHERE id=?"); $u->execute([$id]);
    $row = $u->fetch();
    if (!$row) { echo json_encode(["ok"=>false,"error"=>"User not found"]); exit; }
    $newVal = $row["is_admin"] ? 0 : 1;
    db()->prepare("UPDATE users SET is_admin=? WHERE id=?")->execute([$newVal, $id]);
    echo json_encode(["ok"=>true,"is_admin"=>(bool)$newVal]);
    exit;
}

if ($action === "deactivate") {
    $id = (int)($_GET["id"] ?? 0);
    if (!$id || $id === (int)$admin["id"]) { echo json_encode(["ok"=>false,"error"=>"Cannot deactivate yourself"]); exit; }
    db()->prepare("UPDATE users SET is_active=0 WHERE id=?")->execute([$id]);
    echo json_encode(["ok"=>true]);
    exit;
}

echo json_encode(["ok"=>false,"error"=>"Unknown action"]);
';

@unlink($usersFile);
$r = file_put_contents($usersFile, $usersContent);
echo "users.php rewritten: " . ($r !== false ? "✅ ($r bytes)" : "❌ FAILED") . "\n";

echo "\n✅ All done! Now:\n";
echo "1. Delete install3.php\n";
echo "2. LOG OUT of the admin panel and log back in\n";
echo "3. Users should appear!\n";
echo "</pre>";
echo "<p style='color:red;font-weight:bold;font-family:monospace;padding:20px'>⚠️ DELETE install3.php immediately!</p>";
