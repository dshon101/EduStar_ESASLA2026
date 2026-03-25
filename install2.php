<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<pre style='font-family:monospace;font-size:13px;padding:20px'>";

// ── CREATE admin/ FOLDER AND WRITE users.php ──────────────────────
$adminDir  = __DIR__ . '/api/admin';
$usersFile = $adminDir . '/users.php';
$aiFile    = __DIR__ . '/api/ai.php';

// Make the directory
if (!is_dir($adminDir)) {
    $made = mkdir($adminDir, 0755, true);
    echo "Created api/admin/ folder: " . ($made ? "✅" : "❌ FAILED") . "\n";
} else {
    echo "api/admin/ folder: already exists ✅\n";
}

// ── WRITE users.php ───────────────────────────────────────────────
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

$token = getBearerToken();
if (!$token) { http_response_code(401); echo json_encode(["ok"=>false,"error"=>"Unauthorised"]); exit; }
$stmt = db()->prepare("SELECT u.* FROM sessions s JOIN users u ON u.id=s.user_id WHERE s.token=? AND s.expires_at>NOW() AND u.is_active=1");
$stmt->execute([$token]);
$admin = $stmt->fetch();
if (!$admin || !$admin["is_admin"]) { http_response_code(403); echo json_encode(["ok"=>false,"error"=>"Admin only"]); exit; }

$action = $_GET["action"] ?? "list";

if ($action === "list") {
    $search = trim($_GET["search"] ?? "");
    if ($search) {
        $s = db()->prepare("SELECT id,name,email,country,grade,points,level,quizzes_taken,is_admin,is_active,created_at,last_login FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY created_at DESC");
        $like = "%" . $search . "%";
        $s->execute([$like, $like]);
    } else {
        $s = db()->prepare("SELECT id,name,email,country,grade,points,level,quizzes_taken,is_admin,is_active,created_at,last_login FROM users ORDER BY created_at DESC");
        $s->execute();
    }
    $users = $s->fetchAll();
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
$r1 = file_put_contents($usersFile, $usersContent);
echo "api/admin/users.php: " . ($r1 !== false ? "✅ Written ($r1 bytes)" : "❌ FAILED") . "\n";

// ── WRITE ai.php ──────────────────────────────────────────────────
$aiContent = '<?php
error_reporting(0);
ini_set("display_errors", 0);
require_once __DIR__ . "/../config/helpers.php";
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token");
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { http_response_code(204); exit; }
if ($_SERVER["REQUEST_METHOD"] !== "POST") { echo json_encode(["ok"=>false,"error"=>"POST only"]); exit; }

$raw  = file_get_contents("php://input");
$body = json_decode($raw ?: "{}", true) ?? [];
$messages     = $body["messages"]  ?? [];
$systemPrompt = $body["system"]    ?? "You are a helpful African educational AI tutor.";
if (empty($messages)) { echo json_encode(["ok"=>false,"error"=>"No messages provided"]); exit; }

$pollinationsMessages = [["role"=>"system","content"=>$systemPrompt]];
foreach ($messages as $m) {
    if (!empty($m["role"]) && !empty($m["content"])) {
        $pollinationsMessages[] = ["role"=>$m["role"],"content"=>$m["content"]];
    }
}

$payload = json_encode(["model"=>"openai","messages"=>$pollinationsMessages,"seed"=>42,"private"=>true]);
$ch = curl_init("https://text.pollinations.ai/openai");
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => ["Content-Type: application/json"],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => false,
]);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($curlErr || $httpCode !== 200) {
    $lastMsg = end($messages)["content"] ?? "Hello";
    $encoded = urlencode($lastMsg);
    $ch2 = curl_init("https://text.pollinations.ai/" . $encoded);
    curl_setopt_array($ch2, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>20,CURLOPT_SSL_VERIFYPEER=>false]);
    $fallback = curl_exec($ch2);
    curl_close($ch2);
    if ($fallback) { echo json_encode(["ok"=>true,"content"=>[["type"=>"text","text"=>$fallback]]]); exit; }
    echo json_encode(["ok"=>false,"error"=>"AI service temporarily unavailable."]);
    exit;
}

$data = json_decode($response, true);
$text = $data["choices"][0]["message"]["content"] ?? null;
if (!$text) { echo json_encode(["ok"=>false,"error"=>"Empty AI response."]); exit; }
echo json_encode(["ok"=>true,"content"=>[["type"=>"text","text"=>$text]]]);
';

@unlink($aiFile);
$r2 = file_put_contents($aiFile, $aiContent);
echo "api/ai.php: " . ($r2 !== false ? "✅ Written ($r2 bytes)" : "❌ FAILED") . "\n";

// ── VERIFY: TEST USERS.PHP DIRECTLY ──────────────────────────────
echo "\n=== VERIFY users.php ===\n";
if (file_exists($usersFile)) {
    $check = file_get_contents($usersFile);
    echo "File exists: ✅\n";
    echo "File size: " . strlen($check) . " bytes\n";
    echo "Has SELECT: " . (strpos($check, 'SELECT') !== false ? "✅" : "❌") . "\n";
} else {
    echo "File missing ❌\n";
}

// ── TEST DB CONNECTION + USER COUNT ───────────────────────────────
echo "\n=== DB TEST ===\n";
try {
    require_once __DIR__ . '/config/db.php';
    $count = db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "Total users in DB: $count ✅\n";
    $users = db()->query("SELECT id, name, email, is_admin FROM users ORDER BY id DESC LIMIT 5")->fetchAll();
    echo "Recent users:\n";
    foreach ($users as $u) {
        echo "  ID={$u['id']} name={$u['name']} email={$u['email']} admin={$u['is_admin']}\n";
    }
} catch (Exception $e) {
    echo "DB ERROR: " . $e->getMessage() . "\n";
}

echo "\n✅ Done! Delete install2.php now.\n";
echo "</pre>";
echo "<p style='color:red;font-weight:bold;font-family:monospace;padding:20px'>⚠️ DELETE install2.php immediately!</p>";
