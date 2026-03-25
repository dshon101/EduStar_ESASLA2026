<?php
// EduStar community.php v3 — fully safe with try/catch on every action
error_reporting(0);
ini_set("display_errors", 0);
ob_start();

require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../config/helpers.php";

// ── Response helpers ──────────────────────────────────────────────
function cOk($data = [], $code = 200) {
    ob_get_clean();
    http_response_code($code);
    header("Content-Type: application/json; charset=utf-8");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token");
    echo json_encode(array_merge(["ok" => true], (array)$data));
    exit;
}
function cFail($msg, $code = 400) {
    ob_get_clean();
    http_response_code($code);
    header("Content-Type: application/json; charset=utf-8");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token");
    echo json_encode(["ok" => false, "error" => $msg]);
    exit;
}

// ── Initial headers ───────────────────────────────────────────────
if (!headers_sent()) {
    header("Content-Type: application/json; charset=utf-8");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token");
}
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { ob_end_clean(); http_response_code(204); exit; }

// ── Safe syslog ───────────────────────────────────────────────────
function cLog($actorId, $actorName, $action, $type = null, $id = null, $detail = null) {
    try {
        $ip = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : null;
        db()->prepare("INSERT INTO system_logs (actor_id, actor_name, action, target_type, target_id, detail, ip) VALUES (?,?,?,?,?,?,?)")
           ->execute([$actorId, $actorName, $action, $type, $id, $detail, $ip]);
    } catch (Throwable $e) {}
}

// ── Get current user (returns null if not logged in) ─────────────
function getUser() {
    try {
        $token = getBearerToken();
        if (!$token) return null;
        $s = db()->prepare("SELECT u.* FROM sessions s JOIN users u ON u.id=s.user_id WHERE s.token=? AND s.expires_at>NOW() AND u.is_active=1");
        $s->execute([$token]);
        return $s->fetch() ?: null;
    } catch (Throwable $e) { return null; }
}

// ── Require logged in user ────────────────────────────────────────
function mustLogin() {
    $u = getUser();
    if (!$u) cFail("Please log in to continue.", 401);
    return $u;
}

$method = $_SERVER["REQUEST_METHOD"];
$action = isset($_GET["action"]) ? trim($_GET["action"]) : "list";

