<?php
// ================================================================
// EduStar — Shared PHP Helpers
// ================================================================
require_once __DIR__ . '/db.php';

// ── CORS & JSON headers ──────────────────────────────────────────
if (!function_exists('jsonHeaders')) {
function jsonHeaders(): void {
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: ' . CORS_ORIGIN);
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
}
}

// ── Send JSON response ───────────────────────────────────────────
if (!function_exists('ok')) {
function ok(array $data = [], int $code = 200): never {
    http_response_code($code);
    echo json_encode(['ok' => true, ...$data]);
    exit;
}
}

if (!function_exists('fail')) {
function fail(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}
}

// ── Parse JSON body ──────────────────────────────────────────────
if (!function_exists('body')) {
function body(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?? [];
}
}

// ── Token helpers ────────────────────────────────────────────────
if (!function_exists('generateToken')) {
function generateToken(): string {
    return bin2hex(random_bytes(32));
}
}

if (!function_exists('createSession')) {
function createSession(int $userId): string {
    $token = generateToken();
    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
    db()->prepare('INSERT INTO sessions (token, user_id, expires_at) VALUES (?,?,?)')
       ->execute([$token, $userId, $expires]);
    return $token;
}
}

if (!function_exists('requireAuth')) {
function requireAuth(): array {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) fail('Unauthorised', 401);
    $token = $m[1];
    $stmt = db()->prepare('
        SELECT u.* FROM sessions s
        JOIN users u ON u.id = s.user_id
        WHERE s.token = ? AND s.expires_at > NOW() AND u.is_active = 1
    ');
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if (!$user) fail('Session expired or invalid', 401);
    return $user;
}
}

if (!function_exists('optionalAuth')) {
function optionalAuth(): ?array {
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) return null;
    $stmt = db()->prepare('
        SELECT u.* FROM sessions s
        JOIN users u ON u.id = s.user_id
        WHERE s.token = ? AND s.expires_at > NOW() AND u.is_active = 1
    ');
    $stmt->execute([$m[1]]);
    return $stmt->fetch() ?: null;
}
}

if (!function_exists('requireAdmin')) {
function requireAdmin(): array {
    $user = requireAuth();
    if (!$user['is_admin']) fail('Admin access required', 403);
    return $user;
}
}

// ── Sanitise ─────────────────────────────────────────────────────
if (!function_exists('str')) {
function str(string $key, array $src): string {
    return trim($src[$key] ?? '');
}
}

if (!function_exists('intVal')) {
function intVal(string $key, array $src, int $default = 0): int {
    return isset($src[$key]) ? (int)$src[$key] : $default;
}
}

// ── User public fields ───────────────────────────────────────────
if (!function_exists('publicUser')) {
function publicUser(array $u): array {
    return [
        'id'           => (int)$u['id'],
        'name'         => $u['name'],
        'email'        => $u['email'],
        'country'      => $u['country'],
        'grade'        => $u['grade'],
        'avatar'       => $u['avatar'],
        'points'       => (int)$u['points'],
        'level'        => (int)$u['level'],
        'quizzesTaken' => (int)$u['quizzes_taken'],
        'isAdmin'      => (bool)$u['is_admin'],
        'joinDate'     => $u['created_at'],
    ];
}
}
