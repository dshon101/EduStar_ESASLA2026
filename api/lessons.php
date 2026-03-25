<?php
error_reporting(0); ini_set("display_errors",0);
require_once __DIR__."/../config/db.php";
require_once __DIR__."/../config/helpers.php";
jsonHeaders();
$action = $_GET["action"] ?? "";

if ($action === "complete" && $_SERVER["REQUEST_METHOD"]==="POST") {
    $user = requireAuth(); $b = body();
    $lid  = trim($b["lessonId"] ?? "");
    $sid  = trim($b["subjectId"] ?? (explode("-",$lid)[0] ?? ""));
    $pts  = max(0,(int)($b["points"]??50));
    if (!$lid) fail("lessonId required");
    db()->prepare("INSERT IGNORE INTO completed_lessons (user_id,lesson_id,subject_id,points_earned) VALUES (?,?,?,?)")
       ->execute([$user["id"],$lid,$sid,$pts]);
    $isNew = (int)db()->query("SELECT COUNT(*) FROM completed_lessons WHERE user_id={$user["id"]} AND lesson_id=".db()->quote($lid))->fetchColumn() > 0;
    if ($isNew) {
        $np = (int)$user["points"]+$pts; $nl = max(1,(int)floor($np/500)+1);
        db()->prepare("UPDATE users SET points=?,level=? WHERE id=?")->execute([$np,$nl,$user["id"]]);
    }
    $comp = db()->prepare("SELECT lesson_id FROM completed_lessons WHERE user_id=?");
    $comp->execute([$user["id"]]);
    ok(["completed"=>$comp->fetchAll(PDO::FETCH_COLUMN),"pointsEarned"=>$isNew?$pts:0]); exit;
}
if ($action === "my_progress") {
    $user = requireAuth();
    $s = db()->prepare("SELECT lesson_id,subject_id,points_earned,completed_at FROM completed_lessons WHERE user_id=? ORDER BY completed_at DESC");
    $s->execute([$user["id"]]); ok(["progress"=>$s->fetchAll()]); exit;
}
if ($action === "admin_stats") {
    requireAdmin();
    $total = db()->query("SELECT COUNT(*) FROM completed_lessons")->fetchColumn();
    $bysub = db()->query("SELECT subject_id,COUNT(*) as cnt FROM completed_lessons GROUP BY subject_id ORDER BY cnt DESC LIMIT 10")->fetchAll();
    $top   = db()->query("SELECT u.name,COUNT(*) as lessons FROM completed_lessons cl JOIN users u ON u.id=cl.user_id GROUP BY cl.user_id ORDER BY lessons DESC LIMIT 10")->fetchAll();
    ok(["total"=>(int)$total,"bySubject"=>$bysub,"topStudents"=>$top]); exit;
}
fail("Unknown action",404);
