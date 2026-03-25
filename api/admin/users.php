<?php
error_reporting(0);
ini_set("display_errors", 0);
require_once __DIR__ . "/../../config/db.php";
require_once __DIR__ . "/../../config/helpers.php";
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token");
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { http_response_code(204); exit; }

// ── Sys log helper ─────────────────────────────────────────────────
if (!function_exists("sysLog")) {
function sysLog($actorId, $actorName, $action, $targetType = null, $targetId = null, $detail = null) {
    try {
        $ip = $_SERVER["REMOTE_ADDR"] ?? null;
        db()->prepare("INSERT INTO system_logs (actor_id, actor_name, action, target_type, target_id, detail, ip) VALUES (?,?,?,?,?,?,?)")
           ->execute([$actorId, $actorName, $action, $targetType, $targetId, $detail, $ip]);
    } catch (Exception $e) {}
}
}

// ── Auth check ─────────────────────────────────────────────────────
$token = getBearerToken();
if (!$token) {
    http_response_code(401);
    echo json_encode(["ok"=>false,"error"=>"Unauthorised — no token"]);
    exit;
}
$s = db()->prepare("SELECT u.* FROM sessions s JOIN users u ON u.id=s.user_id WHERE s.token=? AND s.expires_at>NOW() AND u.is_active=1");
$s->execute([$token]);
$admin = $s->fetch();
if (!$admin) {
    http_response_code(401);
    echo json_encode(["ok"=>false,"error"=>"Session expired — please log in again"]);
    exit;
}
if (!$admin["is_admin"]) {
    http_response_code(403);
    echo json_encode(["ok"=>false,"error"=>"Admin access required"]);
    exit;
}

$action = $_GET["action"] ?? "list";

// ── LIST USERS ─────────────────────────────────────────────────────
if ($action === "list") {
    try {
        $search = trim($_GET["search"] ?? "");
        if ($search) {
            $q = db()->prepare("SELECT id,name,email,country,grade,points,level,quizzes_taken,is_admin,is_active,created_at,last_login FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY created_at DESC");
            $q->execute(["%" . $search . "%", "%" . $search . "%"]);
        } else {
            $q = db()->prepare("SELECT id,name,email,country,grade,points,level,quizzes_taken,is_admin,is_active,created_at,last_login FROM users ORDER BY created_at DESC");
            $q->execute();
        }
        $users = $q->fetchAll();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["ok"=>false,"error"=>"Failed to fetch users: " . $e->getMessage()]);
        exit;
    }

    // All stats wrapped individually so one failure never blocks the rest
    $statUsers = 0; $statQuizzes = 0; $statLessons = 0;
    $statDownloads = 0; $statTickets = 0; $statPosts = 0;

    try { $statUsers   = (int)db()->query("SELECT COUNT(*) FROM users")->fetchColumn(); } catch(Exception $e) {}
    try { $statQuizzes = (int)db()->query("SELECT COALESCE(SUM(quizzes_taken),0) FROM users")->fetchColumn(); } catch(Exception $e) {}
    try { $statLessons = (int)db()->query("SELECT COUNT(*) FROM completed_lessons")->fetchColumn(); } catch(Exception $e) {}
    try { $statDownloads = (int)db()->query("SELECT COUNT(*) FROM book_downloads")->fetchColumn(); } catch(Exception $e) {}
    try { $statTickets = (int)db()->query("SELECT COUNT(*) FROM support_tickets WHERE status='open'")->fetchColumn(); } catch(Exception $e) {}
    try { $statPosts   = (int)db()->query("SELECT COUNT(*) FROM community_posts WHERE is_active=1")->fetchColumn(); } catch(Exception $e) {}

    echo json_encode([
        "ok"    => true,
        "users" => $users,
        "stats" => [
            "users"        => $statUsers,
            "quizzes"      => $statQuizzes,
            "lessons"      => $statLessons,
            "downloads"    => $statDownloads,
            "open_tickets" => $statTickets,
            "posts"        => $statPosts,
        ]
    ]);
    exit;
}

// ── TOGGLE ADMIN ───────────────────────────────────────────────────
if ($action === "toggle_admin") {
    $id = (int)($_GET["id"] ?? 0);
    if (!$id || $id === (int)$admin["id"]) {
        echo json_encode(["ok"=>false,"error"=>"Cannot modify yourself"]); exit;
    }
    $u = db()->prepare("SELECT is_admin, name, email FROM users WHERE id=?");
    $u->execute([$id]);
    $row = $u->fetch();
    if (!$row) { echo json_encode(["ok"=>false,"error"=>"User not found"]); exit; }
    $newVal = $row["is_admin"] ? 0 : 1;
    db()->prepare("UPDATE users SET is_admin=? WHERE id=?")->execute([$newVal, $id]);
    sysLog($admin["id"], $admin["name"], $newVal ? "promote_admin" : "demote_admin", "user", $id, "Target: " . $row["email"]);
    echo json_encode(["ok"=>true,"is_admin"=>(bool)$newVal]);
    exit;
}

// ── DEACTIVATE (ban) ───────────────────────────────────────────────
if ($action === "deactivate") {
    $id = (int)($_GET["id"] ?? 0);
    if (!$id || $id === (int)$admin["id"]) {
        echo json_encode(["ok"=>false,"error"=>"Cannot deactivate yourself"]); exit;
    }
    $u = db()->prepare("SELECT name, email FROM users WHERE id=?");
    $u->execute([$id]); $row = $u->fetch();
    db()->prepare("UPDATE users SET is_active=0 WHERE id=?")->execute([$id]);
    sysLog($admin["id"], $admin["name"], "ban_user", "user", $id, "Banned: " . ($row["email"] ?? "?"));
    echo json_encode(["ok"=>true]);
    exit;
}

// ── ACTIVATE (unban) ───────────────────────────────────────────────
if ($action === "activate") {
    $id = (int)($_GET["id"] ?? 0);
    if (!$id) { echo json_encode(["ok"=>false,"error"=>"ID required"]); exit; }
    $u = db()->prepare("SELECT name, email FROM users WHERE id=?");
    $u->execute([$id]); $row = $u->fetch();
    db()->prepare("UPDATE users SET is_active=1 WHERE id=?")->execute([$id]);
    sysLog($admin["id"], $admin["name"], "unban_user", "user", $id, "Unbanned: " . ($row["email"] ?? "?"));
    echo json_encode(["ok"=>true]);
    exit;
}

// ── DELETE USER ────────────────────────────────────────────────────
if ($action === "delete") {
    $id = (int)($_GET["id"] ?? 0);
    if (!$id || $id === (int)$admin["id"]) {
        echo json_encode(["ok"=>false,"error"=>"Cannot delete yourself"]); exit;
    }
    $u = db()->prepare("SELECT name, email FROM users WHERE id=?");
    $u->execute([$id]); $row = $u->fetch();
    sysLog($admin["id"], $admin["name"], "admin_delete_user", "user", $id, "Deleted: " . ($row["email"] ?? "?"));
    db()->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    echo json_encode(["ok"=>true]);
    exit;
}

echo json_encode(["ok"=>false,"error"=>"Unknown action"]);