// ================================================================
// EDUSTAR — Smart Learning Assistant
// Script: app.js
// All student data is stored in localStorage (no server needed)
// ================================================================

// ── LOCAL STORAGE HELPERS ────────────────────────────────────────
function loadState() {
  try {
    const saved = localStorage.getItem('edustar_student');
    return saved ? JSON.parse(saved) : null;
  } catch (e) { return null; }
}

function saveState() {
  localStorage.setItem('edustar_student', JSON.stringify(student));
}

// ── STUDENT STATE ────────────────────────────────────────────────
let student = loadState() || {
  name: '',
  points: 0,
  level: 1,
  completed: [],
  quizzesTaken: 0
};

// ── SUBJECTS & LESSONS DATA ──────────────────────────────────────
const SUBJECTS = [
  {
    id: 'math', name: 'Mathematics', icon: '📐',
    desc: 'Numbers, algebra, geometry and more',
    badge: { text: 'Popular', cls: 'badge-orange' },
    lessons: [
      {
        id: 'math-1', title: 'Understanding Fractions',
        body: `<h3>What is a Fraction?</h3>
<p>A fraction represents a <strong>part of a whole</strong>. It is written as one number over another, like ½ or ¾.</p>
<div class="highlight-box">📌 The <strong>top number</strong> is called the <em>numerator</em> — how many parts you have.<br>The <strong>bottom number</strong> is the <em>denominator</em> — how many equal parts the whole is divided into.</div>
<h3>Real-Life Example</h3>
<p>If you cut a mango into 4 equal pieces and eat 1 piece, you have eaten <strong>1/4</strong> of the mango.</p>
<div class="example-box">🥭  1 piece eaten ÷ 4 total pieces = 1/4</div>
<h3>Adding Fractions</h3>
<p>When denominators are the same, just add the numerators:</p>
<div class="example-box">1/4 + 2/4 = 3/4</div>
<p>When denominators differ, find a common denominator first:</p>
<div class="example-box">1/2 + 1/3  →  3/6 + 2/6  =  5/6</div>`
      },
      {
        id: 'math-2', title: 'Introduction to Algebra',
        body: `<h3>What is Algebra?</h3>
<p>Algebra uses <strong>letters</strong> (called variables) to represent unknown numbers. We use it to solve problems where we don't know a value yet.</p>
<div class="highlight-box">💡 Think of a variable like an empty box: □ + 3 = 7. What goes in the box? Answer: 4!</div>
<h3>Solving an Equation</h3>
<div class="example-box">x + 5 = 12
x = 12 − 5
x = 7 ✓</div>
<p>Always do the <em>same operation on both sides</em> to keep the equation balanced — like a scale!</p>
<h3>Why Is Algebra Useful?</h3>
<p>Algebra helps engineers design bridges, programmers build apps, and scientists discover new things. It is the foundation of all advanced mathematics.</p>`
      },
      {
        id: 'math-3', title: 'Multiplication & Division',
        body: `<h3>Understanding Multiplication</h3>
<p>Multiplication is repeated addition. Instead of adding the same number many times, we multiply.</p>
<div class="example-box">4 × 3  means  4 + 4 + 4  =  12</div>
<div class="highlight-box">🌽 If you have 4 rows of maize plants with 3 plants in each row, you have 4 × 3 = 12 plants total.</div>
<h3>Division</h3>
<p>Division is splitting into equal groups — the opposite of multiplication.</p>
<div class="example-box">12 ÷ 4 = 3
(12 plants split into 4 equal rows = 3 per row)</div>
<h3>Quick Tips</h3>
<p>Any number multiplied by 0 is 0. Any number multiplied by 1 stays the same. Division by 0 is impossible!</p>`
      }
    ]
  },
  {
    id: 'science', name: 'Science', icon: '🔬',
    desc: 'Biology, chemistry, physics, and nature',
    badge: { text: 'Recommended', cls: 'badge-green' },
    lessons: [
      {
        id: 'sci-1', title: 'Photosynthesis — How Plants Make Food',
        body: `<h3>What is Photosynthesis?</h3>
<p>Photosynthesis is how plants make their own food using sunlight, water, and carbon dioxide. Plants are the only living things that can do this — making them the foundation of all life on Earth!</p>
<div class="highlight-box">🌱 Plants are called <em>producers</em> because they produce their own food. Animals are <em>consumers</em> because they eat other organisms.</div>
<h3>The Formula</h3>
<div class="example-box">Sunlight + Water (H₂O) + Carbon Dioxide (CO₂)
          ↓
Glucose (Sugar) + Oxygen (O₂)</div>
<h3>Where Does It Happen?</h3>
<p>Photosynthesis happens inside <strong>chloroplasts</strong> — tiny structures in plant cells containing <em>chlorophyll</em>, which is what makes plants green and captures sunlight energy.</p>`
      },
      {
        id: 'sci-2', title: 'The Water Cycle',
        body: `<h3>What is the Water Cycle?</h3>
<p>Water on Earth is always moving in a continuous cycle. The same water has existed for billions of years — it just keeps changing form!</p>
<div class="highlight-box">
☀️ <strong>Evaporation:</strong> Heat turns water into vapour that rises.<br><br>
☁️ <strong>Condensation:</strong> Vapour cools and forms clouds.<br><br>
🌧️ <strong>Precipitation:</strong> Water falls as rain, snow, or hail.<br><br>
🌊 <strong>Collection:</strong> Water gathers in rivers, lakes, and oceans.
</div>
<h3>Why It Matters for Africa</h3>
<p>Understanding the water cycle helps farmers know when to plant. Deforestation disrupts the cycle — trees help bring rain by releasing water vapour into the air.</p>`
      }
    ]
  },
  {
    id: 'english', name: 'English', icon: '📖',
    desc: 'Reading, writing, grammar and vocabulary',
    badge: { text: 'Essential', cls: 'badge-gold' },
    lessons: [
      {
        id: 'eng-1', title: 'Parts of Speech',
        body: `<h3>What are Parts of Speech?</h3>
<p>Every word in a sentence plays a specific role called its <em>part of speech</em>. Knowing these helps you write clearly and correctly.</p>
<div class="highlight-box">
🔵 <strong>Noun</strong> — A person, place, or thing. (girl, Nairobi, book)<br><br>
🟢 <strong>Verb</strong> — An action or state. (run, is, think, study)<br><br>
🟡 <strong>Adjective</strong> — Describes a noun. (tall, clever, beautiful)<br><br>
🔴 <strong>Adverb</strong> — Describes a verb or adjective. (quickly, very, always)<br><br>
🟣 <strong>Pronoun</strong> — Replaces a noun. (he, she, it, they, we)
</div>
<h3>Example Sentence</h3>
<div class="example-box">"The clever [adj] girl [noun] quickly [adv] solved [verb] the puzzle."</div>
<p>Try identifying the parts of speech in sentences you read every day!</p>`
      },
      {
        id: 'eng-2', title: 'How to Write a Good Paragraph',
        body: `<h3>The Three Parts of a Paragraph</h3>
<p>A good paragraph always has three parts working together.</p>
<div class="highlight-box">
1️⃣ <strong>Topic Sentence</strong> — States the main idea. This is the first sentence.<br><br>
2️⃣ <strong>Supporting Sentences</strong> — Give details, examples, or reasons.<br><br>
3️⃣ <strong>Conclusion Sentence</strong> — Wraps up or links to the next idea.
</div>
<h3>Example Paragraph</h3>
<div class="example-box">Education is the most powerful tool we have. [Topic]
It opens doors to better jobs, health, and freedom. [Support]
When students learn, entire communities grow stronger. [Conclusion]</div>
<p>Practice writing one paragraph every day about something that interests you — football, cooking, animals, or your community!</p>`
      }
    ]
  },
  {
    id: 'history', name: 'History', icon: '🏛️',
    desc: 'African history, world events and culture',
    badge: { text: 'New', cls: 'badge-green' },
    lessons: [
      {
        id: 'hist-1', title: 'African Independence Movements',
        body: `<h3>The Fight for Freedom</h3>
<p>In the 20th century, African nations fought hard to win independence from colonial powers who had controlled the continent since the 1800s.</p>
<div class="highlight-box">
🇬🇭 <strong>Ghana (1957)</strong> — First sub-Saharan African country to gain independence, led by Kwame Nkrumah.<br><br>
🇿🇦 <strong>South Africa (1994)</strong> — Nelson Mandela became the first democratically elected president, ending apartheid.<br><br>
🇿🇲 <strong>Zambia (1964)</strong> — Kenneth Kaunda led the nation to independence from British rule.
</div>
<div class="example-box">"Africa must unite." — Kwame Nkrumah</div>
<p>These leaders showed the world that justice and dignity cannot be denied forever. Their courage changed history.</p>`
      }
    ]
  },
  {
    id: 'geography', name: 'Geography', icon: '🌍',
    desc: 'Maps, continents, climate, and landforms',
    badge: { text: 'Popular', cls: 'badge-orange' },
    lessons: [
      {
        id: 'geo-1', title: "Africa's Major Landforms",
        body: `<h3>The Geography of Africa</h3>
<p>Africa is the <strong>second largest continent</strong> in the world with an incredible variety of landscapes.</p>
<div class="highlight-box">
🏜️ <strong>Sahara Desert</strong> — The world's largest hot desert, covering most of North Africa.<br><br>
🌿 <strong>Congo Rainforest</strong> — Second largest rainforest on Earth, home to gorillas and thousands of species.<br><br>
🏔️ <strong>Mount Kilimanjaro</strong> — Africa's highest peak at 5,895m, located in Tanzania.<br><br>
🌊 <strong>Nile River</strong> — The world's longest river, flowing through 11 countries for 6,650 km.
</div>
<p>Africa's geography shapes its weather, farming patterns, and where people live. Protecting these natural wonders is everyone's responsibility.</p>`
      }
    ]
  },
  {
    id: 'computer', name: 'Computer Studies', icon: '💻',
    desc: 'ICT, coding basics, and digital skills',
    badge: { text: 'Future Skills', cls: 'badge-green' },
    lessons: [
      {
        id: 'comp-1', title: 'Introduction to Coding',
        body: `<h3>What is Coding?</h3>
<p>Coding (programming) means giving step-by-step instructions to a computer. Computers can only follow very exact instructions written in special programming languages.</p>
<div class="highlight-box">💡 Just like you give instructions in English or Swahili to a person, you write instructions in Python, JavaScript, or other languages for a computer!</div>
<h3>Your First Code</h3>
<div class="example-box"># Python — tell the computer to say hello
print("Hello Africa! You are amazing! 🌍")</div>
<h3>Why Learn to Code?</h3>
<p>Coding is one of the most valuable skills of the 21st century. It lets you build apps, solve problems, create businesses, and shape Africa's technological future. Anyone can learn — including you!</p>`
      },
      {
        id: 'comp-2', title: 'What is the Internet?',
        body: `<h3>How the Internet Works</h3>
<p>The internet is a massive global network of computers all connected together, sharing information.</p>
<div class="highlight-box">
🖥️ <strong>Servers</strong> — Powerful computers that store websites and data.<br><br>
📡 <strong>Network</strong> — Cables, satellites, and wireless signals connecting everything.<br><br>
🌐 <strong>Browser</strong> — Software like Chrome that lets you visit websites.<br><br>
🔗 <strong>URL</strong> — The address of a website (e.g., www.google.com).
</div>
<h3>Internet Safety Tips</h3>
<p>Never share your passwords. Be careful about what personal information you post online. If something online makes you feel unsafe, tell a trusted adult. The internet is a powerful tool — use it wisely!</p>`
      }
    ]
  }
];

