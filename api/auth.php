<?php
// EduStar auth.php v6 — safe, backwards-compatible
error_reporting(0);
ini_set("display_errors", 0);

require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../config/helpers.php";

// Only load mailer if it exists and is readable
$mailerPath = __DIR__ . "/../config/mailer.php";
if (file_exists($mailerPath)) {
    require_once $mailerPath;
}

header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token");
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { http_response_code(204); exit; }

function authOk($data, $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge(["ok" => true], $data));
    exit;
}
function authFail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(["ok" => false, "error" => $msg]);
    exit;
}

// ── Safe system log — never crashes, never required ───────────────
function safeLog($actorId, $actorName, $action, $detail = null) {
    try {
        $ip = isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : null;
        db()->prepare(
            "INSERT INTO system_logs (actor_id, actor_name, action, target_type, target_id, detail, ip) VALUES (?,?,?,?,?,?,?)"
        )->execute([$actorId, $actorName, $action, "user", $actorId, $detail, $ip]);
    } catch (Throwable $e) {
        // Silently ignore — logs are non-critical
    }
}

// ── Safe device check — never crashes ─────────────────────────────
function safeDeviceCheck($userId) {
    try {
        $ua   = isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : "unknown";
        $hash = hash("sha256", $ua);
        $stmt = db()->prepare("SELECT id FROM device_sessions WHERE user_id = ? AND device_hash = ?");
        $stmt->execute([$userId, $hash]);
        $isNew = !$stmt->fetch();
        if ($isNew) {
            db()->prepare(
                "INSERT IGNORE INTO device_sessions (user_id, device_hash, user_agent) VALUES (?,?,?)"
            )->execute([$userId, $hash, $ua]);
        }
        return $isNew;
    } catch (Throwable $e) {
        return false;
    }
}

// ── Safe device label ─────────────────────────────────────────────
function getDeviceLabel() {
    $ua = isset($_SERVER["HTTP_USER_AGENT"]) ? $_SERVER["HTTP_USER_AGENT"] : "Unknown";
    $platform = "Unknown device";
    $browser  = "Browser";
    if (preg_match('/(iPhone|iPad|Android|Windows Phone)/i', $ua, $m)) $platform = $m[1];
    elseif (preg_match('/(Windows|Macintosh|Linux)/i', $ua, $m))        $platform = $m[1];
    if (preg_match('/(Chrome|Firefox|Safari|Edge|Opera)/i', $ua, $m))   $browser  = $m[1];
    return $browser . " on " . $platform;
}

// ── Safe email sender — never crashes ─────────────────────────────
function safeSendLoginAlert($email, $name, $deviceLabel, $isNew) {
    if (!$isNew) return;
    if (!function_exists("sendLoginAlert")) return;
    try {
        // Support both old (3-param) and new (4-param) versions of sendLoginAlert
        $rf = new ReflectionFunction("sendLoginAlert");
        if ($rf->getNumberOfParameters() >= 4) {
            @sendLoginAlert($email, $name, $deviceLabel, $isNew);
        } else {
            // Old mailer.php — skip new-device alert to avoid crash
        }
    } catch (Throwable $e) {
        // Silently ignore
    }
}

$action = $_GET["action"] ?? "";

