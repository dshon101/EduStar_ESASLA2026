<?php
// EduStar support.php v2 — safe, no hard mailer dependency
error_reporting(0);
ini_set("display_errors", 0);
ob_start(); // Buffer ALL output — guarantees JSON response even if something leaks

require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../config/helpers.php";

// Load mailer safely — never crash if it fails
function loadMailer() {
    static $loaded = false;
    if ($loaded) return;
    $path = __DIR__ . "/../config/mailer.php";
    if (file_exists($path)) {
        try { require_once $path; } catch (Throwable $e) {}
    }
    $loaded = true;
}

// Safe syslog — never crashes
function safeLog($actorId, $actorName, $action, $targetType = null, $targetId = null, $detail = null) {
    try {
        $ip = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : null;
        db()->prepare("INSERT INTO system_logs (actor_id, actor_name, action, target_type, target_id, detail, ip) VALUES (?,?,?,?,?,?,?)")
           ->execute([$actorId, $actorName, $action, $targetType, $targetId, $detail, $ip]);
    } catch (Throwable $e) {}
}

// Safe email — never crashes, never blocks ok()
function safeEmail($fn, $args) {
    // Run email in a way that can't affect the response
    try {
        loadMailer();
        if (function_exists($fn)) {
            call_user_func_array($fn, $args);
        }
    } catch (Throwable $e) {
        error_log("EduStar email error [$fn]: " . $e->getMessage());
    }
}

// Clear any leaked output and send JSON
function sendJson($data, $code = 200) {
    $buffered = ob_get_clean(); // discard any leaked output
    if (!headers_sent()) {
        http_response_code($code);
        header("Content-Type: application/json; charset=utf-8");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token");
    }
    echo json_encode($data);
    exit;
}
function ok($data = [], $code = 200)  { sendJson(array_merge(["ok" => true],  (array)$data), $code); }
function err($msg,     $code = 400)   { sendJson(["ok" => false, "error" => $msg], $code); }

// CORS / OPTIONS
if (!headers_sent()) {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token");
}
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    ob_end_clean();
    http_response_code(204);
    exit;
}

$method = $_SERVER["REQUEST_METHOD"];
$action = isset($_GET["action"]) ? trim($_GET["action"]) : "";

// ── GET CURRENT USER (helper) ────────────────────────────────────
function getCurrentUser() {
    $token = getBearerToken();
    if (!$token) return null;
    try {
        $s = db()->prepare("SELECT u.* FROM sessions s JOIN users u ON u.id=s.user_id WHERE s.token=? AND s.expires_at>NOW() AND u.is_active=1");
        $s->execute([$token]);
        return $s->fetch() ?: null;
    } catch (Throwable $e) { return null; }
}

// ── SUBMIT TICKET ─────────────────────────────────────────────────
if ($action === "submit" && $method === "POST") {
    try {
        $raw  = file_get_contents("php://input");
        $b    = json_decode($raw ? $raw : "{}", true);
        if (!is_array($b)) $b = [];

        $name    = trim(isset($b["name"])     ? $b["name"]     : "");
        $email   = strtolower(trim(isset($b["email"])   ? $b["email"]   : ""));
        $subject = trim(isset($b["subject"])  ? $b["subject"]  : "");
        $cat     = trim(isset($b["category"]) ? $b["category"] : "general");
        $msg     = trim(isset($b["message"])  ? $b["message"]  : "");

        if (!$name)    err("Name is required.");
        if (!$email)   err("Email is required.");
        if (!$subject) err("Subject is required.");
        if (!$msg)     err("Message is required.");
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) err("Invalid email address.");
        if (strlen($msg) < 10) err("Please provide more detail in your message.");

        $validCats = ["general","technical","billing","content","account","feedback"];
        if (!in_array($cat, $validCats)) $cat = "general";

        $userId = null;
        $user   = getCurrentUser();
        if ($user) $userId = (int)$user["id"];

        db()->prepare("INSERT INTO support_tickets (user_id, name, email, subject, category, message) VALUES (?,?,?,?,?,?)")
           ->execute([$userId, $name, $email, $subject, $cat, $msg]);
        $ticketId = (int)db()->lastInsertId();

        safeLog($userId, $name, "ticket_submitted", "ticket", $ticketId, "Subject: $subject");

        // Send email BEFORE ok() — inside the output buffer so any leakage is discarded
        // safeEmail catches all errors; ob_start() at top ensures nothing corrupts the JSON
        safeEmail("sendTicketConfirmation", [$email, $name, $ticketId, $subject]);

        ok(["ticket_id" => $ticketId, "message" => "Ticket #$ticketId submitted successfully."]);

    } catch (Throwable $e) {
        err("Failed to submit ticket: " . $e->getMessage());
    }
}