// ── QUIZ QUESTIONS ────────────────────────────────────────────────
const ALL_QUESTIONS = [
  { q: "What is the top number of a fraction called?",                opts: ["Denominator","Numerator","Divisor","Multiplier"],            ans: 1, sub: "Math" },
  { q: "What gas do plants release during photosynthesis?",           opts: ["Carbon Dioxide","Nitrogen","Oxygen","Hydrogen"],             ans: 2, sub: "Science" },
  { q: "Which was the first sub-Saharan country to gain independence?", opts: ["Nigeria","Kenya","Ghana","South Africa"],                 ans: 2, sub: "History" },
  { q: "What is the world's longest river?",                          opts: ["Amazon","Congo","Niger","Nile"],                             ans: 3, sub: "Geography" },
  { q: "In the equation x + 5 = 12, what is x?",                     opts: ["5","6","7","17"],                                            ans: 2, sub: "Math" },
  { q: "A word that describes a noun is called an:",                  opts: ["Adverb","Verb","Adjective","Pronoun"],                        ans: 2, sub: "English" },
  { q: "Africa's highest mountain is:",                               opts: ["Mount Kenya","Mount Kilimanjaro","Mount Elgon","Atlas Mountains"], ans: 1, sub: "Geography" },
  { q: "What do plants need for photosynthesis?",                     opts: ["Water only","Sunlight only","Sunlight, water and CO₂","Soil and water"], ans: 2, sub: "Science" },
  { q: "What do you call letters used for unknown numbers in maths?", opts: ["Constants","Variables","Decimals","Fractions"],              ans: 1, sub: "Math" },
  { q: "What does a browser do?",                                     opts: ["Stores files","Connects cables","Lets you visit websites","Charges your phone"], ans: 2, sub: "Computer" },
  { q: "Which step of the water cycle involves water falling as rain?", opts: ["Evaporation","Condensation","Precipitation","Collection"], ans: 2, sub: "Science" },
  { q: "Nelson Mandela was the first democratic president of which country?", opts: ["Zimbabwe","Zambia","South Africa","Ghana"],          ans: 2, sub: "History" },
];

