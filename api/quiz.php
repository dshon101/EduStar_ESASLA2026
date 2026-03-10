<?php
// ================================================================
// EduStar API — /api/quiz.php
// POST /api/quiz.php?action=save   — save a quiz result
// GET  /api/quiz.php?action=scores — get my best scores
// GET  /api/quiz.php?action=leaderboard — top 20 globally
// ================================================================
require_once __DIR__ . '/../config/helpers.php';
jsonHeaders();

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// ── SAVE QUIZ SCORE ──────────────────────────────────────────────
if ($action === 'save' && $method === 'POST') {
    $user = requireAuth();
    $b    = body();

    $subjectId   = str('subjectId', $b);
    $subjectName = str('subjectName', $b);
    $scorePct    = intVal('pct', $b);
    $pts         = intVal('pts', $b);
    $correct     = intVal('correct', $b);
    $total       = intVal('total', $b);
    $timeSecs    = intVal('timeSecs', $b);

    if (!$subjectId || $total < 1) fail('Missing required fields.');

    db()->prepare('
        INSERT INTO quiz_scores
          (user_id, subject_id, subject_name, score_pct, points_earned, correct, total, time_secs)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ')->execute([$user['id'], $subjectId, $subjectName, $scorePct, $pts, $correct, $total, $timeSecs]);

    // Update user points + level
    db()->prepare('
        UPDATE users
        SET points = points + ?,
            quizzes_taken = quizzes_taken + 1,
            level = GREATEST(1, FLOOR((points + ?) / 200) + 1)
        WHERE id = ?
    ')->execute([$pts, $pts, $user['id']]);

    ok(['message' => 'Score saved.']);
}

// ── MY BEST SCORES ────────────────────────────────────────────────
if ($action === 'scores' && $method === 'GET') {
    $user = requireAuth();

    $stmt = db()->prepare('
        SELECT subject_id, subject_name, MAX(score_pct) AS best_pct,
               SUM(points_earned) AS total_pts, COUNT(*) AS attempts,
               MIN(time_secs) AS best_time, MAX(taken_at) AS last_taken
        FROM quiz_scores
        WHERE user_id = ?
        GROUP BY subject_id, subject_name
        ORDER BY best_pct DESC
        LIMIT 20
    ');
    $stmt->execute([$user['id']]);
    ok(['scores' => $stmt->fetchAll()]);
}

// ── GLOBAL LEADERBOARD ────────────────────────────────────────────
if ($action === 'leaderboard' && $method === 'GET') {
    $stmt = db()->prepare('
        SELECT u.name, u.country, u.points, u.level,
               COUNT(DISTINCT qs.id) AS quizzes_taken
        FROM users u
        LEFT JOIN quiz_scores qs ON qs.user_id = u.id
        WHERE u.is_active = 1
        GROUP BY u.id
        ORDER BY u.points DESC
        LIMIT 20
    ');
    $stmt->execute();
    ok(['leaderboard' => $stmt->fetchAll()]);
}

fail('Unknown action or method.', 404);