// ── MY TICKETS ────────────────────────────────────────────────────
if ($action === "my_tickets" && $method === "GET") {
    try {
        $email = strtolower(trim(isset($_GET["email"]) ? $_GET["email"] : ""));
        $user  = getCurrentUser();
        if ($user) $email = $user["email"];
        if (!$email) err("Email required.");

        $q = db()->prepare("SELECT id, subject, category, status, message, created_at, updated_at, admin_reply
                            FROM support_tickets WHERE email = ? ORDER BY created_at DESC LIMIT 50");
        $q->execute([$email]);
        ok(["tickets" => $q->fetchAll()]);
    } catch (Throwable $e) {
        err("Failed to fetch tickets: " . $e->getMessage());
    }
}

// ── DELETE MY TICKET ──────────────────────────────────────────────
if ($action === "delete_ticket" && $method === "DELETE") {
    try {
        $user = getCurrentUser();
        if (!$user) err("Please log in to delete a ticket.", 401);

        $id = (int)(isset($_GET["id"]) ? $_GET["id"] : 0);
        if (!$id) err("Ticket ID required.");

        // Only allow deletion of own open/closed tickets — not in-progress or resolved
        $q = db()->prepare("SELECT id, user_id, status FROM support_tickets WHERE id = ?");
        $q->execute([$id]);
        $ticket = $q->fetch();
        if (!$ticket)                              err("Ticket not found.", 404);
        if ((int)$ticket["user_id"] !== (int)$user["id"]) err("You can only delete your own tickets.", 403);
        if (in_array($ticket["status"], ["in_progress","resolved"])) {
            err("Cannot delete a ticket that is already being handled. Contact support to close it.");
        }

        db()->prepare("DELETE FROM support_tickets WHERE id = ? AND user_id = ?")
           ->execute([$id, $user["id"]]);

        safeLog($user["id"], $user["name"], "ticket_deleted", "ticket", $id);
        ok(["message" => "Ticket deleted."]);

    } catch (Throwable $e) {
        err("Failed to delete ticket: " . $e->getMessage());
    }
}

// ── ADMIN: LIST ALL TICKETS ───────────────────────────────────────
if ($action === "admin_list" && $method === "GET") {
    try {
        $user = getCurrentUser();
        if (!$user || !$user["is_admin"]) err("Admin access required.", 403);

        $status = trim(isset($_GET["status"]) ? $_GET["status"] : "");
        if ($status) {
            $q = db()->prepare("SELECT * FROM support_tickets WHERE status = ? ORDER BY FIELD(status,'open','in_progress','resolved','closed'), created_at DESC");
            $q->execute([$status]);
        } else {
            $q = db()->prepare("SELECT * FROM support_tickets ORDER BY FIELD(status,'open','in_progress','resolved','closed'), created_at DESC");
            $q->execute();
        }
        ok(["tickets" => $q->fetchAll()]);
    } catch (Throwable $e) {
        err("Failed to fetch tickets: " . $e->getMessage());
    }
}

// ── ADMIN: REPLY TO TICKET ────────────────────────────────────────
if ($action === "admin_reply" && $method === "POST") {
    try {
        $user = getCurrentUser();
        if (!$user || !$user["is_admin"]) err("Admin access required.", 403);

        $raw    = file_get_contents("php://input");
        $b      = json_decode($raw ? $raw : "{}", true);
        if (!is_array($b)) $b = [];

        $id     = (int)(isset($b["id"])     ? $b["id"]     : 0);
        $reply  = trim(isset($b["reply"])   ? $b["reply"]  : "");
        $status = trim(isset($b["status"])  ? $b["status"] : "resolved");
        if (!$id)    err("Ticket ID required.");
        if (!$reply) err("Reply text required.");

        $validStatuses = ["open","in_progress","resolved","closed"];
        if (!in_array($status, $validStatuses)) $status = "resolved";

        $tq = db()->prepare("SELECT * FROM support_tickets WHERE id = ?");
        $tq->execute([$id]);
        $ticket = $tq->fetch();
        if (!$ticket) err("Ticket not found.", 404);

        db()->prepare("UPDATE support_tickets SET admin_reply=?, status=?, replied_at=NOW(), updated_at=NOW() WHERE id=?")
           ->execute([$reply, $status, $id]);

        safeLog($user["id"], $user["name"], "ticket_reply", "ticket", $id, "Status: $status");

        // Send email BEFORE ok() — inside the output buffer so any leakage is discarded
        safeEmail("sendTicketReplyNotification", [$ticket["email"], $ticket["name"], $id, $ticket["subject"]]);

        ok(["message" => "Reply sent."]);

    } catch (Throwable $e) {
        err("Failed to send reply: " . $e->getMessage());
    }
}

// ── ADMIN: DELETE ANY TICKET ──────────────────────────────────────
if ($action === "admin_delete" && $method === "DELETE") {
    try {
        $user = getCurrentUser();
        if (!$user || !$user["is_admin"]) err("Admin access required.", 403);
        $id = (int)(isset($_GET["id"]) ? $_GET["id"] : 0);
        if (!$id) err("Ticket ID required.");
        db()->prepare("DELETE FROM support_tickets WHERE id = ?")->execute([$id]);
        safeLog($user["id"], $user["name"], "admin_delete_ticket", "ticket", $id);
        ok(["message" => "Ticket deleted."]);
    } catch (Throwable $e) {
        err("Failed to delete ticket: " . $e->getMessage());
    }
}

err("Unknown action.", 404);