// ── QUIZ STATE ────────────────────────────────────────────────────
let quiz = { questions: [], current: 0, score: 0, answered: false };

// ── LESSON STATE ──────────────────────────────────────────────────
let currentSubject  = null;
let currentLessonIdx = 0;

// ── CHAT STATE ────────────────────────────────────────────────────
let chatHistory = [];
let chatBusy    = false;

const SYSTEM_PROMPT = `You are EduStar AI, a warm and encouraging educational tutor for students across Africa, especially those in rural and underserved communities. Your role:
- Explain concepts simply using relatable African examples (animals, geography, food, daily life)
- Be patient, kind, and encouraging — always celebrate curiosity
- Keep answers clear and concise (3–6 sentences unless more is needed)
- Use an occasional emoji to make learning feel friendly and fun
- Support: Mathematics, Science, English, History, Geography, Computer Studies
- When you don't know something, say so honestly and encourage the student to explore further`;

// ================================================================
// INIT
// ================================================================
window.addEventListener('DOMContentLoaded', () => {
  if (!student.name) {
    document.getElementById('name-overlay').style.display = 'flex';
    setTimeout(() => document.getElementById('name-input').focus(), 300);
  } else {
    document.getElementById('name-overlay').style.display = 'none';
  }

  updateUI();
  renderSubjects();
  initQuiz();
  animateCounters();
});