// ── REGISTER ──────────────────────────────────────────────────────
if ($action === "register" && $_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $raw     = file_get_contents("php://input");
        $b       = json_decode($raw ? $raw : "{}", true);
        if (!is_array($b)) $b = [];
        $name    = trim(isset($b["name"])    ? $b["name"]    : "");
        $email   = strtolower(trim(isset($b["email"])   ? $b["email"]   : ""));
        $country = trim(isset($b["country"]) ? $b["country"] : "");
        $grade   = trim(isset($b["grade"])   ? $b["grade"]   : "");
        $pw      = isset($b["password"])     ? $b["password"] : "";
        if (!$name || !$email || !$country || !$grade || !$pw) authFail("All fields are required.");
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) authFail("Invalid email address.");
        if (strlen($pw) < 6) authFail("Password must be at least 6 characters.");
        $chk = db()->prepare("SELECT id FROM users WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) authFail("An account with this email already exists.");
        $hash = password_hash($pw, PASSWORD_BCRYPT);
        db()->prepare("INSERT INTO users (name, email, password_hash, country, grade) VALUES (?,?,?,?,?)")
           ->execute([$name, $email, $hash, $country, $grade]);
        $userId = (int)db()->lastInsertId();
        db()->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$userId]);
        $token = bin2hex(random_bytes(32));
        $exp   = date("Y-m-d H:i:s", strtotime("+30 days"));
        db()->prepare("INSERT INTO sessions (token, user_id, expires_at) VALUES (?,?,?)")->execute([$token, $userId, $exp]);
        safeDeviceCheck($userId);
        $stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $u = $stmt->fetch();
        safeLog($userId, $name, "register", "New account: $email");
        // Respond first, then attempt email (prevents SMTP timeout causing 500)
        authOk(["token" => $token, "user" => buildUser($u)], 201);
        try { if (function_exists("sendWelcomeEmail")) @sendWelcomeEmail($email, $name, $country, $grade); } catch (Throwable $e) {}
    } catch (Throwable $e) {
        authFail("Registration failed: " . $e->getMessage());
    }
}

// ── LOGIN ─────────────────────────────────────────────────────────
if ($action === "login" && $_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $raw   = file_get_contents("php://input");
        $b     = json_decode($raw ? $raw : "{}", true);
        if (!is_array($b)) $b = [];
        $email = strtolower(trim(isset($b["email"]) ? $b["email"] : ""));
        $pw    = isset($b["password"]) ? $b["password"] : "";
        if (!$email || !$pw) authFail("Email and password are required.");
        $stmt = db()->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($pw, $user["password_hash"])) {
            authFail("Invalid email or password.", 401);
        }
        db()->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user["id"]]);
        $token = bin2hex(random_bytes(32));
        $exp   = date("Y-m-d H:i:s", strtotime("+30 days"));
        db()->prepare("INSERT INTO sessions (token, user_id, expires_at) VALUES (?,?,?)")
           ->execute([$token, (int)$user["id"], $exp]);
        $isNew = safeDeviceCheck($user["id"]);
        $label = getDeviceLabel();
        safeLog($user["id"], $user["name"], "login", "Device: $label");
        // Respond first, then attempt email
        authOk(["token" => $token, "user" => buildUser($user)]);
        try { safeSendLoginAlert($email, $user["name"], $label, $isNew); } catch (Throwable $e) {}
    } catch (Throwable $e) {
        authFail("Login failed: " . $e->getMessage());
    }
}

// ── LOGOUT ────────────────────────────────────────────────────────
if ($action === "logout" && $_SERVER["REQUEST_METHOD"] === "POST") {
    $token = getBearerToken();
    if ($token) {
        try {
            $s = db()->prepare("SELECT u.id, u.name FROM sessions s JOIN users u ON u.id=s.user_id WHERE s.token=?");
            $s->execute([$token]);
            $u = $s->fetch();
            if ($u) safeLog($u["id"], $u["name"], "logout");
        } catch (Throwable $e) {}
        db()->prepare("DELETE FROM sessions WHERE token = ?")->execute([$token]);
    }
    authOk(["message" => "Logged out."]);
}