// ── LIST POSTS ────────────────────────────────────────────────────
if ($action === "list" && $method === "GET") {
    try {
        $cat    = trim(isset($_GET["category"]) ? $_GET["category"] : "");
        $search = trim(isset($_GET["search"])   ? $_GET["search"]   : "");
        $page   = max(1, (int)(isset($_GET["page"]) ? $_GET["page"] : 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $where  = ["cp.is_active = 1"];
        $params = [];
        if ($cat)    { $where[] = "cp.category = ?";               $params[] = $cat; }
        if ($search) { $where[] = "(cp.title LIKE ? OR cp.body LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }
        $w = implode(" AND ", $where);

        $q = db()->prepare("
            SELECT cp.id, cp.user_id, cp.title, cp.body, cp.category,
                   cp.likes, cp.is_pinned, cp.created_at,
                   u.name AS author_name, u.avatar AS author_avatar, u.country AS author_country,
                   (SELECT COUNT(*) FROM community_replies cr WHERE cr.post_id=cp.id AND cr.is_active=1) AS reply_count
            FROM community_posts cp
            JOIN users u ON u.id = cp.user_id
            WHERE $w
            ORDER BY cp.is_pinned DESC, cp.created_at DESC
            LIMIT $limit OFFSET $offset
        ");
        $q->execute($params);
        $posts = $q->fetchAll();

        $tq = db()->prepare("SELECT COUNT(*) FROM community_posts cp WHERE $w");
        $tq->execute($params);
        $total = (int)$tq->fetchColumn();

        cOk(["posts" => $posts, "total" => $total, "page" => $page, "pages" => max(1, ceil($total / $limit))]);
    } catch (Throwable $e) { cFail("Failed to load posts: " . $e->getMessage()); }
}

// ── GET SINGLE POST + REPLIES ─────────────────────────────────────
if ($action === "get" && $method === "GET") {
    try {
        $id = (int)(isset($_GET["id"]) ? $_GET["id"] : 0);
        if (!$id) cFail("Post ID required.");

        $q = db()->prepare("
            SELECT cp.*, u.name AS author_name, u.avatar AS author_avatar, u.country AS author_country,
                   (SELECT COUNT(*) FROM community_replies cr WHERE cr.post_id=cp.id AND cr.is_active=1) AS reply_count
            FROM community_posts cp JOIN users u ON u.id=cp.user_id
            WHERE cp.id=? AND cp.is_active=1
        ");
        $q->execute([$id]);
        $post = $q->fetch();
        if (!$post) cFail("Post not found.", 404);

        $rq = db()->prepare("
            SELECT cr.id, cr.post_id, cr.body, cr.likes, cr.created_at,
                   u.name AS author_name, u.avatar AS author_avatar
            FROM community_replies cr JOIN users u ON u.id=cr.user_id
            WHERE cr.post_id=? AND cr.is_active=1 ORDER BY cr.created_at ASC
        ");
        $rq->execute([$id]);
        cOk(["post" => $post, "replies" => $rq->fetchAll()]);
    } catch (Throwable $e) { cFail("Failed to load post: " . $e->getMessage()); }
}

// ── CREATE POST ───────────────────────────────────────────────────
if ($action === "create" && $method === "POST") {
    try {
        $user  = mustLogin();
        $raw   = file_get_contents("php://input");
        $b     = json_decode($raw ? $raw : "{}", true);
        if (!is_array($b)) $b = [];

        $title = trim(isset($b["title"])    ? $b["title"]    : "");
        $body_ = trim(isset($b["body"])     ? $b["body"]     : "");
        $cat   = trim(isset($b["category"]) ? $b["category"] : "general");

        if (!$title) cFail("Title is required.");
        if (!$body_) cFail("Post body is required.");
        if (strlen($title) > 220) cFail("Title is too long.");

        $validCats = ["general","homework","science","maths","english","study_tips","books","announcements"];
        if (!in_array($cat, $validCats)) $cat = "general";

        // Convert text to utf8mb4-safe encoding before inserting
        $title = mb_convert_encoding($title, "UTF-8", "UTF-8");
        $body_ = mb_convert_encoding($body_, "UTF-8", "UTF-8");

        db()->prepare("INSERT INTO community_posts (user_id, title, body, category) VALUES (?,?,?,?)")
           ->execute([(int)$user["id"], $title, $body_, $cat]);
        $postId = (int)db()->lastInsertId();

        cLog($user["id"], $user["name"], "community_post", "post", $postId, "Created: " . substr($title, 0, 60));
        cOk(["id" => $postId, "message" => "Post published."]);
    } catch (Throwable $e) {
        cFail("Failed to create post: " . $e->getMessage());
    }
}

// ── ADD REPLY ─────────────────────────────────────────────────────
if ($action === "reply" && $method === "POST") {
    try {
        $user  = mustLogin();
        $raw   = file_get_contents("php://input");
        $b     = json_decode($raw ? $raw : "{}", true);
        if (!is_array($b)) $b = [];

        $postId = (int)(isset($b["post_id"]) ? $b["post_id"] : 0);
        $body_  = trim(isset($b["body"])     ? $b["body"]     : "");
        if (!$postId) cFail("Post ID required.");
        if (!$body_)  cFail("Reply cannot be empty.");

        $pc = db()->prepare("SELECT id FROM community_posts WHERE id=? AND is_active=1");
        $pc->execute([$postId]);
        if (!$pc->fetch()) cFail("Post not found.", 404);

        $body_ = mb_convert_encoding($body_, "UTF-8", "UTF-8");
        db()->prepare("INSERT INTO community_replies (post_id, user_id, body) VALUES (?,?,?)")
           ->execute([$postId, (int)$user["id"], $body_]);
        $replyId = (int)db()->lastInsertId();

        cLog($user["id"], $user["name"], "community_reply", "reply", $replyId, "Reply to post $postId");
        cOk(["id" => $replyId, "message" => "Reply added."]);
    } catch (Throwable $e) {
        cFail("Failed to add reply: " . $e->getMessage());
    }
}

// ── LIKE / UNLIKE POST ────────────────────────────────────────────
if ($action === "like" && $method === "POST") {
    try {
        $user   = mustLogin();
        $raw    = file_get_contents("php://input");
        $b      = json_decode($raw ? $raw : "{}", true);
        $postId = (int)(isset($b["post_id"]) ? $b["post_id"] : 0);
        if (!$postId) cFail("Post ID required.");

        try {
            db()->prepare("INSERT INTO community_likes (user_id, post_id) VALUES (?,?)")
               ->execute([(int)$user["id"], $postId]);
            db()->prepare("UPDATE community_posts SET likes=likes+1 WHERE id=?")->execute([$postId]);
            $likes = (int)db()->query("SELECT likes FROM community_posts WHERE id=" . (int)$postId)->fetchColumn();
            cOk(["liked" => true, "likes" => $likes]);
        } catch (Throwable $dup) {
            // Duplicate — unlike
            db()->prepare("DELETE FROM community_likes WHERE user_id=? AND post_id=?")
               ->execute([(int)$user["id"], $postId]);
            db()->prepare("UPDATE community_posts SET likes=GREATEST(0,likes-1) WHERE id=?")->execute([$postId]);
            $likes = (int)db()->query("SELECT likes FROM community_posts WHERE id=" . (int)$postId)->fetchColumn();
            cOk(["liked" => false, "likes" => $likes]);
        }
    } catch (Throwable $e) { cFail("Like failed: " . $e->getMessage()); }
}

// ── DELETE POST ───────────────────────────────────────────────────
if ($action === "delete_post" && $method === "DELETE") {
    try {
        $user   = mustLogin();
        $postId = (int)(isset($_GET["id"]) ? $_GET["id"] : 0);
        if (!$postId) cFail("Post ID required.");

        $pq = db()->prepare("SELECT user_id FROM community_posts WHERE id=?");
        $pq->execute([$postId]);
        $post = $pq->fetch();
        if (!$post) cFail("Post not found.", 404);
        if ((int)$post["user_id"] !== (int)$user["id"] && !$user["is_admin"]) cFail("Not authorised.", 403);

        db()->prepare("UPDATE community_posts SET is_active=0 WHERE id=?")->execute([$postId]);
        cLog($user["id"], $user["name"], "delete_post", "post", $postId);
        cOk(["message" => "Post deleted."]);
    } catch (Throwable $e) { cFail("Delete failed: " . $e->getMessage()); }
}

cFail("Unknown action.", 404);