// ================================================================
// COUNTER ANIMATION
// ================================================================
function animateCounters() {
  document.querySelectorAll('[data-count]').forEach(el => {
    const target = parseInt(el.dataset.count);
    const suffix = target >= 1000 ? '+' : '';
    let cur = 0;
    const step = target / 50;
    const iv = setInterval(() => {
      cur = Math.min(cur + step, target);
      el.textContent = Math.floor(cur) + suffix;
      if (cur >= target) clearInterval(iv);
    }, 28);
  });
}

// ================================================================
// UI UPDATE
// ================================================================
function updateUI() {
  const { points, level, completed, quizzesTaken, name } = student;

  document.getElementById('nav-points').textContent  = points;
  document.getElementById('nav-level').textContent   = level;
  document.getElementById('nav-name').textContent    = name || 'Student';
  document.getElementById('prog-points').textContent = points;
  document.getElementById('prog-level').textContent  = level;
  document.getElementById('prog-lessons').textContent  = completed.length;
  document.getElementById('prog-quizzes').textContent  = quizzesTaken;

  const xpInLevel = points % 200;
  const xpPct     = (xpInLevel / 200) * 100;

  document.getElementById('xp-bar').style.width           = xpPct + '%';
  document.getElementById('xp-level-label').textContent   = `Level ${level}`;
  document.getElementById('xp-progress-label').textContent = `${xpInLevel} / 200 XP to next level`;
}

// ================================================================
// NAME MODAL
// ================================================================
function openNameModal() {
  document.getElementById('name-overlay').style.display = 'flex';
  document.getElementById('name-input').value = student.name || '';
  setTimeout(() => document.getElementById('name-input').focus(), 100);
}

function saveName() {
  const name = document.getElementById('name-input').value.trim();
  if (!name) return;
  student.name = name;
  saveState();
  document.getElementById('name-overlay').style.display = 'none';
  updateUI();
  toast(`Welcome, ${name}! Let's start learning! 🌟`);
}

// Allow pressing Enter in the name input
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('name-input').addEventListener('keydown', e => {
    if (e.key === 'Enter') saveName();
  });
});

