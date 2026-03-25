<?php
// EduStar helpers.php v4 — written by install.php
error_reporting(0);
ini_set("display_errors", 0);

if (!function_exists("jsonHeaders")) {
function jsonHeaders() {
    header("Content-Type: application/json; charset=utf-8");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Auth-Token");
    if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") { http_response_code(204); exit; }
}}

if (!function_exists("ok")) {
function ok($data = [], $code = 200) {
    http_response_code($code);
    echo json_encode(array_merge(["ok" => true], (array)$data));
    exit;
}}

if (!function_exists("fail")) {
function fail($msg, $code = 400) {
    http_response_code($code);
    echo json_encode(["ok" => false, "error" => $msg]);
    exit;
}}

if (!function_exists("body")) {
function body() {
    static $cache = null;
    if ($cache !== null) return $cache;
    $raw = file_get_contents("php://input");
    $cache = json_decode($raw ?: "{}", true) ?? [];
    return $cache;
}}

if (!function_exists("getStr")) {
function getStr($key, $arr, $default = "") {
    $v = isset($arr[$key]) ? $arr[$key] : $default;
    return is_string($v) ? trim($v) : $default;
}}

if (!function_exists("str")) {
function str($key, $arr, $default = "") { return getStr($key, $arr, $default); }}

if (!function_exists("getInt")) {
function getInt($key, $arr, $default = 0) {
    $v = isset($arr[$key]) ? $arr[$key] : $default;
    return filter_var($v, FILTER_VALIDATE_INT) !== false ? (int)$v : $default;
}}

if (!function_exists("generateToken")) {
function generateToken() { return bin2hex(random_bytes(32)); }
}

if (!function_exists("getBearerToken")) {
function getBearerToken() {
    $auth = "";

    // Check all possible locations the Authorization header could appear
    // (LiteSpeed / InfinityFree may move it to different server vars)
    $candidates = [
        "HTTP_AUTHORIZATION",
        "REDIRECT_HTTP_AUTHORIZATION",
        "HTTP_X_AUTHORIZATION",
    ];
    foreach ($candidates as $key) {
        if (!empty($_SERVER[$key])) { $auth = $_SERVER[$key]; break; }
    }

    // Try apache_request_headers() if available (works on some LiteSpeed configs)
    if (empty($auth) && function_exists("apache_request_headers")) {
        $h = apache_request_headers();
        foreach (["Authorization", "authorization", "AUTHORIZATION"] as $hk) {
            if (!empty($h[$hk])) { $auth = $h[$hk]; break; }
        }
    }

    // X-Auth-Token custom header (our fallback for LiteSpeed)
    if (empty($auth) && !empty($_SERVER["HTTP_X_AUTH_TOKEN"])) {
        return trim($_SERVER["HTTP_X_AUTH_TOKEN"]);
    }

    // Token in URL query string (?_token=...)
    if (empty($auth) && !empty($_GET["_token"])) {
        return trim($_GET["_token"]);
    }

    // Token in POST form data
    if (empty($auth) && !empty($_POST["_token"])) {
        return trim($_POST["_token"]);
    }

    // Token in request body (JSON)
    if (empty($auth)) {
        $b = body();
        if (!empty($b["_token"])) return trim($b["_token"]);
    }

    // Parse "Bearer <token>" from Authorization header
    if (!empty($auth) && preg_match("/^Bearer\s+(.+)$/i", $auth, $m)) {
        return trim($m[1]);
    }

    // If auth header is set but not Bearer format, return it raw (some clients omit "Bearer ")
    if (!empty($auth) && !preg_match("/^Bearer /i", $auth)) {
        return trim($auth);
    }

    return "";
}}

if (!function_exists("createSession")) {
function createSession($userId) {
    $token = generateToken();
    $exp   = date("Y-m-d H:i:s", strtotime("+30 days"));
    db()->prepare("INSERT INTO sessions (token, user_id, expires_at) VALUES (?,?,?)")->execute([$token, $userId, $exp]);
    return $token;
}}

if (!function_exists("requireAuth")) {
function requireAuth() {
    $token = getBearerToken();
    if (empty($token)) fail("Unauthorised", 401);
    $stmt = db()->prepare("SELECT u.* FROM sessions s JOIN users u ON u.id = s.user_id WHERE s.token = ? AND s.expires_at > NOW() AND u.is_active = 1");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if (!$user) fail("Session expired", 401);
    return $user;
}}

if (!function_exists("optionalAuth")) {
function optionalAuth() {
    $token = getBearerToken();
    if (empty($token)) return null;
    $stmt = db()->prepare("SELECT u.* FROM sessions s JOIN users u ON u.id = s.user_id WHERE s.token = ? AND s.expires_at > NOW() AND u.is_active = 1");
    $stmt->execute([$token]);
    return $stmt->fetch() ?: null;
}}

if (!function_exists("requireAdmin")) {
function requireAdmin() {
    $user = requireAuth();
    if (empty($user["is_admin"])) fail("Admin access required", 403);
    return $user;
}}

if (!function_exists("publicUser")) {
function publicUser($u) {
    return [
        "id"           => (int)$u["id"],
        "name"         => $u["name"],
        "email"        => $u["email"],
        "country"      => $u["country"],
        "grade"        => $u["grade"],
        "avatar"       => isset($u["avatar"]) ? $u["avatar"] : null,
        "points"       => (int)$u["points"],
        "level"        => (int)$u["level"],
        "quizzesTaken" => (int)$u["quizzes_taken"],
        "isAdmin"      => (bool)$u["is_admin"],
        "joinDate"     => $u["created_at"],
    ];
}}