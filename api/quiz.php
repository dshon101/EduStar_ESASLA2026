<?php
error_reporting(0); ini_set("display_errors",0);
require_once __DIR__."/../config/db.php";
require_once __DIR__."/../config/helpers.php";
jsonHeaders();
$action = $_GET["action"] ?? "";

if ($action === "leaderboard") {
    $rows = db()->query("SELECT id,name,country,points,level,quizzes_taken FROM users WHERE is_active=1 ORDER BY points DESC LIMIT 20")->fetchAll();
    ok(["leaderboard"=>$rows]); exit;
}
if ($action === "submit" && $_SERVER["REQUEST_METHOD"]==="POST") {
    $user = requireAuth(); $b = body();
    $sid  = trim($b["subjectId"] ?? ""); $sname = trim($b["subjectName"] ?? $sid);
    $score= max(0,(int)($b["score"]??0)); $total = max(1,(int)($b["total"]??10));
    $pct  = round($score/$total*100,2);
    db()->prepare("INSERT INTO quiz_scores (user_id,subject_id,subject_name,score,total,percentage) VALUES (?,?,?,?,?,?)")
       ->execute([$user["id"],$sid,$sname,$score,$total,$pct]);
    $nq = (int)$user["quizzes_taken"]+1;
    $pts= (int)$user["points"]+$score*10;
    $lvl= max(1,(int)floor($pts/500)+1);
    db()->prepare("UPDATE users SET quizzes_taken=?,points=?,level=? WHERE id=?")->execute([$nq,$pts,$lvl,$user["id"]]);
    ok(["score"=>$score,"total"=>$total,"percentage"=>$pct,"pointsEarned"=>$score*10]); exit;
}
if ($action === "history") {
    $user = requireAuth();
    $s = db()->prepare("SELECT * FROM quiz_scores WHERE user_id=? ORDER BY taken_at DESC LIMIT 50");
    $s->execute([$user["id"]]); ok(["history"=>$s->fetchAll()]); exit;
}
if ($action === "admin_stats") {
    requireAdmin();
    $total = db()->query("SELECT COUNT(*) FROM quiz_scores")->fetchColumn();
    $avg   = db()->query("SELECT AVG(percentage) FROM quiz_scores")->fetchColumn();
    $bysub = db()->query("SELECT subject_name,COUNT(*) as cnt,AVG(percentage) as avg_pct FROM quiz_scores GROUP BY subject_name ORDER BY cnt DESC LIMIT 10")->fetchAll();
    ok(["total"=>(int)$total,"avgScore"=>round($avg,1),"bySubject"=>$bysub]); exit;
}
fail("Unknown action",404);