// ================================================================
// SUBJECTS
// ================================================================
function renderSubjects() {
  const grid = document.getElementById('subjects-grid');
  grid.innerHTML = SUBJECTS.map(sub => {
    const done   = sub.lessons.filter(l => student.completed.includes(l.id)).length;
    const allDone = done === sub.lessons.length;
    return `
      <div class="subject-card" onclick="openSubject('${sub.id}')">
        <span class="subject-icon">${sub.icon}</span>
        <div class="subject-name">${sub.name}</div>
        <div class="subject-desc">${sub.desc}</div>
        <div class="subject-meta">
          <span class="lesson-count">${done}/${sub.lessons.length} lessons${allDone ? ' ✅' : ''}</span>
          <span class="subject-badge ${sub.badge.cls}">${sub.badge.text}</span>
        </div>
      </div>`;
  }).join('');
}

function openSubject(id) {
  currentSubject   = SUBJECTS.find(s => s.id === id);
  if (!currentSubject) return;
  currentLessonIdx = 0;
  showLesson();
}

function showLesson() {
  const lesson = currentSubject.lessons[currentLessonIdx];

  document.getElementById('l-tag').textContent   = currentSubject.icon + ' ' + currentSubject.name;
  document.getElementById('l-title').textContent = lesson.title;
  document.getElementById('l-body').innerHTML    = lesson.body;

  // Lesson tab navigation (only when subject has multiple lessons)
  const nav = document.getElementById('l-nav');
  if (currentSubject.lessons.length > 1) {
    nav.innerHTML = currentSubject.lessons.map((l, i) =>
      `<button class="lesson-nav-btn ${i === currentLessonIdx ? 'active' : ''}" onclick="switchLesson(${i})">${i + 1}. ${l.title}</button>`
    ).join('');
  } else {
    nav.innerHTML = '';
  }

  document.getElementById('lesson-panel').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function switchLesson(idx) {
  currentLessonIdx = idx;
  showLesson();
}

function closeLesson() {
  document.getElementById('lesson-panel').classList.remove('open');
  document.body.style.overflow = '';
}

function completeLesson() {
  const lesson = currentSubject.lessons[currentLessonIdx];
  if (student.completed.includes(lesson.id)) {
    toast('✅ Already completed! Try another lesson.');
    closeLesson();
    return;
  }
  student.completed.push(lesson.id);
  student.points += 50;
  student.level   = Math.max(1, Math.floor(student.points / 200) + 1);
  saveState();
  updateUI();
  renderSubjects();
  toast('🎉 Lesson complete! +50 points earned!');
  closeLesson();
}

// ================================================================
// QUIZ
// ================================================================
function initQuiz() {
  const shuffled = [...ALL_QUESTIONS].sort(() => Math.random() - 0.5).slice(0, 5);
  quiz = { questions: shuffled, current: 0, score: 0, answered: false };
  document.getElementById('quiz-content').style.display = 'block';
  document.getElementById('quiz-result').classList.remove('show');
  renderQuestion();
}

function renderQuestion() {
  const q     = quiz.questions[quiz.current];
  const total = quiz.questions.length;

  document.getElementById('quiz-meta').textContent = `Question ${quiz.current + 1} of ${total} • ${q.sub}`;
  document.getElementById('quiz-q').textContent    = q.q;
  document.getElementById('qpf').style.width       = `${(quiz.current / total) * 100}%`;

  const fb = document.getElementById('quiz-fb');
  fb.classList.remove('show', 'correct-fb', 'wrong-fb');
  document.getElementById('quiz-next').style.display = 'none';
  quiz.answered = false;

  document.getElementById('quiz-opts').innerHTML = q.opts.map((opt, i) =>
    `<button class="quiz-option" onclick="answer(${i})">${opt}</button>`
  ).join('');
}

function answer(idx) {
  if (quiz.answered) return;
  quiz.answered = true;

  const q    = quiz.questions[quiz.current];
  const opts = document.querySelectorAll('.quiz-option');
  opts.forEach(b => b.onclick = null);
  opts[q.ans].classList.add('correct');
  if (idx !== q.ans) opts[idx].classList.add('wrong');

  const ok = (idx === q.ans);
  if (ok) quiz.score++;

  const fb = document.getElementById('quiz-fb');
  fb.textContent  = ok
    ? `✅ Correct! ${q.opts[q.ans]} is right. Well done!`
    : `❌ The correct answer is: ${q.opts[q.ans]}. Keep going!`;
  fb.className = `quiz-feedback show ${ok ? 'correct-fb' : 'wrong-fb'}`;
  document.getElementById('quiz-next').style.display = 'inline-flex';
}

function nextQuestion() {
  quiz.current++;
  if (quiz.current >= quiz.questions.length) finishQuiz();
  else renderQuestion();
}

function finishQuiz() {
  const pct = Math.round((quiz.score / quiz.questions.length) * 100);
  const deg = (pct / 100) * 360;

  document.getElementById('quiz-content').style.display = 'none';
  document.getElementById('quiz-result').classList.add('show');
  document.getElementById('score-circle').style.setProperty('--deg', deg + 'deg');
  document.getElementById('score-num').textContent = pct + '%';
  document.getElementById('score-msg').textContent =
    pct >= 80 ? '🌟 Excellent Work!' : pct >= 60 ? '👍 Good Job!' : '💪 Keep Practicing!';

  const pts = quiz.score * 2;
  document.getElementById('score-pts').textContent = `+${pts} points • ${quiz.score}/${quiz.questions.length} correct`;

  student.points += pts;
  student.level   = Math.max(1, Math.floor(student.points / 200) + 1);
  student.quizzesTaken++;
  saveState();
  updateUI();
  toast(`Quiz complete! +${pts} points earned 🎉`);
}

function restartQuiz() { initQuiz(); }

// ================================================================
// AI CHAT
// ================================================================
function useSuggestion(el) {
  document.getElementById('chat-input').value = el.textContent;
  sendMessage();
}

async function sendMessage() {
  const input = document.getElementById('chat-input');
  const msg   = input.value.trim();
  if (!msg || chatBusy) return;

  input.value        = '';
  input.style.height = 'auto';
  addMsg('user', msg);
  chatHistory.push({ role: 'user', content: msg });
  document.querySelector('.chat-suggestions').style.display = 'none';

  await getReply();
}

async function getReply() {
  chatBusy = true;
  document.getElementById('send-btn').disabled = true;

  // Show typing indicator
  const typingDiv       = document.createElement('div');
  typingDiv.className   = 'msg ai';
  typingDiv.id          = 'typing';
  typingDiv.innerHTML   = `
    <div class="msg-avatar ai">🤖</div>
    <div class="msg-bubble">
      <div class="typing-indicator">
        <span></span><span></span><span></span>
      </div>
    </div>`;
  document.getElementById('chat-messages').appendChild(typingDiv);
  scrollChat();

  try {
    const res = await fetch('https://api.anthropic.com/v1/messages', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        model: 'claude-sonnet-4-20250514',
        max_tokens: 1000,
        system: SYSTEM_PROMPT + `\n\nThe student's name is: ${student.name || 'Student'}`,
        messages: chatHistory
      })
    });

    const data  = await res.json();
    const reply = data.content?.map(c => c.text || '').join('') || "I couldn't process that — please try again!";
    chatHistory.push({ role: 'assistant', content: reply });
    document.getElementById('typing')?.remove();
    addMsg('ai', reply);

  } catch (e) {
    document.getElementById('typing')?.remove();
    addMsg('ai', "I'm having trouble connecting right now. Please check your internet and try again! 🌐");
  }

  chatBusy = false;
  document.getElementById('send-btn').disabled = false;
}

