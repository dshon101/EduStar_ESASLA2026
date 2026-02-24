<?php
// ============================================================
// SMART LEARNING ASSISTANT - PHP Backend Logic
// ============================================================

session_start();

// Initialize student session data
if (!isset($_SESSION['student'])) {
    $_SESSION['student'] = [
        'name' => 'Student',
        'points' => 0,
        'level' => 1,
        'completed_lessons' => [],
        'quiz_scores' => [],
        'streak' => 0,
        'last_visit' => date('Y-m-d'),
    ];
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    $action = $_POST['action'];

    switch ($action) {
        case 'set_name':
            $_SESSION['student']['name'] = htmlspecialchars($_POST['name'] ?? 'Student');
            echo json_encode(['success' => true, 'name' => $_SESSION['student']['name']]);
            exit;

        case 'complete_lesson':
            $lesson_id = $_POST['lesson_id'] ?? '';
            if ($lesson_id && !in_array($lesson_id, $_SESSION['student']['completed_lessons'])) {
                $_SESSION['student']['completed_lessons'][] = $lesson_id;
                $_SESSION['student']['points'] += 50;
                $_SESSION['student']['level'] = max(1, floor($_SESSION['student']['points'] / 200) + 1);
            }
            echo json_encode([
                'success' => true,
                'points' => $_SESSION['student']['points'],
                'level' => $_SESSION['student']['level'],
                'completed' => $_SESSION['student']['completed_lessons'],
            ]);
            exit;

        case 'submit_quiz':
            $score = intval($_POST['score'] ?? 0);
            $subject = htmlspecialchars($_POST['subject'] ?? 'General');
            $_SESSION['student']['quiz_scores'][] = ['subject' => $subject, 'score' => $score, 'date' => date('Y-m-d')];
            $points_earned = $score * 2;
            $_SESSION['student']['points'] += $points_earned;
            $_SESSION['student']['level'] = max(1, floor($_SESSION['student']['points'] / 200) + 1);
            echo json_encode([
                'success' => true,
                'points_earned' => $points_earned,
                'total_points' => $_SESSION['student']['points'],
                'level' => $_SESSION['student']['level'],
                'message' => $score >= 80 ? 'Excellent work!' : ($score >= 60 ? 'Good job! Keep practicing.' : 'Keep trying, you can do it!'),
            ]);
            exit;

        case 'get_progress':
            echo json_encode([
                'success' => true,
                'student' => $_SESSION['student'],
            ]);
            exit;

        case 'reset_progress':
            $_SESSION['student'] = [
                'name' => $_SESSION['student']['name'],
                'points' => 0,
                'level' => 1,
                'completed_lessons' => [],
                'quiz_scores' => [],
                'streak' => 0,
                'last_visit' => date('Y-m-d'),
            ];
            echo json_encode(['success' => true]);
            exit;
    }
    echo json_encode(['success' => false, 'error' => 'Unknown action']);
    exit;
}

$student = $_SESSION['student'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>EduStar – Smart Learning Assistant</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<style>
/* ============================================================
   DESIGN SYSTEM — Warm African Sunset meets Tech Future
   ============================================================ */
:root {
  --sun: #FF6B2B;
  --gold: #F5A623;
  --sky: #1A1A2E;
  --deep: #0D0D1A;
  --teal: #00C9A7;
  --soft: #FFE8D6;
  --text: #F0EDE8;
  --muted: #8A8A9A;
  --card: rgba(255,255,255,0.05);
  --border: rgba(255,255,255,0.08);
  --radius: 16px;
  --radius-sm: 10px;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

html { scroll-behavior: smooth; }

body {
  font-family: 'DM Sans', sans-serif;
  background: var(--deep);
  color: var(--text);
  min-height: 100vh;
  overflow-x: hidden;
}

/* Background pattern */
body::before {
  content: '';
  position: fixed;
  inset: 0;
  background:
    radial-gradient(ellipse 80% 50% at 20% -10%, rgba(255,107,43,0.15) 0%, transparent 60%),
    radial-gradient(ellipse 60% 40% at 80% 110%, rgba(0,201,167,0.1) 0%, transparent 60%),
    repeating-linear-gradient(
      45deg,
      transparent,
      transparent 60px,
      rgba(255,255,255,0.01) 60px,
      rgba(255,255,255,0.01) 61px
    );
  pointer-events: none;
  z-index: 0;
}

/* ============================================================ LAYOUT */
.app { position: relative; z-index: 1; }

/* ============================================================ NAV */
nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem 2rem;
  background: rgba(13,13,26,0.8);
  backdrop-filter: blur(20px);
  border-bottom: 1px solid var(--border);
  position: sticky;
  top: 0;
  z-index: 100;
}

.logo {
  font-family: 'Syne', sans-serif;
  font-weight: 800;
  font-size: 1.5rem;
  background: linear-gradient(135deg, var(--sun), var(--gold));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  letter-spacing: -0.5px;
}

.logo span { font-weight: 400; opacity: 0.7; -webkit-text-fill-color: var(--text); }

.nav-right {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.student-badge {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 50px;
  padding: 0.4rem 1rem;
  font-size: 0.85rem;
  cursor: pointer;
  transition: all 0.2s;
}

.student-badge:hover { background: rgba(255,255,255,0.1); }

.level-dot {
  width: 24px; height: 24px;
  background: linear-gradient(135deg, var(--sun), var(--gold));
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.65rem;
  font-weight: 700;
  color: white;
  font-family: 'Syne', sans-serif;
}

.points-pill {
  background: rgba(0,201,167,0.15);
  border: 1px solid rgba(0,201,167,0.3);
  color: var(--teal);
  border-radius: 50px;
  padding: 0.3rem 0.8rem;
  font-size: 0.8rem;
  font-weight: 500;
}

/* ============================================================ HERO */
.hero {
  padding: 5rem 2rem 4rem;
  text-align: center;
  max-width: 800px;
  margin: 0 auto;
}

.hero-tag {
  display: inline-block;
  background: rgba(255,107,43,0.15);
  border: 1px solid rgba(255,107,43,0.3);
  color: var(--sun);
  border-radius: 50px;
  padding: 0.35rem 1rem;
  font-size: 0.8rem;
  font-weight: 500;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  margin-bottom: 1.5rem;
}

.hero h1 {
  font-family: 'Syne', sans-serif;
  font-weight: 800;
  font-size: clamp(2.2rem, 6vw, 4rem);
  line-height: 1.1;
  letter-spacing: -1px;
  margin-bottom: 1.2rem;
}

.hero h1 em {
  font-style: normal;
  background: linear-gradient(135deg, var(--sun), var(--gold));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero p {
  color: var(--muted);
  font-size: 1.1rem;
  line-height: 1.7;
  max-width: 560px;
  margin: 0 auto 2.5rem;
}

.hero-cta {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
}

/* ============================================================ BUTTONS */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.8rem 1.8rem;
  border-radius: 50px;
  font-size: 0.95rem;
  font-weight: 500;
  font-family: 'DM Sans', sans-serif;
  cursor: pointer;
  border: none;
  transition: all 0.25s;
  text-decoration: none;
}

.btn-primary {
  background: linear-gradient(135deg, var(--sun), var(--gold));
  color: white;
  box-shadow: 0 4px 20px rgba(255,107,43,0.4);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(255,107,43,0.5);
}

.btn-secondary {
  background: var(--card);
  border: 1px solid var(--border);
  color: var(--text);
}

.btn-secondary:hover {
  background: rgba(255,255,255,0.1);
  transform: translateY(-2px);
}

.btn-teal {
  background: linear-gradient(135deg, var(--teal), #00A896);
  color: white;
  box-shadow: 0 4px 20px rgba(0,201,167,0.3);
}

.btn-teal:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(0,201,167,0.4);
}

.btn-sm { padding: 0.5rem 1.2rem; font-size: 0.85rem; }

/* ============================================================ STATS BAR */
.stats-bar {
  display: flex;
  gap: 1.5rem;
  justify-content: center;
  flex-wrap: wrap;
  padding: 1.5rem 2rem;
  border-top: 1px solid var(--border);
  border-bottom: 1px solid var(--border);
  margin: 0 0 3rem;
}

.stat-item {
  text-align: center;
}

.stat-num {
  font-family: 'Syne', sans-serif;
  font-size: 1.8rem;
  font-weight: 800;
  background: linear-gradient(135deg, var(--sun), var(--gold));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.stat-label { font-size: 0.8rem; color: var(--muted); margin-top: 0.1rem; }

/* ============================================================ SECTIONS */
.section {
  max-width: 1100px;
  margin: 0 auto;
  padding: 2rem 2rem 4rem;
}

.section-header {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  margin-bottom: 2rem;
  flex-wrap: wrap;
  gap: 1rem;
}

.section-title {
  font-family: 'Syne', sans-serif;
  font-size: 1.6rem;
  font-weight: 700;
  letter-spacing: -0.5px;
}

.section-title span {
  background: linear-gradient(135deg, var(--sun), var(--gold));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

/* ============================================================ PROGRESS CARD */
.progress-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.5rem;
  margin-bottom: 2rem;
}

.progress-card h3 {
  font-family: 'Syne', sans-serif;
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 1rem;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-size: 0.8rem;
}

.progress-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: 1rem;
  margin-bottom: 1.2rem;
}

.progress-stat {
  background: rgba(255,255,255,0.03);
  border-radius: var(--radius-sm);
  padding: 0.8rem;
  text-align: center;
}

.progress-stat .num {
  font-family: 'Syne', sans-serif;
  font-size: 1.6rem;
  font-weight: 800;
  color: var(--gold);
}

.progress-stat .lbl { font-size: 0.75rem; color: var(--muted); margin-top: 0.2rem; }

.xp-bar-wrap {
  background: rgba(255,255,255,0.06);
  border-radius: 50px;
  height: 8px;
  overflow: hidden;
}

.xp-bar {
  height: 100%;
  background: linear-gradient(90deg, var(--sun), var(--gold));
  border-radius: 50px;
  transition: width 0.8s ease;
}

.xp-label {
  display: flex;
  justify-content: space-between;
  font-size: 0.75rem;
  color: var(--muted);
  margin-top: 0.4rem;
}

/* ============================================================ SUBJECT GRID */
.subjects-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 3rem;
}

.subject-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 1.5rem;
  cursor: pointer;
  transition: all 0.25s;
  position: relative;
  overflow: hidden;
}

