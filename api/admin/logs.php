<?php
// EduStar admin/logs.php — System Logs API
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
$s = db()->prepare("SELECT u.* FROM sessions s JOIN users u ON u.id=s.user_id WHERE s.token=? AND s.expires_at>NOW() AND u.is_active=1");
$s->execute([$token]);
$admin = $s->fetch();
if (!$admin || !$admin["is_admin"]) { http_response_code(403); echo json_encode(["ok"=>false,"error"=>"Admin only"]); exit; }

$action = $_GET["action"] ?? "list";

if ($action === "list") {
    $page   = max(1, (int)($_GET["page"] ?? 1));
    $limit  = 100;
    $offset = ($page - 1) * $limit;
    $actFilter = trim($_GET["action_filter"] ?? "");
    $search    = trim($_GET["search"] ?? "");

    $where  = ["1=1"];
    $params = [];
    if ($actFilter) { $where[] = "action = ?"; $params[] = $actFilter; }
    if ($search)    { $where[] = "(actor_name LIKE ? OR detail LIKE ? OR action LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }

    $whereStr = implode(" AND ", $where);
    $q = db()->prepare("SELECT * FROM system_logs WHERE $whereStr ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
    $q->execute($params);
    $logs = $q->fetchAll();

    $totalQ = db()->prepare("SELECT COUNT(*) FROM system_logs WHERE $whereStr");
    $totalQ->execute($params);
    $total = (int)$totalQ->fetchColumn();

    // Get distinct actions for filter dropdown
    $actions = db()->query("SELECT DISTINCT action FROM system_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode(["ok"=>true,"logs"=>$logs,"total"=>$total,"actions"=>$actions]);
    exit;
}

if ($action === "export") {
    // Return all logs as JSON for CSV/Excel export
    $format = $_GET["format"] ?? "json";
    $q = db()->prepare("SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 10000");
    $q->execute();
    $logs = $q->fetchAll();

    if ($format === "csv") {
        header("Content-Type: text/csv; charset=utf-8");
        header("Content-Disposition: attachment; filename=edustar_logs_" . date("Y-m-d") . ".csv");
        $out = fopen("php://output", "w");
        fputcsv($out, ["ID","Actor ID","Actor Name","Action","Target Type","Target ID","Detail","IP","Date/Time"]);
        foreach ($logs as $row) {
            fputcsv($out, [
                $row["id"], $row["actor_id"], $row["actor_name"], $row["action"],
                $row["target_type"], $row["target_id"], $row["detail"], $row["ip"], $row["created_at"]
            ]);
        }
        fclose($out);
        exit;
    }
    // JSON for Excel export (handled in JS)
    echo json_encode(["ok"=>true,"logs"=>$logs]);
    exit;
}

if ($action === "clear") {
    // Only allow clearing logs older than 90 days
    db()->exec("DELETE FROM system_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)");
    // Log this action itself
    $ip = $_SERVER["REMOTE_ADDR"] ?? null;
    db()->prepare("INSERT INTO system_logs (actor_id, actor_name, action, detail, ip) VALUES (?,?,?,?,?)")
       ->execute([$admin["id"], $admin["name"], "logs_cleared", "Cleared logs older than 90 days", $ip]);
    echo json_encode(["ok"=>true,"message"=>"Old logs cleared."]);
    exit;
}

echo json_encode(["ok"=>false,"error"=>"Unknown action"]);
