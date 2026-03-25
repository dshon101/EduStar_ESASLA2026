// ================================================================
// EduStar — Floating AI Tutor Chat Widget
// Drop <script src="/scripts/chat-widget.js"></script> on any page
// Requires data.js to be loaded first
// ================================================================
(function () {
  'use strict';

  // Only show for logged-in users
  const student = (function () {
    try { return JSON.parse(localStorage.getItem('edustar_current') || 'null'); } catch { return null; }
  })();
  if (!student) return;

  let chatHistory = [];
  let chatBusy    = false;
  let isOpen      = false;
  let hasGreeted  = false;

  // ── Inject styles ─────────────────────────────────────────────
  const style = document.createElement('style');
  style.textContent = `
    /* Floating button */
    #ai-fab {
      position:fixed; bottom:1.5rem; right:1.5rem; z-index:9000;
      width:56px; height:56px; border-radius:50%;
      background:linear-gradient(135deg,#FF6B2B,#F5A623);
      border:none; cursor:pointer; box-shadow:0 4px 20px rgba(255,107,43,0.45);
      display:flex; align-items:center; justify-content:center;
      font-size:1.5rem; transition:transform 0.2s, box-shadow 0.2s;
      color:white;
    }
    #ai-fab:hover { transform:scale(1.1); box-shadow:0 6px 28px rgba(255,107,43,0.6); }
    #ai-fab .fab-badge {
      position:absolute; top:-2px; right:-2px;
      width:16px; height:16px; border-radius:50%;
      background:#00C9A7; border:2px solid #0D0D1A;
      display:none;
    }
    #ai-fab .fab-badge.show { display:block; }

    /* Chat window */
    #ai-chat-window {
      position:fixed; bottom:5.5rem; right:1.5rem; z-index:9000;
      width:360px; max-width:calc(100vw - 2rem);
      height:520px; max-height:calc(100vh - 8rem);
      background:#1A1A2E; border:1px solid rgba(255,107,43,0.25);
      border-radius:20px; overflow:hidden;
      box-shadow:0 20px 60px rgba(0,0,0,0.5);
      display:flex; flex-direction:column;
      opacity:0; transform:scale(0.92) translateY(12px);
      pointer-events:none;
      transition:opacity 0.25s, transform 0.25s;
      transform-origin:bottom right;
    }
    #ai-chat-window.open {
      opacity:1; transform:scale(1) translateY(0);
      pointer-events:auto;
    }

    /* Header */
    #ai-chat-header {
      background:linear-gradient(135deg,rgba(255,107,43,0.15),rgba(245,166,35,0.08));
      border-bottom:1px solid rgba(255,255,255,0.08);
      padding:0.9rem 1.1rem;
      display:flex; align-items:center; gap:0.7rem;
      flex-shrink:0;
    }
    #ai-chat-header .ai-avatar {
      width:36px; height:36px; border-radius:50%;
      background:linear-gradient(135deg,#FF6B2B,#F5A623);
      display:flex; align-items:center; justify-content:center;
      font-size:1.1rem; flex-shrink:0;
    }
    #ai-chat-header .ai-info { flex:1; min-width:0; }
    #ai-chat-header .ai-info strong { font-family:'Syne',sans-serif; font-size:0.9rem; display:block; color:#F0EDE8; }
    #ai-chat-header .ai-info span { font-size:0.72rem; color:#00C9A7; }
    #ai-chat-header .ai-close {
      background:rgba(255,255,255,0.08); border:none; color:#8A8A9A;
      width:28px; height:28px; border-radius:50%; cursor:pointer;
      font-size:0.85rem; display:flex; align-items:center; justify-content:center;
      transition:all 0.18s;
    }
    #ai-chat-header .ai-close:hover { background:rgba(255,71,87,0.2); color:#FF6B7A; }

    /* Messages */
    #ai-chat-msgs {
      flex:1; overflow-y:auto; padding:1rem;
      display:flex; flex-direction:column; gap:0.8rem;
    }
    #ai-chat-msgs::-webkit-scrollbar { width:3px; }
    #ai-chat-msgs::-webkit-scrollbar-thumb { background:rgba(255,255,255,0.1); border-radius:2px; }
    .ai-msg { display:flex; gap:0.5rem; align-items:flex-start; }
    .ai-msg.user { flex-direction:row-reverse; }
    .ai-msg-av {
      width:28px; height:28px; border-radius:50%; flex-shrink:0;
      display:flex; align-items:center; justify-content:center; font-size:0.8rem;
    }
    .ai-msg-av.bot { background:linear-gradient(135deg,#FF6B2B,#F5A623); }
    .ai-msg-av.usr { background:rgba(0,201,167,0.15); border:1px solid rgba(0,201,167,0.3); color:#00C9A7; font-size:0.7rem; font-weight:700; font-family:'Syne',sans-serif; }
    .ai-msg-bubble {
      max-width:78%; padding:0.6rem 0.9rem;
      font-size:0.83rem; line-height:1.6; border-radius:14px;
      color:#F0EDE8; word-break:break-word;
    }
    .ai-msg.bot .ai-msg-bubble { background:rgba(255,255,255,0.06); border:1px solid rgba(255,255,255,0.08); border-top-left-radius:4px; }
    .ai-msg.user .ai-msg-bubble { background:linear-gradient(135deg,rgba(255,107,43,0.25),rgba(245,166,35,0.15)); border:1px solid rgba(255,107,43,0.22); border-top-right-radius:4px; }
    .ai-typing { display:flex; gap:5px; align-items:center; padding:0.4rem 0.2rem; }
    .ai-typing span { width:7px; height:7px; background:#8A8A9A; border-radius:50%; animation:aiDotBounce 1.2s infinite; }
    .ai-typing span:nth-child(2){animation-delay:0.2s}
    .ai-typing span:nth-child(3){animation-delay:0.4s}
    @keyframes aiDotBounce { 0%,60%,100%{transform:translateY(0)} 30%{transform:translateY(-7px)} }

    /* Suggestions */
    #ai-chat-suggestions {
      display:flex; flex-wrap:wrap; gap:0.4rem;
      padding:0 0.9rem 0.6rem; flex-shrink:0;
    }
    .ai-chip {
      background:rgba(255,255,255,0.04); border:1px solid rgba(255,255,255,0.1);
      color:#8A8A9A; border-radius:50px; padding:0.22rem 0.7rem;
      font-size:0.72rem; cursor:pointer; font-family:'DM Sans',sans-serif;
      transition:all 0.18s;
    }
    .ai-chip:hover { background:rgba(255,107,43,0.1); border-color:rgba(255,107,43,0.3); color:#FF6B2B; }

    /* Input */
    #ai-chat-input-row {
      border-top:1px solid rgba(255,255,255,0.08);
      padding:0.7rem 0.9rem;
      display:flex; gap:0.6rem; align-items:flex-end; flex-shrink:0;
    }
    #ai-chat-input {
      flex:1; background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1);
      border-radius:10px; padding:0.6rem 0.8rem; color:#F0EDE8;
      font-family:'DM Sans',sans-serif; font-size:0.85rem;
      resize:none; min-height:38px; max-height:90px; outline:none;
      transition:border-color 0.2s;
    }
    #ai-chat-input:focus { border-color:rgba(255,107,43,0.5); }
    #ai-chat-input::placeholder { color:#8A8A9A; }
    #ai-chat-send {
      width:38px; height:38px; flex-shrink:0;
      background:linear-gradient(135deg,#FF6B2B,#F5A623);
      border:none; border-radius:9px; color:white; cursor:pointer;
      font-size:0.9rem; display:flex; align-items:center; justify-content:center;
      transition:all 0.2s;
    }
    #ai-chat-send:hover { transform:scale(1.08); box-shadow:0 3px 12px rgba(255,107,43,0.4); }
    #ai-chat-send:disabled { opacity:0.45; cursor:not-allowed; transform:none; }

    @media(max-width:480px){
      #ai-chat-window { width:calc(100vw - 1rem); right:0.5rem; bottom:5rem; }
      #ai-fab { bottom:1rem; right:1rem; }
    }
  `;
  document.head.appendChild(style);

  // ── Build HTML ────────────────────────────────────────────────
  const fab = document.createElement('button');
  fab.id = 'ai-fab';
  fab.title = 'AI Tutor';
  fab.innerHTML = '🤖<span class="fab-badge" id="ai-fab-badge"></span>';
  fab.onclick = toggleChat;
  document.body.appendChild(fab);

  const win = document.createElement('div');
  win.id = 'ai-chat-window';
  win.innerHTML = `
    <div id="ai-chat-header">
      <div class="ai-avatar">🤖</div>
      <div class="ai-info">
        <strong>EduStar AI Tutor</strong>
        <span>● Always here to help</span>
      </div>
      <button class="ai-close" onclick="window._aiChatClose()" title="Close">✕</button>
    </div>
    <div id="ai-chat-msgs"></div>
    <div id="ai-chat-suggestions"></div>
    <div id="ai-chat-input-row">
      <textarea id="ai-chat-input" placeholder="Ask your question here..." rows="1"></textarea>
      <button id="ai-chat-send" onclick="window._aiChatSend()">➤</button>
    </div>
  `;
  document.body.appendChild(win);

  // ── Wire up input ─────────────────────────────────────────────
  const inp = document.getElementById('ai-chat-input');
  inp.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); window._aiChatSend(); }
  });
  inp.addEventListener('input', () => {
    inp.style.height = 'auto';
    inp.style.height = Math.min(inp.scrollHeight, 90) + 'px';
  });

  // ── Toggle open/close ─────────────────────────────────────────
  function toggleChat() {
    isOpen = !isOpen;
    win.classList.toggle('open', isOpen);
    fab.innerHTML = isOpen ? '✕<span class="fab-badge" id="ai-fab-badge"></span>' : '🤖<span class="fab-badge" id="ai-fab-badge"></span>';
    document.getElementById('ai-fab-badge').classList.remove('show');
    if (isOpen && !hasGreeted) {
      hasGreeted = true;
      showGreeting();
      showSuggestions();
    }
    if (isOpen) setTimeout(() => inp.focus(), 280);
  }
  window._aiChatClose = () => { if (isOpen) toggleChat(); };

  // ── Greeting ──────────────────────────────────────────────────
  function showGreeting() {
    const country = (typeof COUNTRY_NAMES !== 'undefined' && COUNTRY_NAMES[student.country]) || 'Africa';
    addBotMsg(`👋 Hujambo, ${student.name}! I'm your EduStar AI Tutor. I know the ${country} curriculum for ${student.grade} inside out. What are you studying today?`);
  }

  function showSuggestions() {
    const country = (typeof COUNTRY_NAMES !== 'undefined' && COUNTRY_NAMES[student.country]) || 'Africa';
    const chips = [
      `Help with ${student.grade} Maths`,
      `Explain photosynthesis`,
      `Essay writing tips`,
      `${country} history`,
      `Exam strategies`,
    ];
    const el = document.getElementById('ai-chat-suggestions');
    el.innerHTML = chips.map(c =>
      `<button class="ai-chip" onclick="window._aiUseChip(this)">${c}</button>`
    ).join('');
  }

  window._aiUseChip = function(btn) {
    inp.value = btn.textContent;
    document.getElementById('ai-chat-suggestions').innerHTML = '';
    window._aiChatSend();
  };

  // ── Send message ──────────────────────────────────────────────
  window._aiChatSend = async function () {
    const msg = inp.value.trim();
    if (!msg || chatBusy) return;
    inp.value = ''; inp.style.height = 'auto';
    document.getElementById('ai-chat-suggestions').innerHTML = '';
    addUserMsg(msg);
    chatHistory.push({ role: 'user', content: msg });
    await getReply();
  };

  // ── Get AI reply ──────────────────────────────────────────────
  async function getReply() {
    chatBusy = true;
    document.getElementById('ai-chat-send').disabled = true;

    const typingId = 'ai-typing-' + Date.now();
    const typingEl = document.createElement('div');
    typingEl.className = 'ai-msg bot';
    typingEl.id = typingId;
    typingEl.innerHTML = `<div class="ai-msg-av bot">🤖</div><div class="ai-msg-bubble"><div class="ai-typing"><span></span><span></span><span></span></div></div>`;
    document.getElementById('ai-chat-msgs').appendChild(typingEl);
    scrollMsgs();

    const systemPrompt = (typeof buildSystemPrompt === 'function')
      ? buildSystemPrompt(student)
      : `You are a helpful educational AI tutor for ${student.name}, a student in ${student.grade}.`;

    try {
      let reply;

      // Primary: Pollinations OpenAI-compatible
      try {
        const res = await fetch('https://text.pollinations.ai/openai', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            model: 'openai',
            messages: [{ role: 'system', content: systemPrompt }, ...chatHistory],
            seed: Math.floor(Math.random() * 9999),
            private: true
          })
        });
        if (!res.ok) throw new Error('status ' + res.status);
        const data = await res.json();
        reply = data.choices?.[0]?.message?.content;
        if (!reply) throw new Error('empty');
      } catch (e1) {
        // Fallback: Pollinations simple GET
        const sys = systemPrompt.slice(0, 300);
        const last = chatHistory[chatHistory.length - 1]?.content || '';
        const prompt = encodeURIComponent(sys + '\n\nStudent: ' + last + '\n\nTutor:');
        const res2 = await fetch('https://text.pollinations.ai/' + prompt);
        if (!res2.ok) throw new Error('fallback failed');
        reply = await res2.text();
        if (!reply || reply.length < 3) throw new Error('empty fallback');
      }

      document.getElementById(typingId)?.remove();
      addBotMsg(reply);
      chatHistory.push({ role: 'assistant', content: reply });

      // +5 pts every 5 messages
      const userCount = chatHistory.filter(m => m.role === 'user').length;
      if (userCount % 5 === 0) {
        student.points = (student.points || 0) + 5;
        if (typeof saveStudent === 'function') saveStudent(student);
        if (typeof toast === 'function') toast('💬 +5 pts for studying with AI Tutor!');
      }

      // Badge on fab when window is closed
      if (!isOpen) {
        const badge = document.getElementById('ai-fab-badge');
        if (badge) badge.classList.add('show');
      }

    } catch (e) {
      document.getElementById(typingId)?.remove();
      addBotMsg('⚠️ The AI tutor is temporarily unavailable. Please try again in a moment!');
    }

    chatBusy = false;
    document.getElementById('ai-chat-send').disabled = false;
  }

  // ── Message renderers ─────────────────────────────────────────
  function addBotMsg(text) {
    const el = document.createElement('div');
    el.className = 'ai-msg bot';
    el.innerHTML = `<div class="ai-msg-av bot">🤖</div><div class="ai-msg-bubble">${text.replace(/\n/g,'<br>')}</div>`;
    document.getElementById('ai-chat-msgs').appendChild(el);
    scrollMsgs();
  }

  function addUserMsg(text) {
    const initials = (student.name || 'U').split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
    const el = document.createElement('div');
    el.className = 'ai-msg user';
    el.innerHTML = `<div class="ai-msg-av usr">${initials}</div><div class="ai-msg-bubble">${escapeHtml(text)}</div>`;
    document.getElementById('ai-chat-msgs').appendChild(el);
    scrollMsgs();
  }

  function scrollMsgs() {
    const el = document.getElementById('ai-chat-msgs');
    if (el) el.scrollTop = el.scrollHeight;
  }

  function escapeHtml(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

})();