.subject-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
  background: var(--accent, linear-gradient(90deg, var(--sun), var(--gold)));
  opacity: 0;
  transition: opacity 0.25s;
}

.subject-card:hover {
  border-color: rgba(255,255,255,0.2);
  transform: translateY(-4px);
  background: rgba(255,255,255,0.07);
}

.subject-card:hover::before { opacity: 1; }

.subject-icon {
  font-size: 2rem;
  margin-bottom: 0.8rem;
  display: block;
}

.subject-name {
  font-family: 'Syne', sans-serif;
  font-weight: 700;
  font-size: 1.05rem;
  margin-bottom: 0.3rem;
}

.subject-desc { font-size: 0.8rem; color: var(--muted); line-height: 1.5; }

.subject-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 1rem;
}

.lesson-count { font-size: 0.75rem; color: var(--muted); }

.subject-badge {
  font-size: 0.7rem;
  padding: 0.2rem 0.6rem;
  border-radius: 50px;
  font-weight: 500;
}

.badge-green { background: rgba(0,201,167,0.15); color: var(--teal); }
.badge-orange { background: rgba(255,107,43,0.15); color: var(--sun); }
.badge-gold { background: rgba(245,166,35,0.15); color: var(--gold); }

/* ============================================================ LESSON PANEL */
.lesson-panel {
  display: none;
  position: fixed;
  inset: 0;
  z-index: 200;
  background: rgba(0,0,0,0.7);
  backdrop-filter: blur(10px);
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.lesson-panel.open { display: flex; }

.lesson-modal {
  background: var(--sky);
  border: 1px solid var(--border);
  border-radius: 20px;
  width: 100%;
  max-width: 680px;
  max-height: 85vh;
  overflow-y: auto;
  padding: 2rem;
  position: relative;
  animation: slideUp 0.3s ease;
}

@keyframes slideUp {
  from { transform: translateY(30px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.modal-close {
  position: absolute;
  top: 1rem; right: 1rem;
  background: rgba(255,255,255,0.08);
  border: none;
  color: var(--text);
  width: 36px; height: 36px;
  border-radius: 50%;
  cursor: pointer;
  font-size: 1.1rem;
  display: flex; align-items: center; justify-content: center;
  transition: background 0.2s;
}

.modal-close:hover { background: rgba(255,255,255,0.15); }

.lesson-tag {
  font-size: 0.75rem;
  color: var(--sun);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.lesson-title {
  font-family: 'Syne', sans-serif;
  font-size: 1.6rem;
  font-weight: 800;
  margin-bottom: 1rem;
  letter-spacing: -0.5px;
}

.lesson-body {
  color: rgba(240,237,232,0.85);
  line-height: 1.8;
  font-size: 1rem;
}

.lesson-body h3 {
  font-family: 'Syne', sans-serif;
  font-size: 1.1rem;
  color: var(--gold);
  margin: 1.5rem 0 0.5rem;
}

.lesson-body p { margin-bottom: 0.8rem; }

.lesson-body .highlight-box {
  background: rgba(255,107,43,0.1);
  border-left: 3px solid var(--sun);
  border-radius: 0 8px 8px 0;
  padding: 1rem 1.2rem;
  margin: 1rem 0;
  font-size: 0.95rem;
}

.lesson-body .example-box {
  background: rgba(0,201,167,0.08);
  border: 1px solid rgba(0,201,167,0.2);
  border-radius: var(--radius-sm);
  padding: 1rem 1.2rem;
  margin: 1rem 0;
  font-family: monospace;
  font-size: 0.9rem;
  color: var(--teal);
}

.lesson-actions {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
  flex-wrap: wrap;
}

/* ============================================================ QUIZ */
.quiz-section {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 2rem;
  margin-bottom: 3rem;
}

.quiz-question {
  font-family: 'Syne', sans-serif;
  font-size: 1.1rem;
  font-weight: 700;
  margin-bottom: 0.4rem;
  line-height: 1.4;
}

.quiz-meta {
  font-size: 0.8rem;
  color: var(--muted);
  margin-bottom: 1.5rem;
}

.quiz-options {
  display: flex;
  flex-direction: column;
  gap: 0.7rem;
  margin-bottom: 1.5rem;
}

.quiz-option {
  background: rgba(255,255,255,0.04);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 0.9rem 1.2rem;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 0.95rem;
  text-align: left;
  color: var(--text);
  font-family: 'DM Sans', sans-serif;
}

.quiz-option:hover { background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.2); }
.quiz-option.selected { border-color: var(--sun); background: rgba(255,107,43,0.1); }
.quiz-option.correct { border-color: var(--teal); background: rgba(0,201,167,0.1); color: var(--teal); }
.quiz-option.wrong { border-color: #FF4757; background: rgba(255,71,87,0.1); color: #FF4757; }

.quiz-feedback {
  padding: 1rem 1.2rem;
  border-radius: var(--radius-sm);
  margin-bottom: 1rem;
  font-size: 0.9rem;
  display: none;
}

.quiz-feedback.show { display: block; }
.quiz-feedback.correct-fb { background: rgba(0,201,167,0.1); border: 1px solid rgba(0,201,167,0.3); color: var(--teal); }
.quiz-feedback.wrong-fb { background: rgba(255,71,87,0.1); border: 1px solid rgba(255,71,87,0.3); color: #FF6B7A; }

.quiz-progress-bar {
  background: rgba(255,255,255,0.06);
  border-radius: 50px;
  height: 6px;
  margin-bottom: 1.5rem;
  overflow: hidden;
}

.quiz-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--sun), var(--gold));
  border-radius: 50px;
  transition: width 0.4s ease;
}

.quiz-result {
  text-align: center;
  padding: 2rem;
  display: none;
}

.quiz-result.show { display: block; }

.score-circle {
  width: 120px; height: 120px;
  border-radius: 50%;
  background: conic-gradient(var(--sun) var(--pct, 0%), rgba(255,255,255,0.06) 0%);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 1rem;
  position: relative;
}

.score-circle::after {
  content: '';
  position: absolute;
  width: 90px; height: 90px;
  background: var(--sky);
  border-radius: 50%;
}

.score-num {
  position: relative;
  z-index: 1;
  font-family: 'Syne', sans-serif;
  font-size: 1.8rem;
  font-weight: 800;
}

.score-msg {
  font-size: 1.1rem;
  font-weight: 500;
  margin-bottom: 0.5rem;
}

.score-pts { font-size: 0.85rem; color: var(--teal); }

/* ============================================================ AI CHAT */
.chat-section {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  margin-bottom: 3rem;
}

.chat-header {
  background: linear-gradient(135deg, rgba(255,107,43,0.15), rgba(245,166,35,0.1));
  border-bottom: 1px solid var(--border);
  padding: 1.2rem 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.8rem;
}

.ai-avatar {
  width: 40px; height: 40px;
  background: linear-gradient(135deg, var(--sun), var(--gold));
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.2rem;
}

.chat-header-info h3 {
  font-family: 'Syne', sans-serif;
  font-weight: 700;
  font-size: 1rem;
}

.chat-header-info p {
  font-size: 0.75rem;
  color: var(--teal);
  display: flex; align-items: center; gap: 0.3rem;
}

.online-dot {
  width: 7px; height: 7px;
  background: var(--teal);
  border-radius: 50%;
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { opacity: 1; }
  50% { opacity: 0.4; }
}

.chat-messages {
  height: 340px;
  overflow-y: auto;
  padding: 1.5rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.chat-messages::-webkit-scrollbar { width: 4px; }
.chat-messages::-webkit-scrollbar-track { background: transparent; }
.chat-messages::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

.msg {
  display: flex;
  gap: 0.7rem;
  align-items: flex-start;
  animation: msgIn 0.3s ease;
}

@keyframes msgIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.msg.user { flex-direction: row-reverse; }

.msg-avatar {
  width: 32px; height: 32px;
  border-radius: 50%;
  flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.9rem;
}

.msg-avatar.ai { background: linear-gradient(135deg, var(--sun), var(--gold)); }
.msg-avatar.user-av { background: rgba(0,201,167,0.2); border: 1px solid rgba(0,201,167,0.3); }

.msg-bubble {
  max-width: 70%;
  padding: 0.75rem 1rem;
  border-radius: 16px;
  font-size: 0.9rem;
  line-height: 1.6;
}

.msg.ai .msg-bubble {
  background: rgba(255,255,255,0.06);
  border: 1px solid var(--border);
  border-top-left-radius: 4px;
}

.msg.user .msg-bubble {
  background: linear-gradient(135deg, rgba(255,107,43,0.3), rgba(245,166,35,0.2));
  border: 1px solid rgba(255,107,43,0.3);
  border-top-right-radius: 4px;
  text-align: right;
}

.typing-indicator {
  display: flex;
  gap: 0.3rem;
  padding: 0.8rem;
  align-items: center;
}

.typing-indicator span {
  width: 8px; height: 8px;
  background: var(--muted);
  border-radius: 50%;
  animation: bounce 1.2s infinite;
}

.typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
.typing-indicator span:nth-child(3) { animation-delay: 0.4s; }

@keyframes bounce {
  0%, 60%, 100% { transform: translateY(0); }
  30% { transform: translateY(-8px); }
}

.chat-input-area {
  border-top: 1px solid var(--border);
  padding: 1rem 1.5rem;
  display: flex;
  gap: 0.8rem;
  align-items: flex-end;
}

.chat-input {
  flex: 1;
  background: rgba(255,255,255,0.05);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 0.7rem 1rem;
  color: var(--text);
  font-family: 'DM Sans', sans-serif;
  font-size: 0.9rem;
  resize: none;
  min-height: 42px;
  max-height: 100px;
  outline: none;
  transition: border-color 0.2s;
}

.chat-input:focus { border-color: rgba(255,107,43,0.5); }
.chat-input::placeholder { color: var(--muted); }

.send-btn {
  width: 42px; height: 42px;
  background: linear-gradient(135deg, var(--sun), var(--gold));
  border: none;
  border-radius: 10px;
  color: white;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  font-size: 1rem;
  transition: all 0.2s;
  flex-shrink: 0;
}

.send-btn:hover { transform: scale(1.05); box-shadow: 0 4px 15px rgba(255,107,43,0.4); }
.send-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

.chat-suggestions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  padding: 0 1.5rem 1rem;
}

.suggestion-chip {
  background: rgba(255,255,255,0.05);
  border: 1px solid var(--border);
  color: var(--muted);
  border-radius: 50px;
  padding: 0.3rem 0.8rem;
  font-size: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
}

.suggestion-chip:hover {
  background: rgba(255,107,43,0.1);
  border-color: rgba(255,107,43,0.3);
  color: var(--sun);
}

/* ============================================================ FOOTER */
footer {
  text-align: center;
  padding: 3rem 2rem;
  border-top: 1px solid var(--border);
  color: var(--muted);
  font-size: 0.85rem;
}

footer strong { color: var(--sun); }

/* ============================================================ MODALS */
.name-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,0.8);
  backdrop-filter: blur(10px);
  z-index: 300;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.name-modal {
  background: var(--sky);
  border: 1px solid var(--border);
  border-radius: 20px;
  padding: 2.5rem;
  max-width: 420px;
  width: 100%;
  text-align: center;
  animation: slideUp 0.3s ease;
}

.name-modal h2 {
  font-family: 'Syne', sans-serif;
  font-size: 1.8rem;
  font-weight: 800;
  margin-bottom: 0.5rem;
}

.name-modal p { color: var(--muted); margin-bottom: 1.5rem; font-size: 0.9rem; }

.name-input {
  width: 100%;
  background: rgba(255,255,255,0.05);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 0.9rem 1.2rem;
  color: var(--text);
  font-family: 'DM Sans', sans-serif;
  font-size: 1rem;
  outline: none;
  text-align: center;
  margin-bottom: 1.2rem;
  transition: border-color 0.2s;
}

.name-input:focus { border-color: rgba(255,107,43,0.5); }

/* ============================================================ TOAST */
.toast-container {
  position: fixed;
  bottom: 2rem;
  right: 2rem;
  z-index: 500;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.toast {
  background: var(--sky);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 0.8rem 1.2rem;
  font-size: 0.85rem;
  max-width: 280px;
  animation: slideInRight 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.6rem;
}

@keyframes slideInRight {
  from { transform: translateX(100%); opacity: 0; }
  to { transform: translateX(0); opacity: 1; }
}

.toast.success { border-color: rgba(0,201,167,0.3); }
.toast.success .toast-icon { color: var(--teal); }
.toast.error { border-color: rgba(255,71,87,0.3); }

/* ============================================================ RESPONSIVE */
@media (max-width: 600px) {
  nav { padding: 0.8rem 1rem; }
  .section { padding: 1rem 1rem 3rem; }
  .subjects-grid { grid-template-columns: 1fr 1fr; }
  .hero { padding: 3rem 1rem 2rem; }
}
</style>
</head>
<body>
<div class="app">

<!-- NAVIGATION -->
<nav>
  <div class="logo">EduStar<span> AI</span></div>
  <div class="nav-right">
    <div class="points-pill">⭐ <span id="nav-points"><?= $student['points'] ?></span> pts</div>
    <div class="student-badge" onclick="openNameModal()">
      <div class="level-dot" id="nav-level"><?= $student['level'] ?></div>
      <span id="nav-name"><?= htmlspecialchars($student['name']) ?></span>
    </div>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-tag">🌍 Built for Every Learner in Africa</div>
  <h1>Learn Smarter<br>with <em>AI-Powered</em><br>Education</h1>
  <p>Personalized lessons, instant AI tutoring, and adaptive quizzes — helping students in rural and underserved communities access quality education.</p>
  <div class="hero-cta">
    <button class="btn btn-primary" onclick="scrollToLearn()">🚀 Start Learning</button>
    <button class="btn btn-secondary" onclick="scrollToChat()">💬 Ask AI Tutor</button>
  </div>
</section>

<!-- STATS BAR -->
<div class="stats-bar">
  <div class="stat-item"><div class="stat-num" data-count="12">0</div><div class="stat-label">Subjects</div></div>
  <div class="stat-item"><div class="stat-num" data-count="48">0</div><div class="stat-label">Lessons</div></div>
  <div class="stat-item"><div class="stat-num" data-count="96">0</div><div class="stat-label">Quiz Questions</div></div>
  <div class="stat-item"><div class="stat-num" data-count="1000">0</div><div class="stat-label">Students Helped</div></div>
</div>

<!-- MAIN CONTENT -->
<div class="section" id="learn-section">

  <!-- PROGRESS CARD -->
  <div class="progress-card" id="progress-card">
    <h3>📊 Your Learning Progress</h3>
    <div class="progress-stats">
      <div class="progress-stat">
        <div class="num" id="prog-points"><?= $student['points'] ?></div>
        <div class="lbl">Total Points</div>
      </div>
      <div class="progress-stat">
        <div class="num" id="prog-level"><?= $student['level'] ?></div>
        <div class="lbl">Level</div>
      </div>
      <div class="progress-stat">
        <div class="num" id="prog-lessons"><?= count($student['completed_lessons']) ?></div>
        <div class="lbl">Lessons Done</div>
      </div>
      <div class="progress-stat">
        <div class="num" id="prog-quizzes"><?= count($student['quiz_scores']) ?></div>
        <div class="lbl">Quizzes Taken</div>
      </div>
    </div>
    <div class="xp-bar-wrap">
      <div class="xp-bar" id="xp-bar" style="width: <?= min(100, ($student['points'] % 200) / 2) ?>%"></div>
    </div>
    <div class="xp-label">
      <span>Level <?= $student['level'] ?></span>
      <span><?= $student['points'] % 200 ?>/200 XP to next level</span>
    </div>
  </div>

  <!-- SUBJECTS -->
  <div class="section-header">
    <div class="section-title">Choose a <span>Subject</span></div>
    <button class="btn btn-secondary btn-sm" onclick="filterSubjects()">🔍 Filter</button>
  </div>

  <div class="subjects-grid" id="subjects-grid">
    <!-- Subjects rendered by JS below -->
  </div>

  <!-- QUIZ SECTION -->
  <div class="section-header">
    <div class="section-title">Daily <span>Quiz</span></div>
    <span style="font-size:0.8rem;color:var(--muted)">+2 points per correct answer</span>
  </div>

  <div class="quiz-section" id="quiz-section">
    <div id="quiz-content">
      <div class="quiz-progress-bar">
        <div class="quiz-progress-fill" id="quiz-progress-fill" style="width:0%"></div>
      </div>
      <div class="quiz-meta" id="quiz-meta">Question 1 of 5</div>
      <div class="quiz-question" id="quiz-question">Loading question...</div>
      <div class="quiz-options" id="quiz-options"></div>
      <div class="quiz-feedback" id="quiz-feedback"></div>
      <button class="btn btn-primary btn-sm" id="quiz-next-btn" onclick="nextQuestion()" style="display:none">Next Question →</button>
    </div>
    <div class="quiz-result" id="quiz-result">
      <div class="score-circle" id="score-circle">
        <div class="score-num" id="score-num">0%</div>
      </div>
      <div class="score-msg" id="score-msg"></div>
      <div class="score-pts" id="score-pts"></div>
      <br>
      <button class="btn btn-primary" onclick="restartQuiz()">🔄 Try Again</button>
    </div>
  </div>

  <!-- AI CHAT TUTOR -->
  <div class="section-header" id="chat-section">
    <div class="section-title">AI <span>Tutor Chat</span></div>
    <span style="font-size:0.8rem;color:var(--muted)">Ask anything about your studies</span>
  </div>

  <div class="chat-section">
    <div class="chat-header">
      <div class="ai-avatar">🤖</div>
      <div class="chat-header-info">
        <h3>EduStar AI Tutor</h3>
        <p><span class="online-dot"></span> Always here to help</p>
      </div>
    </div>

    <div class="chat-messages" id="chat-messages">
      <div class="msg ai">
        <div class="msg-avatar ai">🤖</div>
        <div class="msg-bubble">
          👋 Hujambo! I'm your personal AI tutor. I can explain any topic, help you understand difficult concepts, check your work, or just answer your questions. What are you studying today?
        </div>
      </div>
    </div>

    <div class="chat-suggestions" id="chat-suggestions">
      <div class="suggestion-chip" onclick="sendSuggestion(this)">Explain photosynthesis</div>
      <div class="suggestion-chip" onclick="sendSuggestion(this)">How does multiplication work?</div>
      <div class="suggestion-chip" onclick="sendSuggestion(this)">What caused World War I?</div>
      <div class="suggestion-chip" onclick="sendSuggestion(this)">Help me with fractions</div>
    </div>

    <div class="chat-input-area">
      <textarea class="chat-input" id="chat-input" placeholder="Ask your question..." rows="1"
        onkeydown="handleChatKey(event)" oninput="autoResize(this)"></textarea>
      <button class="send-btn" id="send-btn" onclick="sendMessage()">➤</button>
    </div>
  </div>

</div><!-- end .section -->

<!-- FOOTER -->
<footer>
  <p>Built with ❤️ for students across Africa • <strong>EduStar AI</strong> — Smart Learning for Everyone</p>
  <p style="margin-top:0.5rem;font-size:0.75rem;opacity:0.6">Powered by AI • Free for all students • No internet? Download lessons!</p>
</footer>

</div><!-- end .app -->

<!-- LESSON PANEL -->
<div class="lesson-panel" id="lesson-panel">
  <div class="lesson-modal">
    <button class="modal-close" onclick="closeLesson()">✕</button>
    <div class="lesson-tag" id="lesson-subject-tag"></div>
    <div class="lesson-title" id="lesson-title"></div>
    <div class="lesson-body" id="lesson-body"></div>
    <div class="lesson-actions">
      <button class="btn btn-primary" onclick="completeLesson()">✅ Mark Complete (+50 pts)</button>
      <button class="btn btn-secondary btn-sm" onclick="closeLesson()">Back to Subjects</button>
    </div>
  </div>
</div>

<!-- NAME MODAL -->
<div class="name-modal-overlay" id="name-modal" style="display:none">
  <div class="name-modal">
    <div style="font-size:3rem;margin-bottom:1rem">🌟</div>
    <h2>Welcome, Learner!</h2>
    <p>Enter your name to personalize your learning experience and track your progress.</p>
    <input type="text" class="name-input" id="name-input" placeholder="Your name..." maxlength="30"
      onkeydown="if(event.key==='Enter') saveName()">
    <button class="btn btn-primary" style="width:100%" onclick="saveName()">Let's Start Learning! 🚀</button>
  </div>
</div>

<!-- TOAST CONTAINER -->
<div class="toast-container" id="toast-container"></div>

<script>
// ================================================================
// SMART LEARNING ASSISTANT — JavaScript Application
// ================================================================

// ── DATA: Subjects & Lessons ────────────────────────────────────
const SUBJECTS = [
  {
    id: 'math', name: 'Mathematics', icon: '📐', color: '#FF6B2B',
    desc: 'Numbers, algebra, geometry and more',
    badge: { text: 'Popular', cls: 'badge-orange' },
    lessons: [
      {
        id: 'math-1', title: 'Understanding Fractions',
        body: `
          <h3>What is a Fraction?</h3>
          <p>A fraction represents a part of a whole. It is written as one number over another, like this: ½ or ¾.</p>
          <div class="highlight-box">
            📌 The <strong>top number</strong> is called the <em>numerator</em> — it tells you how many parts you have.<br>
            The <strong>bottom number</strong> is called the <em>denominator</em> — it tells you how many equal parts the whole is divided into.
          </div>
          <h3>Example</h3>
          <p>If you cut a mango into 4 equal pieces and eat 1 piece, you have eaten 1/4 of the mango.</p>
          <div class="example-box">🥭 1 piece eaten ÷ 4 total pieces = 1/4</div>
          <h3>Adding Fractions</h3>
          <p>To add fractions with the same denominator, simply add the numerators:</p>
          <div class="example-box">1/4 + 2/4 = 3/4</div>
          <p>If denominators are different, find a common denominator first. For example: 1/2 + 1/3 → convert to 3/6 + 2/6 = 5/6</p>
        `
      },
      {
        id: 'math-2', title: 'Introduction to Algebra',
        body: `
          <h3>What is Algebra?</h3>
          <p>Algebra uses letters (called <em>variables</em>) to represent unknown numbers. We use algebra to solve problems where we don't know a value yet.</p>
          <div class="highlight-box">💡 Think of a variable like an empty box: □ + 3 = 7. What goes in the box? The answer is 4!</div>
          <h3>Solving Simple Equations</h3>
          <p>Equation: x + 5 = 12</p>
          <div class="example-box">
            x + 5 = 12<br>
            x = 12 - 5<br>
            x = 7 ✓
          </div>
          <p>Always do the <em>same operation</em> on both sides of the equation to keep it balanced — like a scale!</p>
        `
      }
    ]
  },
  {
    id: 'science', name: 'Science', icon: '🔬', color: '#00C9A7',
    desc: 'Biology, chemistry, physics, and nature',
    badge: { text: 'Recommended', cls: 'badge-green' },
    lessons: [
      {
        id: 'sci-1', title: 'How Plants Make Food (Photosynthesis)',
        body: `
          <h3>What is Photosynthesis?</h3>
          <p>Photosynthesis is the process plants use to make their own food using sunlight, water, and carbon dioxide.</p>
          <div class="highlight-box">
            🌱 Plants are the only living things that can make their own food — they are called <em>producers</em>!
          </div>
          <h3>The Simple Formula</h3>
          <div class="example-box">
            Sunlight + Water (H₂O) + Carbon Dioxide (CO₂)<br>
            ↓<br>
            Glucose (Sugar) + Oxygen (O₂)
          </div>
          <h3>Where Does It Happen?</h3>
          <p>Photosynthesis takes place inside the <strong>chloroplasts</strong> — tiny green structures in plant cells. The green color comes from a chemical called <em>chlorophyll</em>.</p>
          <p>This is why leaves are green and why plants need sunlight to survive!</p>
        `
      },
      {
        id: 'sci-2', title: 'The Water Cycle',
        body: `
          <h3>What is the Water Cycle?</h3>
          <p>Water on Earth moves in a continuous cycle. It never truly disappears — it just changes form!</p>
          <h3>Four Main Steps</h3>
          <div class="highlight-box">
            ☀️ <strong>Evaporation:</strong> Heat from the sun turns water into vapour which rises into the air.<br><br>
            ☁️ <strong>Condensation:</strong> Water vapour cools and forms clouds.<br><br>
            🌧️ <strong>Precipitation:</strong> Water falls as rain, hail, or snow.<br><br>
            🌊 <strong>Collection:</strong> Water collects in rivers, lakes, and oceans — and the cycle repeats!
          </div>
          <p>Understanding the water cycle helps us understand weather, farming, and why we must protect our rivers and forests.</p>
        `
      }
    ]
  },
  {
    id: 'english', name: 'English', icon: '📖', color: '#F5A623',
    desc: 'Reading, writing, grammar and vocabulary',
    badge: { text: 'Essential', cls: 'badge-gold' },
    lessons: [
      {
        id: 'eng-1', title: 'Parts of Speech',
        body: `
          <h3>What are Parts of Speech?</h3>
          <p>Every word in a sentence plays a role. These roles are called <em>parts of speech</em>. The main ones are:</p>
          <div class="highlight-box">
            🔵 <strong>Noun</strong> — A person, place, or thing. (dog, Nairobi, happiness)<br><br>
            🟢 <strong>Verb</strong> — An action or state. (run, is, think)<br><br>
            🟡 <strong>Adjective</strong> — Describes a noun. (big, red, happy)<br><br>
            🔴 <strong>Adverb</strong> — Describes a verb. (quickly, always, very)<br><br>
            🟣 <strong>Pronoun</strong> — Replaces a noun. (he, she, it, they)
          </div>
          <h3>Example Sentence</h3>
          <div class="example-box">
            "The <u>clever</u> [adj] <u>girl</u> [noun] <u>quickly</u> [adv] <u>solved</u> [verb] the puzzle."
          </div>
        `
      }
    ]
  },
  {
    id: 'history', name: 'History', icon: '🏛️', color: '#9B5DE5',
    desc: 'African history, world events, and culture',
    badge: { text: 'New', cls: 'badge-green' },
    lessons: [
      {
        id: 'hist-1', title: 'African Independence Movements',
        body: `
          <h3>The Fight for Freedom</h3>
          <p>In the 20th century, African nations fought for and won their independence from colonial powers. This period is called the <em>African Decolonization Movement</em>.</p>
          <div class="highlight-box">
            🇬🇭 <strong>Ghana (1957)</strong> — First sub-Saharan African country to gain independence, led by Kwame Nkrumah.<br><br>
            🇿🇦 <strong>South Africa (1994)</strong> — Nelson Mandela became president after the end of apartheid.<br><br>
            🇿🇲 <strong>Zambia (1964)</strong> — Kenneth Kaunda led the nation to independence from Britain.
          </div>
          <p>These leaders inspired people around the world with their message of justice, unity, and self-determination.</p>
          <div class="example-box">"Africa must unite." — Kwame Nkrumah</div>
        `
      }
    ]
  },
  {
    id: 'geography', name: 'Geography', icon: '🌍', color: '#00B4D8',
    desc: 'Maps, continents, climate, and landforms',
    badge: { text: 'Popular', cls: 'badge-orange' },
    lessons: [
      {
        id: 'geo-1', title: 'Africa\'s Major Landforms',
        body: `
          <h3>The Geography of Africa</h3>
          <p>Africa is the second largest continent in the world. It has an incredible variety of landscapes.</p>
          <div class="highlight-box">
            🏜️ <strong>Sahara Desert</strong> — The largest hot desert in the world, covering North Africa.<br><br>
            🌿 <strong>Congo Rainforest</strong> — The second largest rainforest on Earth, rich in biodiversity.<br><br>
            🏔️ <strong>Mount Kilimanjaro</strong> — Africa's highest mountain, located in Tanzania (5,895m).<br><br>
            🌊 <strong>Nile River</strong> — The world's longest river, flowing through 11 countries.
          </div>
          <p>Africa's geography directly affects its climate, farming, and where people choose to live.</p>
        `
      }
    ]
  },
  {
    id: 'computer', name: 'Computer Studies', icon: '💻', color: '#06D6A0',
    desc: 'ICT, coding basics, and digital skills',
    badge: { text: 'Future Skills', cls: 'badge-green' },
    lessons: [
      {
        id: 'comp-1', title: 'Introduction to Coding',
        body: `
          <h3>What is Coding?</h3>
          <p>Coding (or programming) is the process of giving instructions to a computer. Computers only understand very specific instructions written in programming languages.</p>
          <div class="highlight-box">
            💡 Just like you write instructions in English or Swahili for a person, you write instructions in Python, JavaScript, or other languages for a computer!
          </div>
          <h3>Your First Code</h3>
          <p>This is what a simple program looks like in Python:</p>
          <div class="example-box">
            # This tells the computer to print a greeting<br>
            print("Hello, Africa! You are amazing! 🌍")
          </div>
          <h3>Why Learn to Code?</h3>
          <p>Coding is one of the most valuable skills you can learn. It helps you solve problems, build apps, get great jobs, and shape the future of Africa's technology.</p>
        `
      }
    ]
  }
];

// ── DATA: Quiz Questions ─────────────────────────────────────────
const QUIZ_QUESTIONS = [
  { q: "What is the top number in a fraction called?", opts: ["Denominator", "Numerator", "Divisor", "Multiplier"], ans: 1, subject: "Mathematics" },
  { q: "What gas do plants release during photosynthesis?", opts: ["Carbon Dioxide", "Nitrogen", "Oxygen", "Hydrogen"], ans: 2, subject: "Science" },
  { q: "Which country was the first in sub-Saharan Africa to gain independence?", opts: ["Nigeria", "Kenya", "Ghana", "South Africa"], ans: 2, subject: "History" },
  { q: "What is the world's longest river?", opts: ["Amazon", "Congo", "Niger", "Nile"], ans: 3, subject: "Geography" },
  { q: "In the equation x + 5 = 12, what is x?", opts: ["5", "6", "7", "17"], ans: 2, subject: "Mathematics" },
  { q: "What is a word that describes a noun called?", opts: ["Adverb", "Verb", "Adjective", "Pronoun"], ans: 2, subject: "English" },
  { q: "Africa's highest mountain is:", opts: ["Mount Kenya", "Mount Kilimanjaro", "Mount Elgon", "Atlas Mountains"], ans: 1, subject: "Geography" },
  { q: "What do plants need for photosynthesis? (Choose the best answer)", opts: ["Water only", "Sunlight only", "Sunlight, water, and CO₂", "Soil and water"], ans: 2, subject: "Science" },
];

// ── STATE ────────────────────────────────────────────────────────
let state = {
  student: {
    name: '<?= addslashes($student['name']) ?>',
    points: <?= $student['points'] ?>,
    level: <?= $student['level'] ?>,
    completed: <?= json_encode($student['completed_lessons']) ?>,
    quizzesTaken: <?= count($student['quiz_scores']) ?>
  },
  currentLesson: null,
  quiz: { questions: [], current: 0, score: 0, answered: false },
  chatLoading: false
};

// ── INIT ─────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  renderSubjects();
  initQuiz();
  animateCounters();

  // Show name modal if default name
  if (state.student.name === 'Student') {
    setTimeout(() => document.getElementById('name-modal').style.display = 'flex', 600);
  }
});

// ── COUNTER ANIMATION ────────────────────────────────────────────
function animateCounters() {
  document.querySelectorAll('[data-count]').forEach(el => {
    const target = parseInt(el.dataset.count);
    const suffix = target >= 1000 ? '+' : '';
    let current = 0;
    const step = target / 50;
    const interval = setInterval(() => {
      current = Math.min(current + step, target);
      el.textContent = Math.floor(current) + suffix;
      if (current >= target) clearInterval(interval);
    }, 30);
  });
}

// ── SUBJECTS ─────────────────────────────────────────────────────
function renderSubjects() {
  const grid = document.getElementById('subjects-grid');
  grid.innerHTML = SUBJECTS.map(sub => {
    const lessonsCompleted = sub.lessons.filter(l => state.student.completed.includes(l.id)).length;
    const allDone = lessonsCompleted === sub.lessons.length;
    return `
      <div class="subject-card" onclick="openSubject('${sub.id}')">
        <span class="subject-icon">${sub.icon}</span>
        <div class="subject-name">${sub.name}</div>
        <div class="subject-desc">${sub.desc}</div>
        <div class="subject-meta">
          <span class="lesson-count">${lessonsCompleted}/${sub.lessons.length} lessons${allDone ? ' ✅' : ''}</span>
          <span class="subject-badge ${sub.badge.cls}">${sub.badge.text}</span>
        </div>
      </div>
    `;
  }).join('');
}

function openSubject(id) {
  const sub = SUBJECTS.find(s => s.id === id);
  if (!sub || sub.lessons.length === 0) return;
  openLesson(sub.lessons[0], sub);
}

// ── LESSONS ───────────────────────────────────────────────────────
function openLesson(lesson, subject) {
  state.currentLesson = { ...lesson, subjectId: subject.id, subjectName: subject.name };
  document.getElementById('lesson-subject-tag').textContent = subject.icon + ' ' + subject.name;
  document.getElementById('lesson-title').textContent = lesson.title;
  document.getElementById('lesson-body').innerHTML = lesson.body;
  document.getElementById('lesson-panel').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeLesson() {
  document.getElementById('lesson-panel').classList.remove('open');
  document.body.style.overflow = '';
  state.currentLesson = null;
}

async function completeLesson() {
  if (!state.currentLesson) return;
  const lessonId = state.currentLesson.id;
  if (state.student.completed.includes(lessonId)) {
    showToast('✅ Already completed! Great job!', 'success');
    closeLesson();
    return;
  }

  try {
    const res = await phpPost('complete_lesson', { lesson_id: lessonId });
    state.student.completed = res.completed;
    state.student.points = res.points;
    state.student.level = res.level;
    updateProgressUI();
    renderSubjects();
    showToast('🎉 Lesson complete! +50 points earned!', 'success');
    closeLesson();
  } catch(e) {
    // Fallback: update locally
    state.student.completed.push(lessonId);
    state.student.points += 50;
    updateProgressUI();
    renderSubjects();
    showToast('🎉 Lesson complete! +50 points earned!', 'success');
    closeLesson();
  }
}

// ── QUIZ ──────────────────────────────────────────────────────────
function initQuiz() {
  const shuffled = [...QUIZ_QUESTIONS].sort(() => Math.random() - 0.5).slice(0, 5);
  state.quiz = { questions: shuffled, current: 0, score: 0, answered: false };
  renderQuestion();
}

function renderQuestion() {
  const q = state.quiz.questions[state.quiz.current];
  const total = state.quiz.questions.length;

  document.getElementById('quiz-meta').textContent = `Question ${state.quiz.current + 1} of ${total} • ${q.subject}`;
  document.getElementById('quiz-question').textContent = q.q;
  document.getElementById('quiz-progress-fill').style.width = `${(state.quiz.current / total) * 100}%`;
  document.getElementById('quiz-feedback').classList.remove('show', 'correct-fb', 'wrong-fb');
  document.getElementById('quiz-next-btn').style.display = 'none';

  const optionsEl = document.getElementById('quiz-options');
  optionsEl.innerHTML = q.opts.map((opt, i) => `
    <button class="quiz-option" onclick="selectAnswer(${i})">${opt}</button>
  `).join('');

  state.quiz.answered = false;
}

function selectAnswer(idx) {
  if (state.quiz.answered) return;
  state.quiz.answered = true;

  const q = state.quiz.questions[state.quiz.current];
  const opts = document.querySelectorAll('.quiz-option');
  const feedback = document.getElementById('quiz-feedback');

  opts.forEach((btn, i) => {
    btn.onclick = null;
    if (i === q.ans) btn.classList.add('correct');
    else if (i === idx) btn.classList.add('wrong');
  });

  const correct = idx === q.ans;
  if (correct) state.quiz.score++;

  feedback.textContent = correct
    ? `✅ Correct! Well done! ${q.opts[q.ans]} is right.`
    : `❌ Not quite. The correct answer is: ${q.opts[q.ans]}`;
  feedback.className = `quiz-feedback show ${correct ? 'correct-fb' : 'wrong-fb'}`;

  document.getElementById('quiz-next-btn').style.display = 'inline-flex';
}

function nextQuestion() {
  state.quiz.current++;
  if (state.quiz.current >= state.quiz.questions.length) {
    showQuizResult();
  } else {
    renderQuestion();
  }
}

async function showQuizResult() {
  const score = Math.round((state.quiz.score / state.quiz.questions.length) * 100);
  document.getElementById('quiz-content').style.display = 'none';
  const result = document.getElementById('quiz-result');
  result.classList.add('show');

  document.getElementById('score-circle').style.setProperty('--pct', score + '%');
  document.getElementById('score-num').textContent = score + '%';

  let msg = score >= 80 ? '🌟 Excellent Work!' : score >= 60 ? '👍 Good Job!' : '💪 Keep Practicing!';
  document.getElementById('score-msg').textContent = msg;

  const ptsEarned = state.quiz.score * 2;
  document.getElementById('score-pts').textContent = `+${ptsEarned} points earned • ${state.quiz.score}/${state.quiz.questions.length} correct`;

  try {
    const res = await phpPost('submit_quiz', { score, subject: 'Mixed' });
    state.student.points = res.total_points;
    state.student.level = res.level;
    state.student.quizzesTaken++;
    updateProgressUI();
  } catch(e) {
    state.student.points += ptsEarned;
    updateProgressUI();
  }
  showToast(`Quiz done! +${ptsEarned} pts earned 🎉`, 'success');
}

function restartQuiz() {
  document.getElementById('quiz-content').style.display = 'block';
  document.getElementById('quiz-result').classList.remove('show');
  initQuiz();
}

// ── AI CHAT ───────────────────────────────────────────────────────
const SYSTEM_PROMPT = `You are EduStar AI, a friendly, encouraging educational tutor for students in Africa (especially rural communities). Your goal is to:
- Explain concepts clearly using simple language, relatable African examples, and analogies
- Be warm, encouraging, and patient
- Use emojis occasionally to make learning fun
- Keep responses concise but complete (aim for 3-5 sentences unless more detail is needed)
- Reference real African contexts (animals, geography, history) when explaining concepts
- Always encourage the student and celebrate their curiosity
- Support subjects: Mathematics, Science, English, History, Geography, Computer Studies
You are talking with a student named: ${state.student.name}`;

let chatHistory = [];

async function sendMessage() {
  const input = document.getElementById('chat-input');
  const msg = input.value.trim();
  if (!msg || state.chatLoading) return;

  input.value = '';
  input.style.height = 'auto';
  appendMessage('user', msg);
  document.getElementById('chat-suggestions').style.display = 'none';

  chatHistory.push({ role: 'user', content: msg });
  await getAIResponse();
}

function sendSuggestion(el) {
  document.getElementById('chat-input').value = el.textContent;
  sendMessage();
}

async function getAIResponse() {
  state.chatLoading = true;
  document.getElementById('send-btn').disabled = true;

  // Show typing indicator
  const typingDiv = document.createElement('div');
  typingDiv.className = 'msg ai';
  typingDiv.id = 'typing-indicator';
  typingDiv.innerHTML = `<div class="msg-avatar ai">🤖</div><div class="msg-bubble"><div class="typing-indicator"><span></span><span></span><span></span></div></div>`;
  document.getElementById('chat-messages').appendChild(typingDiv);
  scrollChat();

  try {
    const response = await fetch('https://api.anthropic.com/v1/messages', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        model: 'claude-sonnet-4-20250514',
        max_tokens: 1000,
        system: SYSTEM_PROMPT,
        messages: chatHistory
      })
    });

    const data = await response.json();
    const reply = data.content?.map(c => c.text || '').join('') || "I'm sorry, I couldn't process that. Please try again!";

    chatHistory.push({ role: 'assistant', content: reply });

    document.getElementById('typing-indicator')?.remove();
    appendMessage('ai', reply);

  } catch (err) {
    document.getElementById('typing-indicator')?.remove();
    appendMessage('ai', "I'm having a bit of trouble connecting right now. Please check your internet connection and try again! 🌐");
  }

  state.chatLoading = false;
  document.getElementById('send-btn').disabled = false;
}

