<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<pre style='font-family:monospace;font-size:13px;padding:20px'>";

$dashboardContent = '<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EduStar – Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/styles/normalize.css">
  <link rel="stylesheet" href="/styles/shared.css">
</head>
<body>
<div class="app">
  <div id="nav-container"></div>

  <!-- HERO -->
  <section class="page-hero" id="hero-section">
    <div class="hero-tag" id="hero-tag">🌍 Loading...</div>
    <h1 id="hero-title">Welcome back!<br><em id="hero-name">Student</em></h1>
    <p id="hero-desc">Your personalised learning dashboard. Continue where you left off.</p>
    <div class="hero-cta" style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;margin-top:1.5rem">
      <a href="/subjects.html" class="btn btn-primary">📚 Browse Subjects</a>
      <a href="/quiz.html" class="btn btn-secondary">🧠 Take a Quiz</a>
      <a href="/books.html" class="btn btn-teal">📖 School Books</a>
    </div>
  </section>

  <!-- STATS BAR -->
  <div class="stats-bar">
    <div class="stat-item"><div class="stat-num" id="stat-points">0</div><div class="stat-label">Total Points</div></div>
    <div class="stat-item"><div class="stat-num" id="stat-level">1</div><div class="stat-label">Level</div></div>
    <div class="stat-item"><div class="stat-num" id="stat-lessons">0</div><div class="stat-label">Lessons Done</div></div>
    <div class="stat-item"><div class="stat-num" id="stat-quizzes">0</div><div class="stat-label">Quizzes Taken</div></div>
    <div class="stat-item"><div class="stat-num">20+</div><div class="stat-label">Subjects Available</div></div>
  </div>

  <div class="page-section">
    <!-- Progress Card -->
    <div class="progress-card">
      <h3 style="font-size:0.77rem;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:1rem">📊 Your Learning Progress</h3>
      <div class="progress-stats">
        <div class="progress-stat"><div class="num" id="prog-points">0</div><div class="lbl">Total Points</div></div>
        <div class="progress-stat"><div class="num" id="prog-level">1</div><div class="lbl">Level</div></div>
        <div class="progress-stat"><div class="num" id="prog-lessons">0</div><div class="lbl">Lessons Done</div></div>
        <div class="progress-stat"><div class="num" id="prog-quizzes">0</div><div class="lbl">Quizzes Taken</div></div>
      </div>
      <div class="xp-bar-wrap"><div class="xp-bar" id="xp-bar"></div></div>
      <div class="xp-label">
        <span id="xp-level-label">Level 1</span>
        <span id="xp-progress-label">0 / 200 XP to next level</span>
      </div>
    </div>

    <!-- Quick Subjects -->
    <div class="section-header">
      <div class="section-title">Popular <span>Subjects</span></div>
      <a href="/subjects.html" class="btn btn-sm btn-secondary">View All →</a>
    </div>
    <div class="subjects-grid" id="subjects-grid"></div>

    <!-- AI TUTOR CHAT -->
    <div class="section-header" id="chat-anchor">
      <div class="section-title">AI <span>Tutor Chat</span></div>
      <span style="font-size:0.78rem;color:var(--muted)">Ask anything about your studies</span>
    </div>
    <div class="chat-section">
      <div class="chat-header">
        <div class="ai-avatar">🤖</div>
        <div class="chat-header-info">
          <h3>EduStar AI Tutor</h3>
          <p><span class="online-dot"></span> Always here to help</p>
        </div>
        <div style="margin-left:auto;font-size:0.75rem;color:var(--muted)" id="chat-grade-info"></div>
      </div>
      <div class="chat-messages" id="chat-messages">
        <div class="msg ai">
          <div class="msg-avatar ai">🤖</div>
          <div class="msg-bubble" id="welcome-msg">👋 Loading your personalised tutor...</div>
        </div>
      </div>
      <div class="chat-suggestions" id="chat-suggestions"></div>
      <div class="chat-input-area">
        <textarea class="chat-input" id="chat-input" placeholder="Ask your question here..." rows="1"></textarea>
        <button class="send-btn" id="send-btn" onclick="sendMessage()">➤</button>
      </div>
    </div>

  </div>

  <footer>
    <p>Built with ❤️ for students across Africa</p>
    <p><strong>EduStar AI</strong> — Smart Learning for Everyone, Everywhere</p>
    <p style="margin-top:0.3rem;font-size:0.73rem;opacity:0.5">Free • Accessible • Personalised</p>
  </footer>
</div>

<div class="toast-container" id="toasts"></div>