// ── ME ─────────────────────────────────────────────────────────────
if ($action === "me" && $_SERVER["REQUEST_METHOD"] === "GET") {
    try {
        $token = getBearerToken();
        if (!$token) authFail("Unauthorised", 401);
        $stmt = db()->prepare(
            "SELECT u.* FROM sessions s JOIN users u ON u.id = s.user_id WHERE s.token = ? AND s.expires_at > NOW() AND u.is_active = 1"
        );
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        if (!$user) authFail("Session expired", 401);
        $cls = db()->prepare("SELECT lesson_id FROM completed_lessons WHERE user_id = ?");
        $cls->execute([$user["id"]]);
        $completed = $cls->fetchAll(PDO::FETCH_COLUMN);
        $data = buildUser($user);
        $data["completed"] = $completed;
        authOk(["user" => $data]);
    } catch (Throwable $e) {
        authFail("Error: " . $e->getMessage());
    }
}

// ── UPDATE ─────────────────────────────────────────────────────────
if ($action === "update" && $_SERVER["REQUEST_METHOD"] === "PUT") {
    try {
        $token = getBearerToken();
        if (!$token) authFail("Unauthorised", 401);
        $stmt = db()->prepare(
            "SELECT u.* FROM sessions s JOIN users u ON u.id = s.user_id WHERE s.token = ? AND s.expires_at > NOW() AND u.is_active = 1"
        );
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        if (!$user) authFail("Session expired", 401);
        $raw = file_get_contents("php://input");
        $b   = json_decode($raw ? $raw : "{}", true);
        if (!is_array($b)) $b = [];
        $fields = []; $vals = [];
        if (isset($b["name"]))         { $fields[] = "name = ?";          $vals[] = trim($b["name"]); }
        if (isset($b["country"]))      { $fields[] = "country = ?";       $vals[] = trim($b["country"]); }
        if (isset($b["grade"]))        { $fields[] = "grade = ?";         $vals[] = trim($b["grade"]); }
        if (isset($b["points"]))       { $fields[] = "points = ?";        $vals[] = max(0, (int)$b["points"]); }
        if (isset($b["level"]))        { $fields[] = "level = ?";         $vals[] = max(1, (int)$b["level"]); }
        if (isset($b["quizzesTaken"])) { $fields[] = "quizzes_taken = ?"; $vals[] = max(0, (int)$b["quizzesTaken"]); }
        if (isset($b["avatar"]))       { $fields[] = "avatar = ?";        $vals[] = trim($b["avatar"]); }
        // theme and notifications — only if columns exist
        if (isset($b["theme"])) {
            try {
                db()->query("SELECT theme FROM users LIMIT 1");
                $fields[] = "theme = ?"; $vals[] = trim($b["theme"]);
            } catch (Throwable $e) {}
        }
        if (isset($b["notifications"])) {
            try {
                db()->query("SELECT notifications FROM users LIMIT 1");
                $fields[] = "notifications = ?"; $vals[] = $b["notifications"] ? 1 : 0;
            } catch (Throwable $e) {}
        }
        if (!empty($b["newPassword"])) {
            if (empty($b["currentPassword"])) authFail("Current password required.");
            if (!password_verify($b["currentPassword"], $user["password_hash"])) authFail("Current password is incorrect.");
            if (strlen($b["newPassword"]) < 6) authFail("New password must be at least 6 characters.");
            $fields[] = "password_hash = ?";
            $vals[]   = password_hash($b["newPassword"], PASSWORD_BCRYPT);
            safeLog($user["id"], $user["name"], "password_change");
        }
        if ($fields) {
            $vals[] = $user["id"];
            db()->prepare("UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?")->execute($vals);
        }
        if (isset($b["completed"]) && is_array($b["completed"])) {
            foreach ($b["completed"] as $lessonId) {
                $lessonId = trim($lessonId);
                if (!$lessonId) continue;
                $subjectId = explode("-", $lessonId)[0];
                db()->prepare(
                    "INSERT IGNORE INTO completed_lessons (user_id, lesson_id, subject_id, points_earned) VALUES (?,?,?,?)"
                )->execute([$user["id"], $lessonId, $subjectId, 50]);
            }
        }
        $fresh = db()->prepare("SELECT * FROM users WHERE id = ?");
        $fresh->execute([$user["id"]]);
        $u    = $fresh->fetch();
        $data = buildUser($u);
        $cls  = db()->prepare("SELECT lesson_id FROM completed_lessons WHERE user_id = ?");
        $cls->execute([$user["id"]]);
        $data["completed"] = $cls->fetchAll(PDO::FETCH_COLUMN);
        authOk(["user" => $data]);
    } catch (Throwable $e) {
        authFail("Update failed: " . $e->getMessage());
    }
}

