<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EduStar — Admin Panel</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../styles/normalize.css">
  <link rel="stylesheet" href="../styles/shared.css">
  <style>
    .admin-grid { display:grid; grid-template-columns:220px 1fr; min-height:100vh; }
    .admin-sidebar { background:rgba(13,13,26,0.95); border-right:1px solid var(--border); padding:1.5rem 1rem; position:sticky; top:0; height:100vh; overflow-y:auto; }
    .admin-sidebar h2 { font-family:'Syne',sans-serif; font-weight:800; font-size:1.1rem; margin-bottom:1.5rem; background:linear-gradient(135deg,var(--sun),var(--gold)); -webkit-background-clip:text; -webkit-text-fill-color:transparent; background-clip:text; }
    .sidebar-link { display:block; padding:0.6rem 0.9rem; border-radius:var(--radius-sm); font-size:0.85rem; color:var(--muted); text-decoration:none; cursor:pointer; margin-bottom:0.2rem; border:1px solid transparent; transition:all 0.2s; }
    .sidebar-link:hover,.sidebar-link.active { background:rgba(255,107,43,0.1); border-color:rgba(255,107,43,0.25); color:var(--sun); }
    .admin-main { padding:2rem; }
    .admin-section { display:none; }
    .admin-section.active { display:block; }
    .admin-section h2 { font-family:'Syne',sans-serif; font-weight:700; font-size:1.4rem; margin-bottom:1.5rem; }
    table { width:100%; border-collapse:collapse; font-size:0.84rem; }
    th { text-align:left; padding:0.7rem 1rem; background:rgba(255,255,255,0.04); border-bottom:1px solid var(--border); font-weight:600; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.4px; color:var(--muted); }
    td { padding:0.7rem 1rem; border-bottom:1px solid var(--border); vertical-align:middle; }
    tr:hover td { background:rgba(255,255,255,0.02); }
    .badge { display:inline-block; padding:0.18rem 0.6rem; border-radius:50px; font-size:0.68rem; font-weight:600; }
    .badge-admin { background:rgba(255,107,43,0.15); color:var(--sun); border:1px solid rgba(255,107,43,0.3); }
    .badge-active { background:rgba(0,201,167,0.12); color:var(--teal); border:1px solid rgba(0,201,167,0.28); }
    .form-card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:1.6rem; margin-bottom:1.5rem; max-width:680px; }
    .form-card h3 { font-family:'Syne',sans-serif; font-weight:700; margin-bottom:1.2rem; }
    .field { margin-bottom:1rem; }
    .field label { display:block; font-size:0.78rem; color:var(--muted); margin-bottom:0.35rem; text-transform:uppercase; letter-spacing:0.4px; }
    .field input, .field select, .field textarea { width:100%; background:rgba(255,255,255,0.05); border:1px solid var(--border); border-radius:10px; padding:0.75rem 1rem; color:var(--text); font-family:'DM Sans',sans-serif; font-size:0.9rem; outline:none; }
    .field input:focus,.field select:focus,.field textarea:focus { border-color:rgba(255,107,43,0.5); }
    .field textarea { min-height:80px; resize:vertical; }
    .field select option { background:var(--sky); }
    .stat-cards { display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:1rem; margin-bottom:2rem; }
    .stat-card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:1.2rem; text-align:center; }
    .stat-card .n { font-family:'Syne',sans-serif; font-size:2rem; font-weight:800; color:var(--gold); }
    .stat-card .l { font-size:0.75rem; color:var(--muted); margin-top:0.2rem; }
    .upload-zone { border:2px dashed var(--border); border-radius:var(--radius); padding:2rem; text-align:center; cursor:pointer; transition:all 0.2s; }
    .upload-zone:hover,.upload-zone.drag { border-color:rgba(255,107,43,0.5); background:rgba(255,107,43,0.05); }
    .upload-zone p { color:var(--muted); font-size:0.85rem; margin-top:0.5rem; }
    #upload-file { display:none; }
    .error-box { background:rgba(255,71,87,0.1); border:1px solid rgba(255,71,87,0.3); border-radius:10px; padding:0.8rem 1rem; font-size:0.83rem; color:#FF6B7A; margin-bottom:1rem; display:none; }
    .error-box.show { display:block; }
    .success-box { background:rgba(0,201,167,0.1); border:1px solid rgba(0,201,167,0.28); border-radius:10px; padding:0.8rem 1rem; font-size:0.83rem; color:var(--teal); margin-bottom:1rem; display:none; }
    .success-box.show { display:block; }
  </style>
</head>
<body>
<div class="app">
  <div id="auth-guard" style="display:flex;align-items:center;justify-content:center;min-height:100vh;flex-direction:column;gap:1rem">
    <div style="font-size:3rem">🔐</div>
    <h2 style="font-family:'Syne',sans-serif">Admin Login</h2>
    <div style="background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:2rem;width:360px">
      <div class="field"><label>Email</label><input type="email" id="adm-email" placeholder="admin@example.com"></div>
      <div class="field"><label>Password</label><input type="password" id="adm-pw" placeholder="Password"></div>
      <div class="error-box" id="adm-err"></div>
      <button class="btn btn-primary" style="width:100%" onclick="adminLogin()">Login as Admin</button>
    </div>
  </div>

  <div id="admin-app" style="display:none">
    <div class="admin-grid">
      <!-- Sidebar -->
      <div class="admin-sidebar">
        <h2>⚡ EduStar Admin</h2>
        <a class="sidebar-link active" onclick="showSection('dashboard')">📊 Dashboard</a>
        <a class="sidebar-link" onclick="showSection('users')">👥 Users</a>
        <a class="sidebar-link" onclick="showSection('books')">📚 Books</a>
        <a class="sidebar-link" onclick="showSection('upload')">⬆️ Upload PDF</a>
        <a class="sidebar-link" onclick="showSection('add-book')">➕ Add Book</a>
        <hr style="border-color:var(--border);margin:1rem 0">
        <a class="sidebar-link" href="../index.html">🏠 Back to App</a>
        <a class="sidebar-link" onclick="adminLogout()">🚪 Logout</a>
      </div>

      <!-- Main -->
      <div class="admin-main">

        <!-- DASHBOARD -->
        <div class="admin-section active" id="sec-dashboard">
          <h2>📊 Overview</h2>
          <div class="stat-cards">
            <div class="stat-card"><div class="n" id="stat-users">—</div><div class="l">Total Users</div></div>
            <div class="stat-card"><div class="n" id="stat-books">—</div><div class="l">Books in DB</div></div>
            <div class="stat-card"><div class="n" id="stat-quizzes">—</div><div class="l">Quizzes Taken</div></div>
            <div class="stat-card"><div class="n" id="stat-downloads">—</div><div class="l">Book Downloads</div></div>
          </div>
          <h3 style="font-family:'Syne',sans-serif;margin-bottom:1rem">🏆 Top Students</h3>
          <table id="lb-table"><thead><tr><th>#</th><th>Name</th><th>Country</th><th>Points</th><th>Level</th></tr></thead><tbody></tbody></table>
        </div>

        <!-- USERS -->
        <div class="admin-section" id="sec-users">
          <h2>👥 Registered Users</h2>
          <div style="margin-bottom:1rem"><input class="field" style="max-width:320px;display:inline-block;padding:0.55rem 1rem;background:rgba(255,255,255,0.05);border:1px solid var(--border);border-radius:10px;color:var(--text);font-family:'DM Sans',sans-serif;outline:none" placeholder="🔍 Search users..." oninput="filterUserTable(this.value)" id="user-search"></div>
          <table id="users-table">
            <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Country</th><th>Grade</th><th>Points</th><th>Level</th><th>Joined</th><th>Role</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>

        <!-- BOOKS LIST -->
        <div class="admin-section" id="sec-books">
          <h2>📚 Books Database</h2>
          <table id="books-table">
            <thead><tr><th>Key</th><th>Title</th><th>Subject</th><th>Country</th><th>Grade</th><th>File?</th><th>Downloads</th><th>Actions</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>

        <!-- UPLOAD PDF -->
        <div class="admin-section" id="sec-upload">
          <h2>⬆️ Upload PDF for a Book</h2>
          <div class="form-card">
            <h3>Select Book & Upload File</h3>
            <div class="success-box" id="upload-success"></div>
            <div class="error-box" id="upload-error"></div>
            <div class="field">
              <label>Book Key</label>
              <select id="upload-bookkey"><option value="">Loading books...</option></select>
            </div>
            <div class="upload-zone" onclick="document.getElementById('upload-file').click()" id="upload-zone">
              <div style="font-size:2.5rem">📄</div>
              <p>Click to select a PDF file</p>
              <p id="upload-filename" style="color:var(--sun);margin-top:0.5rem"></p>
            </div>
            <input type="file" id="upload-file" accept="application/pdf" onchange="onFileSelect(this)">
            <button class="btn btn-primary" style="margin-top:1rem;width:100%" onclick="doUpload()">⬆️ Upload PDF</button>
          </div>
        </div>

        <!-- ADD BOOK -->
        <div class="admin-section" id="sec-add-book">
          <h2>➕ Add New Book</h2>
          <div class="form-card">
            <h3>Book Metadata</h3>
            <div class="success-box" id="add-success"></div>
            <div class="error-box" id="add-error"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
              <div class="field"><label>Book Key (unique slug)</label><input id="nb-key" placeholder="e.g. ke-math-g8"></div>
              <div class="field"><label>Title</label><input id="nb-title" placeholder="Mathematics Grade 8"></div>
              <div class="field"><label>Subject</label>
                <select id="nb-subject">
                  <option value="math">Mathematics</option><option value="english">English</option>
                  <option value="science">Science</option><option value="biology">Biology</option>
                  <option value="chemistry">Chemistry</option><option value="physics">Physics</option>
                  <option value="history">History</option><option value="geography">Geography</option>
                  <option value="economics">Economics</option><option value="accounting">Accounting</option>
                  <option value="computer">Computer Studies</option><option value="agriculture">Agriculture</option>
                  <option value="business">Business Studies</option><option value="kiswahili">Kiswahili</option>
                  <option value="literature">Literature</option>
                </select>
              </div>
              <div class="field"><label>Country Code</label>
                <select id="nb-country">
                  <option value="continental">🌍 Pan-African</option>
                  <option value="KE">🇰🇪 Kenya</option><option value="NG">🇳🇬 Nigeria</option>
                  <option value="ZA">🇿🇦 South Africa</option><option value="TZ">🇹🇿 Tanzania</option>
                  <option value="UG">🇺🇬 Uganda</option><option value="GH">🇬🇭 Ghana</option>
                  <option value="ZW">🇿🇼 Zimbabwe</option><option value="ZM">🇿🇲 Zambia</option>
                  <option value="ET">🇪🇹 Ethiopia</option><option value="RW">🇷🇼 Rwanda</option>
                </select>
              </div>
              <div class="field"><label>Grade Range</label>
                <select id="nb-grade">
                  <option value="lower_primary">Lower Primary</option>
                  <option value="upper_primary">Upper Primary</option>
                  <option value="lower_secondary">Lower Secondary</option>
                  <option value="upper_secondary">Upper Secondary</option>
                </select>
              </div>
              <div class="field"><label>Year</label><input id="nb-year" type="number" placeholder="2024"></div>
              <div class="field"><label>Publisher</label><input id="nb-publisher" placeholder="KICD"></div>
              <div class="field"><label>Curriculum</label><input id="nb-curriculum" placeholder="CBC"></div>
              <div class="field"><label>Icon (emoji)</label><input id="nb-icon" placeholder="📐"></div>
              <div class="field"><label>Colour (hex)</label><input id="nb-color" type="color" value="#FF6B2B"></div>
            </div>
            <div class="field"><label>Chapters (one per line: "Chapter Title | topic1, topic2")</label>
              <textarea id="nb-chapters" placeholder="Chapter 1: Numbers | Integers, Fractions, Decimals&#10;Chapter 2: Algebra | Equations, Inequalities"></textarea>
            </div>
            <button class="btn btn-primary" onclick="doAddBook()">➕ Create Book</button>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>
<div class="toast-container" id="toasts"></div>

<script>
const API = '../api';
let adminToken = localStorage.getItem('edustar_admin_token');
let allUsers = [], allBooks = [];

// ── AUTH ─────────────────────────────────────────────────────────
async function adminLogin() {
  const email = document.getElementById('adm-email').value.trim();
  const pw    = document.getElementById('adm-pw').value;
  try {
    const res = await api('POST', '/auth.php?action=login', {email, password: pw});
    if (!res.user.isAdmin) throw new Error('Not an admin account.');
    localStorage.setItem('edustar_admin_token', res.token);
    adminToken = res.token;
    showApp();
  } catch (e) {
    const el = document.getElementById('adm-err');
    el.textContent = e.message; el.style.display = 'block';
  }
}

function adminLogout() {
  localStorage.removeItem('edustar_admin_token');
  location.reload();
}

function showApp() {
  document.getElementById('auth-guard').style.display = 'none';
  document.getElementById('admin-app').style.display = 'block';
  loadDashboard(); loadUsers(); loadBooks();
}

window.addEventListener('load', () => {
  if (adminToken) showApp();
});

// ── API helper ────────────────────────────────────────────────────
async function api(method, path, body, isForm) {
  const opts = {
    method,
    headers: { Authorization: `Bearer ${adminToken}` }
  };
  if (body && !isForm) {
    opts.headers['Content-Type'] = 'application/json';
    opts.body = JSON.stringify(body);
  } else if (body && isForm) {
    opts.body = body;
  }
  const res = await fetch(API + path, opts);
  const data = await res.json();
  if (!data.ok) throw new Error(data.error || 'API error');
  return data;
}

// ── NAV ───────────────────────────────────────────────────────────
function showSection(id) {
  document.querySelectorAll('.admin-section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
  document.getElementById('sec-' + id).classList.add('active');
  event.currentTarget.classList.add('active');
}

// ── DASHBOARD ────────────────────────────────────────────────────
async function loadDashboard() {
  try {
    const lb = await api('GET', '/quiz.php?action=leaderboard');
    document.getElementById('stat-users').textContent = allUsers.length || '…';
    const medals = ['🥇','🥈','🥉'];
    document.querySelector('#lb-table tbody').innerHTML = lb.leaderboard.map((u,i) =>
      `<tr><td>${medals[i]||'#'+(i+1)}</td><td>${u.name}</td><td>${u.country}</td><td>${u.points}</td><td>Lv.${u.level}</td></tr>`
    ).join('');
  } catch(e) { console.error(e); }
}

// ── USERS ─────────────────────────────────────────────────────────
async function loadUsers() {
  try {
    const data = await api('GET', '/auth.php?action=me');
    // For admin listing we fetch leaderboard as proxy (real admin would have /api/admin/users)
    // Here we render what we have
    renderUserTable([]);
  } catch(e) {}
}

function renderUserTable(users) {
  document.querySelector('#users-table tbody').innerHTML = users.length
    ? users.map(u => `<tr>
        <td>${u.id}</td><td>${u.name}</td><td>${u.email}</td>
        <td>${u.country}</td><td>${u.grade}</td><td>${u.points}</td>
        <td>${u.level}</td><td>${(u.joinDate||'').split('T')[0]}</td>
        <td>${u.isAdmin?'<span class="badge badge-admin">Admin</span>':'<span class="badge badge-active">User</span>'}</td>
      </tr>`).join('')
    : '<tr><td colspan="9" style="text-align:center;color:var(--muted);padding:2rem">User list requires server-side admin endpoint — see /api/admin/users.php</td></tr>';
}

function filterUserTable(q) {
  const rows = document.querySelectorAll('#users-table tbody tr');
  rows.forEach(r => r.style.display = r.innerText.toLowerCase().includes(q.toLowerCase()) ? '' : 'none');
}

// ── BOOKS ─────────────────────────────────────────────────────────
async function loadBooks() {
  try {
    const data = await api('GET', '/books.php?action=list');
    allBooks = data.books;
    renderBooksTable(allBooks);
    populateBookSelect(allBooks);
    document.getElementById('stat-books').textContent = allBooks.length;
    document.getElementById('stat-downloads').textContent =
      allBooks.reduce((s, b) => s + (b.download_count||0), 0);
  } catch(e) { console.error(e); }
}

function renderBooksTable(books) {
  const tbody = document.querySelector('#books-table tbody');
  tbody.innerHTML = books.map(b => `<tr>
    <td><code style="font-size:0.75rem">${b.book_key}</code></td>
    <td>${b.title}</td><td>${b.subject}</td><td>${b.country}</td>
    <td>${b.grade_range}</td>
    <td>${b.hasFile ? '<span class="badge badge-active">✅ PDF</span>' : '<span style="color:var(--muted)">—</span>'}</td>
    <td>${b.download_count}</td>
    <td><button class="btn btn-sm btn-teal" onclick="quickUpload('${b.book_key}')">⬆️ Upload</button>
        <button class="btn btn-sm" style="background:rgba(255,71,87,0.1);color:#FF6B7A;border:1px solid rgba(255,71,87,0.3);border-radius:50px;padding:0.35rem 0.8rem;font-size:0.75rem;cursor:pointer" onclick="deleteBook('${b.book_key}')">🗑</button>
    </td>
  </tr>`).join('');
}

function populateBookSelect(books) {
  const sel = document.getElementById('upload-bookkey');
  sel.innerHTML = '<option value="">— Choose a book —</option>' +
    books.map(b => `<option value="${b.book_key}">${b.title} (${b.book_key})</option>`).join('');
}

function quickUpload(key) {
  document.getElementById('upload-bookkey').value = key;
  showSection('upload');
  document.querySelectorAll('.sidebar-link')[3].classList.add('active');
}

async function deleteBook(key) {
  if (!confirm(`Delete "${key}" from the database?`)) return;
  try {
    await api('DELETE', `/books.php?action=delete&id=${key}`);
    toast('Book removed.'); loadBooks();
  } catch(e) { toast(e.message, 'error'); }
}

// ── UPLOAD PDF ────────────────────────────────────────────────────
let selectedFile = null;

function onFileSelect(inp) {
  selectedFile = inp.files[0];
  document.getElementById('upload-filename').textContent = selectedFile ? '📄 ' + selectedFile.name : '';
}

document.addEventListener('DOMContentLoaded', () => {
  const zone = document.getElementById('upload-zone');
  zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag'); });
  zone.addEventListener('dragleave', () => zone.classList.remove('drag'));
  zone.addEventListener('drop', e => {
    e.preventDefault(); zone.classList.remove('drag');
    const f = e.dataTransfer.files[0];
    if (f) { selectedFile = f; document.getElementById('upload-filename').textContent = '📄 ' + f.name; }
  });
});

async function doUpload() {
  const key = document.getElementById('upload-bookkey').value;
  const sEl = document.getElementById('upload-success');
  const eEl = document.getElementById('upload-error');
  sEl.style.display = eEl.style.display = 'none';

  if (!key) { eEl.textContent = 'Please select a book.'; eEl.style.display = 'block'; return; }
  if (!selectedFile) { eEl.textContent = 'Please select a PDF file.'; eEl.style.display = 'block'; return; }

  const fd = new FormData();
  fd.append('bookKey', key);
  fd.append('file', selectedFile);

  try {
    await api('POST', '/books.php?action=upload', fd, true);
    sEl.textContent = `✅ "${selectedFile.name}" uploaded successfully!`;
    sEl.style.display = 'block';
    selectedFile = null;
    document.getElementById('upload-filename').textContent = '';
    loadBooks();
  } catch(e) { eEl.textContent = e.message; eEl.style.display = 'block'; }
}

// ── ADD BOOK ──────────────────────────────────────────────────────
async function doAddBook() {
  const sEl = document.getElementById('add-success');
  const eEl = document.getElementById('add-error');
  sEl.style.display = eEl.style.display = 'none';

  const chapRaw = document.getElementById('nb-chapters').value.trim();
  const chapters = chapRaw ? chapRaw.split('\n').filter(Boolean).map(line => {
    const [title, topicsStr] = line.split('|');
    return { title: title?.trim(), topics: topicsStr ? topicsStr.split(',').map(t => t.trim()) : [] };
  }) : [];

  const payload = {
    book_key:   document.getElementById('nb-key').value.trim(),
    title:      document.getElementById('nb-title').value.trim(),
    subject:    document.getElementById('nb-subject').value,
    country:    document.getElementById('nb-country').value,
    grade_range:document.getElementById('nb-grade').value,
    year:       parseInt(document.getElementById('nb-year').value) || null,
    publisher:  document.getElementById('nb-publisher').value.trim(),
    curriculum: document.getElementById('nb-curriculum').value.trim(),
    icon:       document.getElementById('nb-icon').value.trim() || '📚',
    color:      document.getElementById('nb-color').value,
    chapters
  };

  try {
    await api('POST', '/books.php?action=create', payload);
    sEl.textContent = `✅ Book "${payload.title}" created successfully!`;
    sEl.style.display = 'block';
    loadBooks();
  } catch(e) { eEl.textContent = e.message; eEl.style.display = 'block'; }
}

// ── TOAST ─────────────────────────────────────────────────────────
function toast(msg, type) {
  const el = document.createElement('div');
  el.className = 'toast' + (type === 'error' ? '' : ' success');
  el.textContent = msg;
  document.getElementById('toasts').appendChild(el);
  setTimeout(() => el.remove(), 3500);
}
</script>
</body>
</html>