function appendMessage(role, text) {
  const msgs = document.getElementById('chat-messages');
  const div = document.createElement('div');
  div.className = `msg ${role}`;
  div.innerHTML = `
    <div class="msg-avatar ${role === 'ai' ? 'ai' : 'user-av'}">${role === 'ai' ? '🤖' : '👤'}</div>
    <div class="msg-bubble">${text.replace(/\n/g, '<br>')}</div>
  `;
  msgs.appendChild(div);
  scrollChat();
}

function scrollChat() {
  const msgs = document.getElementById('chat-messages');
  msgs.scrollTop = msgs.scrollHeight;
}

function handleChatKey(e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
}

function autoResize(el) {
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 100) + 'px';
}

// ── NAME MODAL ────────────────────────────────────────────────────
function openNameModal() {
  document.getElementById('name-modal').style.display = 'flex';
  document.getElementById('name-input').value = state.student.name !== 'Student' ? state.student.name : '';
  setTimeout(() => document.getElementById('name-input').focus(), 100);
}

async function saveName() {
  const name = document.getElementById('name-input').value.trim();
  if (!name) return;
  try {
    await phpPost('set_name', { name });
  } catch(e) {}
  state.student.name = name;
  document.getElementById('nav-name').textContent = name;
  document.getElementById('name-modal').style.display = 'none';
  showToast(`Welcome, ${name}! Let's learn together! 🌟`, 'success');
}