// ── DELETE ACCOUNT ─────────────────────────────────────────────────
if ($action === "delete" && $_SERVER["REQUEST_METHOD"] === "DELETE") {
    try {
        $token = getBearerToken();
        if (!$token) authFail("Unauthorised", 401);
        $stmt = db()->prepare(
            "SELECT u.* FROM sessions s JOIN users u ON u.id = s.user_id WHERE s.token = ? AND s.expires_at > NOW() AND u.is_active = 1"
        );
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        if (!$user) authFail("Session expired", 401);
        $raw = file_get_contents("php://input");
        $b   = json_decode($raw ? $raw : "{}", true);
        if (!is_array($b)) $b = [];
        if (empty($b["password"])) authFail("Password required to delete account.");
        if (!password_verify($b["password"], $user["password_hash"])) authFail("Incorrect password.");
        safeLog($user["id"], $user["name"], "account_deleted", "Self-deleted: " . $user["email"]);
        db()->prepare("DELETE FROM users WHERE id = ?")->execute([$user["id"]]);
        authOk(["message" => "Account deleted."]);
    } catch (Throwable $e) {
        authFail("Delete failed: " . $e->getMessage());
    }
}

// ── RESET PROGRESS ─────────────────────────────────────────────────
if ($action === "reset_progress" && $_SERVER["REQUEST_METHOD"] === "POST") {
    try {
        $token = getBearerToken();
        if (!$token) authFail("Unauthorised", 401);
        $stmt = db()->prepare(
            "SELECT u.* FROM sessions s JOIN users u ON u.id = s.user_id WHERE s.token = ? AND s.expires_at > NOW() AND u.is_active = 1"
        );
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        if (!$user) authFail("Session expired", 401);
        db()->prepare("DELETE FROM completed_lessons WHERE user_id = ?")->execute([$user["id"]]);
        try {
            db()->prepare("DELETE FROM quiz_scores WHERE user_id = ?")->execute([$user["id"]]);
        } catch (Throwable $e) {}
        db()->prepare("UPDATE users SET points=0, level=1, quizzes_taken=0 WHERE id=?")->execute([$user["id"]]);
        safeLog($user["id"], $user["name"], "reset_progress");
        $fresh = db()->prepare("SELECT * FROM users WHERE id = ?");
        $fresh->execute([$user["id"]]);
        $u    = $fresh->fetch();
        $data = buildUser($u);
        $data["completed"] = [];
        authOk(["user" => $data]);
    } catch (Throwable $e) {
        authFail("Reset failed: " . $e->getMessage());
    }
}

// ── BUILD USER RESPONSE ────────────────────────────────────────────
function buildUser($u) {
    $data = [
        "id"           => (int)$u["id"],
        "name"         => $u["name"],
        "email"        => $u["email"],
        "country"      => $u["country"],
        "grade"        => $u["grade"],
        "avatar"       => isset($u["avatar"])  ? $u["avatar"]  : null,
        "points"       => (int)$u["points"],
        "level"        => (int)$u["level"],
        "quizzesTaken" => (int)$u["quizzes_taken"],
        "isAdmin"      => (bool)$u["is_admin"],
        "joinDate"     => $u["created_at"],
        "theme"        => isset($u["theme"])   ? $u["theme"]   : "dark",
        "notifications"=> isset($u["notifications"]) ? (bool)$u["notifications"] : true,
    ];
    return $data;
}

authFail("Unknown action.", 404);