function addMsg(role, text) {
  const wrap       = document.createElement('div');
  wrap.className   = `msg ${role}`;
  wrap.innerHTML   = `
    <div class="msg-avatar ${role === 'ai' ? 'ai' : 'user-av'}">${role === 'ai' ? '🤖' : '👤'}</div>
    <div class="msg-bubble">${text.replace(/\n/g, '<br>')}</div>`;
  document.getElementById('chat-messages').appendChild(wrap);
  scrollChat();
}

function scrollChat() {
  const el    = document.getElementById('chat-messages');
  el.scrollTop = el.scrollHeight;
}

// Auto-resize chat textarea
document.addEventListener('DOMContentLoaded', () => {
  const chatInput = document.getElementById('chat-input');

  chatInput.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  });

  chatInput.addEventListener('input', () => {
    chatInput.style.height = 'auto';
    chatInput.style.height = Math.min(chatInput.scrollHeight, 100) + 'px';
  });
});

// ================================================================
// TOAST NOTIFICATIONS
// ================================================================
function toast(msg) {
  const container = document.getElementById('toasts');
  const el        = document.createElement('div');
  el.className    = 'toast success';
  el.innerHTML    = `✅ ${msg}`;
  container.appendChild(el);
  setTimeout(() => el.remove(), 4000);
}