// ── PROGRESS UI ───────────────────────────────────────────────────
function updateProgressUI() {
  const { points, level, completed, quizzesTaken } = state.student;
  document.getElementById('nav-points').textContent = points;
  document.getElementById('nav-level').textContent = level;
  document.getElementById('prog-points').textContent = points;
  document.getElementById('prog-level').textContent = level;
  document.getElementById('prog-lessons').textContent = completed.length;
  document.getElementById('prog-quizzes').textContent = quizzesTaken;

  const xpPct = Math.min(100, ((points % 200) / 200) * 100);
  document.getElementById('xp-bar').style.width = xpPct + '%';
}

// ── UTILITIES ─────────────────────────────────────────────────────
function scrollToLearn() {
  document.getElementById('learn-section').scrollIntoView({ behavior: 'smooth' });
}

function scrollToChat() {
  document.getElementById('chat-section').scrollIntoView({ behavior: 'smooth' });
}

function filterSubjects() {
  showToast('🔍 Filter feature coming soon!', 'success');
}

async function phpPost(action, data = {}) {
  const form = new FormData();
  form.append('action', action);
  Object.entries(data).forEach(([k, v]) => form.append(k, v));

  const res = await fetch(window.location.href, { method: 'POST', body: form });
  return res.json();
}

function showToast(message, type = 'success') {
  const container = document.getElementById('toast-container');
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `<span class="toast-icon">${type === 'success' ? '✅' : '❌'}</span> ${message}`;
  container.appendChild(toast);
  setTimeout(() => toast.remove(), 4000);
}
</script>
</body>
</html>