<script src="/scripts/data.js"></script>
<script src="/scripts/api.js"></script>
<script>
let student = null;
let chatHistory = [];
let chatBusy = false;
const QUICK_SUBJECTS = [\'math\',\'english\',\'science\',\'history\',\'geography\',\'biology\',\'chemistry\',\'physics\',\'economics\',\'accounting\',\'computer\',\'literature\'];

function init() {
  // Get student - redirect to login if not found
  student = requireAuth();
  if (!student) { window.location.href = \'/index.html\'; return; }

  // Nav
  document.getElementById(\'nav-container\').innerHTML = buildNav(student, \'dashboard\');
  // Hero
  const flag = COUNTRY_FLAGS[student.country] || \'🌍\';
  const country = COUNTRY_NAMES[student.country] || \'Africa\';
  document.getElementById(\'hero-tag\').textContent = `${flag} ${country} · ${student.grade}`;
  document.getElementById(\'hero-name\').textContent = student.name;
  document.getElementById(\'hero-desc\').textContent = `Welcome to your ${country} curriculum learning dashboard. You\'re on ${student.grade}.`;
  // Stats
  updateStats();
  // Subjects grid
  renderSubjects();
  // Chat welcome
  const curriculum = COUNTRY_CURRICULUM[student.country] || {};
  document.getElementById(\'chat-grade-info\').textContent = student.grade + \' · \' + (curriculum.system || \'\');
  document.getElementById(\'welcome-msg\').textContent =
    `👋 Hujambo, ${student.name}! I\'m your personal AI tutor. I know the ${COUNTRY_NAMES[student.country] || \'African\'} curriculum for ${student.grade} inside out. What are you studying today?`;
  // Suggestions
  renderSuggestions();
  // Chat input
  const ci = document.getElementById(\'chat-input\');
  ci.addEventListener(\'keydown\', e => { if (e.key===\'Enter\'&&!e.shiftKey){e.preventDefault();sendMessage();} });
  ci.addEventListener(\'input\', () => { ci.style.height=\'auto\'; ci.style.height=Math.min(ci.scrollHeight,100)+\'px\'; });
}

function updateStats() {
  [\'points\',\'level\',\'lessons\',\'quizzes\'].forEach(k => {
    const v = k === \'lessons\' ? (student.completed||[]).length : k === \'quizzes\' ? (student.quizzesTaken||0) : student[k]||0;
    const el1 = document.getElementById(\'stat-\'+k);
    const el2 = document.getElementById(\'prog-\'+k);
    if (el1) el1.textContent = v;
    if (el2) el2.textContent = v;
  });
  document.getElementById(\'nav-points\').textContent = student.points || 0;
  document.getElementById(\'nav-level\').textContent = student.level || 1;
  // XP bar
  const xpPerLevel = 200;
  const xpInLevel = (student.points||0) % xpPerLevel;
  const xpPct = (xpInLevel / xpPerLevel) * 100;
  document.getElementById(\'xp-bar\').style.width = xpPct + \'%\';
  document.getElementById(\'xp-level-label\').textContent = \'Level \' + (student.level||1);
  document.getElementById(\'xp-progress-label\').textContent = xpInLevel + \' / \' + xpPerLevel + \' XP to next level\';
}

function renderSubjects() {
  const grid = document.getElementById(\'subjects-grid\');
  const displayed = ALL_SUBJECTS.filter(s => QUICK_SUBJECTS.includes(s.id)).slice(0, 12);
  grid.innerHTML = displayed.map(s => `
    <a href="/lesson.html?subject=${s.id}" class="subject-card">
      <div class="subject-icon">${s.icon}</div>
      <div class="subject-name">${s.name}</div>
      <div class="subject-desc">${s.desc}</div>
      <div class="subject-badge ${s.badge.cls}">${s.badge.text}</div>
    </a>`).join(\'\');
}

function renderSuggestions() {
  const country = COUNTRY_NAMES[student.country] || \'Africa\';
  const suggestions = [
    `Help me with ${student.grade} Mathematics`,
    `Explain photosynthesis`,
    `What are the key topics for my grade?`,
    `Tell me about African history`,
    `How do I write a good essay?`,
    `${country} geography questions`,
  ];
  document.getElementById(\'chat-suggestions\').innerHTML = suggestions.map(s =>
    `<div class="suggestion-chip" onclick="useSuggestion(this)">${s}</div>`
  ).join(\'\');
}

function useSuggestion(el) {
  document.getElementById(\'chat-input\').value = el.textContent;
  sendMessage();
}

async function sendMessage() {
  const input = document.getElementById(\'chat-input\');
  const msg = input.value.trim();
  if (!msg || chatBusy) return;
  input.value = \'\'; input.style.height = \'auto\';
  addMsg(\'user\', msg);
  chatHistory.push({ role:\'user\', content:msg });
  document.getElementById(\'chat-suggestions\').style.display = \'none\';
  await getReply();
}

async function getReply() {
  chatBusy = true;
  document.getElementById(\'send-btn\').disabled = true;
  const typing = document.createElement(\'div\');
  typing.className = \'msg ai\'; typing.id = \'typing\';
  typing.innerHTML = `<div class="msg-avatar ai">🤖</div><div class="msg-bubble"><div class="typing-indicator"><span></span><span></span><span></span></div></div>`;
  document.getElementById(\'chat-messages\').appendChild(typing);
  scrollChat();

  // Primary: Pollinations.AI OpenAI-compatible endpoint (free, no key)
  const tryPollinations = async () => {
    const res = await fetch(\'https://text.pollinations.ai/openai\', {
      method: \'POST\',
      headers: { \'Content-Type\': \'application/json\' },
      body: JSON.stringify({
        model: \'openai\',
        messages: [{ role: \'system\', content: buildSystemPrompt(student) }, ...chatHistory],
        seed: Math.floor(Math.random()*9999),
        private: true
      })
    });
    if (!res.ok) throw new Error(\'status \' + res.status);
    const data = await res.json();
    const text = data.choices?.[0]?.message?.content;
    if (!text) throw new Error(\'empty response\');
    return text;
  };

  // Fallback: Pollinations simple GET endpoint
  const tryPollinationsGet = async () => {
    const sys = buildSystemPrompt(student).slice(0, 300);
    const lastMsg = chatHistory[chatHistory.length - 1]?.content || \'\';
    const prompt = encodeURIComponent(sys + \'\\n\\nStudent: \' + lastMsg + \'\\n\\nAssistant:\');
    const res = await fetch(\'https://text.pollinations.ai/\' + prompt);
    if (!res.ok) throw new Error(\'status \' + res.status);
    const text = await res.text();
    if (!text || text.length < 3) throw new Error(\'empty\');
    return text;
  };

  try {
    let reply;
    try {
      reply = await tryPollinations();
    } catch(e1) {
      console.warn(\'Primary AI failed, trying fallback:\', e1.message);
      reply = await tryPollinationsGet();
    }
    chatHistory.push({ role: \'assistant\', content: reply });
    document.getElementById(\'typing\')?.remove();
    addMsg(\'ai\', reply);
    if (chatHistory.filter(m => m.role === \'user\').length % 5 === 0) {
      student.points = (student.points || 0) + 5;
      saveStudent(student);
      toast(\'💬 +5 pts for engaging with your AI tutor!\');
      updateStats();
    }
  } catch (e) {
    document.getElementById(\'typing\')?.remove();
    addMsg(\'ai\', \'⚠️ The AI tutor is temporarily unavailable (free service). Please try again in a moment!\');
  }
  chatBusy = false;
  document.getElementById(\'send-btn\').disabled = false;
}

function addMsg(role, text) {
  const wrap = document.createElement(\'div\');
  wrap.className = `msg ${role}`;
  wrap.innerHTML = `<div class="msg-avatar ${role===\'ai\'?\'ai\':\'user-av\'}">${role===\'ai\'?\'🤖\':\'👤\'}</div><div class="msg-bubble">${text.replace(/\\n/g,\'<br>\')}</div>`;
  document.getElementById(\'chat-messages\').appendChild(wrap);
  scrollChat();
}

function scrollChat() {
  const el = document.getElementById(\'chat-messages\');
  el.scrollTop = el.scrollHeight;
}

window.addEventListener(\'load\', init);
</script>
</body>
</html>
';

$path = __DIR__ . '/dashboard.html';
@unlink($path);
$r = file_put_contents($path, $dashboardContent);
echo "dashboard.html: " . ($r !== false ? "✅ Written ($r bytes)" : "❌ FAILED") . "\n";

// Verify it no longer calls anthropic
$check = file_get_contents($path);
echo "Still calls anthropic.com: " . (strpos($check, 'api.anthropic.com') !== false ? "❌ YES - BAD" : "✅ NO - GOOD") . "\n";
echo "Calls pollinations.ai: " . (strpos($check, 'pollinations.ai') !== false ? "✅ YES - GOOD" : "❌ NO - BAD") . "\n";
echo "\n✅ Done! Delete install4.php then hard-refresh dashboard (Ctrl+Shift+R)\n";
echo "</pre>";
echo "<p style='color:red;font-weight:bold;font-family:monospace;padding:20px'>⚠️ DELETE install4.php immediately!</p>";
