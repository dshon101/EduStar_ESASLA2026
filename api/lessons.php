<?php
// ================================================================
// EduStar API — /api/lessons.php
// POST /api/lessons.php?action=complete  — mark lesson done
// GET  /api/lessons.php?action=progress  — get all completed
// ================================================================
require_once __DIR__ . '/../config/helpers.php';
jsonHeaders();

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

// ── MARK COMPLETE ────────────────────────────────────────────────
if ($action === 'complete' && $method === 'POST') {
    $user = requireAuth();
    $b    = body();

    $lessonId  = str('lessonId', $b);
    $subjectId = str('subjectId', $b);
    $pts       = intVal('points', $b, 50);

    if (!$lessonId || !$subjectId) fail('lessonId and subjectId required.');

    // Idempotent insert
    $chk = db()->prepare('SELECT id FROM completed_lessons WHERE user_id = ? AND lesson_id = ?');
    $chk->execute([$user['id'], $lessonId]);
    if ($chk->fetch()) ok(['message' => 'Already completed.', 'alreadyDone' => true]);

    db()->prepare('
        INSERT INTO completed_lessons (user_id, lesson_id, subject_id, points_earned)
        VALUES (?, ?, ?, ?)
    ')->execute([$user['id'], $lessonId, $subjectId, $pts]);

    // Award points
    db()->prepare('
        UPDATE users
        SET points = points + ?,
            level  = GREATEST(1, FLOOR((points + ?) / 200) + 1)
        WHERE id = ?
    ')->execute([$pts, $pts, $user['id']]);

    ok(['message' => 'Lesson completed!', 'pointsAwarded' => $pts]);
}


// ── GET PROGRESS ──────────────────────────────────────────────────
if ($action === 'progress' && $method === 'GET') {
    $user = requireAuth();

    $stmt = db()->prepare('
        SELECT lesson_id, subject_id, points_earned, completed_at
        FROM completed_lessons
        WHERE user_id = ?
        ORDER BY completed_at DESC
    ');
    $stmt->execute([$user['id']]);
    ok(['completed' => $stmt->fetchAll()]);
}

fail('Unknown action or method.', 404);
