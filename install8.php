<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<pre style='font-family:monospace;font-size:13px;padding:20px;background:#0d0d1a;color:#f0f0ff'>";

// ── STEP 1: TEST THE API DIRECTLY ────────────────────────────────
echo "=== TESTING /api/admin/users.php DIRECTLY ===\n";
$usersFile = __DIR__ . '/api/admin/users.php';
echo "File exists: " . (file_exists($usersFile) ? "✅ YES" : "❌ NO") . "\n";

// Try to simulate what the API returns
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/helpers.php';

$count = db()->query("SELECT COUNT(*) FROM users")->fetchColumn();
echo "Users in DB: $count\n";

// Get the admin token from sessions
$sessions = db()->query("SELECT s.token, u.name, u.is_admin FROM sessions s JOIN users u ON u.id=s.user_id WHERE u.is_admin=1 AND s.expires_at > NOW() ORDER BY s.created_at DESC LIMIT 3")->fetchAll();
echo "Active admin sessions: " . count($sessions) . "\n";
foreach ($sessions as $s) {
    echo "  token=" . substr($s['token'],0,16) . "... user={$s['name']} is_admin={$s['is_admin']}\n";
}

// ── STEP 2: REWRITE admin/index.php FROM SCRATCH ─────────────────
// The problem: multiple install scripts created conflicting versions.
// Solution: read current file, extract the HTML/CSS only, inject fresh JS.
echo "\n=== REWRITING admin/index.php ===\n";

$adminPath = __DIR__ . '/admin/index.php';
$current = file_get_contents($adminPath);

// Extract everything from start to <script>
$scriptPos = strrpos($current, '<script>');
$htmlPart  = substr($current, 0, $scriptPos + 8); // include <script>

// Build clean JS
$cleanJS = '
const API = \'/api\';
let adminToken = localStorage.getItem(\'edustar_admin_token\');
let allUsers = [], allBooks = [];

// ── AUTH ──────────────────────────────────────────────────────────
async function adminLogin() {
  const email = document.getElementById(\'adm-email\').value.trim();
  const pw    = document.getElementById(\'adm-pw\').value;
  try {
    const res = await api(\'POST\', \'/auth.php?action=login\', {email, password: pw});
    if (!res.user.isAdmin) throw new Error(\'Not an admin account.\');
    localStorage.setItem(\'edustar_admin_token\', res.token);
    adminToken = res.token;
    showApp();
  } catch (e) {
    const el = document.getElementById(\'adm-err\');
    el.textContent = e.message; el.style.display = \'block\';
  }
}

function adminLogout() {
  localStorage.removeItem(\'edustar_admin_token\');
  location.reload();
}

function showApp() {
  document.getElementById(\'auth-guard\').style.display = \'none\';
  document.getElementById(\'admin-app\').style.display  = \'block\';
  loadDashboard();
  loadUsers();
  loadBooks();
}

window.addEventListener(\'load\', () => { if (adminToken) showApp(); });

// ── API HELPER ────────────────────────────────────────────────────
async function api(method, path, body, isForm) {
  const opts = { method, headers: { Authorization: `Bearer ${adminToken}` } };
  if (body && !isForm) { opts.headers[\'Content-Type\'] = \'application/json\'; opts.body = JSON.stringify(body); }
  else if (body && isForm) { opts.body = body; }
  const res  = await fetch(API + path, opts);
  const data = await res.json();
  if (!data.ok) throw new Error(data.error || \'API error\');
  return data;
}

// ── NAVIGATION ────────────────────────────────────────────────────
function showSection(id) {
  document.querySelectorAll(\'.admin-section\').forEach(s => s.classList.remove(\'active\'));
  document.querySelectorAll(\'.sidebar-link\').forEach(l => l.classList.remove(\'active\'));
  document.getElementById(\'sec-\' + id).classList.add(\'active\');
  event.currentTarget.classList.add(\'active\');
}

// ── DASHBOARD ─────────────────────────────────────────────────────
async function loadDashboard() {
  try {
    const lb = await api(\'GET\', \'/quiz.php?action=leaderboard\');
    const medals = [\'🥇\',\'🥈\',\'🥉\'];
    document.querySelector(\'#lb-table tbody\').innerHTML = lb.leaderboard.map((u,i) =>
      `<tr><td>${medals[i]||\'#\'+(i+1)}</td><td>${u.name}</td><td>${u.country}</td><td>${u.points}</td><td>Lv.${u.level}</td></tr>`
    ).join(\'\');
  } catch(e) { console.error(\'loadDashboard:\', e); }
}

// ── USERS ─────────────────────────────────────────────────────────
async function loadUsers() {
  try {
    const data = await api(\'GET\', \'/admin/users.php?action=list\');
    allUsers = data.users || [];
    renderUserTable(allUsers);
    if (data.stats) {
      const set = (id, val) => { const el = document.getElementById(id); if(el) el.textContent = val; };
      set(\'stat-users\',     data.stats.users     || 0);
      set(\'stat-quizzes\',   data.stats.quizzes   || 0);
      set(\'stat-downloads\', data.stats.downloads || 0);
    }
  } catch(e) { console.error(\'loadUsers error:\', e.message); }
}

function renderUserTable(users) {
  const tbody = document.querySelector(\'#users-table tbody\');
  if (!tbody) return;
  if (!users || !users.length) {
    tbody.innerHTML = \'<tr><td colspan="10" style="text-align:center;color:var(--muted);padding:2rem">No users found.</td></tr>\';
    return;
  }
  tbody.innerHTML = users.map(u => `
    <tr>
      <td>${u.id}</td>
      <td><strong>${u.name||\'—\'}</strong></td>
      <td style="font-size:0.78rem">${u.email||\'—\'}</td>
      <td>${u.country||\'—\'}</td>
      <td>${u.grade||\'—\'}</td>
      <td><strong style="color:var(--gold)">${u.points||0}</strong></td>
      <td>Lv.${u.level||1}</td>
      <td style="font-size:0.75rem;color:var(--muted)">${(u.created_at||\'\'). slice(0,10)}</td>
      <td>${u.is_admin ? \'<span class="badge badge-admin">👑 Admin</span>\' : \'<span class="badge badge-active">User</span>\'}</td>
      <td style="display:flex;gap:0.35rem">
        <button onclick="doToggleAdmin(${u.id})" style="background:rgba(255,107,43,0.1);color:var(--sun);border:1px solid rgba(255,107,43,0.3);border-radius:50px;padding:0.3rem 0.7rem;font-size:0.72rem;cursor:pointer">
          ${u.is_admin ? \'👤 Demote\' : \'⭐ Admin\'}
        </button>
        ${u.is_active ? `<button onclick="doBanUser(${u.id},\'${(u.name||\'user\').replace(/\'/g,\'\`)}" style="background:rgba(255,71,87,0.1);color:#FF6B7A;border:1px solid rgba(255,71,87,0.3);border-radius:50px;padding:0.3rem 0.7rem;font-size:0.72rem;cursor:pointer">🚫 Ban</button>` : \'\'}
      </td>
    </tr>`).join(\'\');
}

function filterUserTable(q) {
  document.querySelectorAll(\'#users-table tbody tr\').forEach(r =>
    r.style.display = r.innerText.toLowerCase().includes(q.toLowerCase()) ? \'\' : \'none\'
  );
}

async function doToggleAdmin(id) {
  if (!confirm(\'Toggle admin status?\')) return;
  try {
    const d = await api(\'GET\', `/admin/users.php?action=toggle_admin&id=${id}`);
    toast(d.is_admin ? \'✅ Promoted to Admin\' : \'✅ Admin removed\');
    loadUsers();
  } catch(e) { toast(e.message); }
}

async function doBanUser(id, name) {
  if (!confirm(`Ban ${name}?`)) return;
  try {
    await api(\'GET\', `/admin/users.php?action=deactivate&id=${id}`);
    toast(\'✅ User banned\');
    loadUsers();
  } catch(e) { toast(e.message); }
}

// ── BOOKS ─────────────────────────────────────────────────────────
async function loadBooks() {
  try {
    const data = await api(\'GET\', \'/books.php?action=stats\');
    const set = (id, val) => { const el = document.getElementById(id); if(el) el.textContent = val; };
    set(\'bs-total\',       data.total || 0);
    set(\'bs-top-country\', data.byCountry?.[0]?.country || \'—\');
    set(\'bs-top-subject\', data.bySubject?.[0]?.subject  || \'—\');
    set(\'stat-downloads\', data.total || 0);
    const top = document.getElementById(\'books-top-tbody\');
    if (top) top.innerHTML = (data.topBooks||[]).length
      ? data.topBooks.map(b=>`<tr><td>${b.title}</td><td>${b.country}</td><td>${b.subject}</td><td><strong style="color:var(--gold)">${b.cnt}</strong></td></tr>`).join(\'\')
      : \'<tr><td colspan="4" style="text-align:center;color:var(--muted);padding:1.5rem">No downloads yet.</td></tr>\';
    const rec = document.getElementById(\'books-recent-tbody\');
    if (rec) rec.innerHTML = (data.recent||[]).length
      ? data.recent.map(d=>`<tr><td style="font-size:0.82rem">${d.title}</td><td>${d.country}</td><td>${d.subject}</td><td>${d.user_name||\'Guest\'}</td><td style="font-size:0.75rem;color:var(--muted)">${(d.downloaded_at||\'\'). slice(0,16)}</td></tr>`).join(\'\')
      : \'<tr><td colspan="5" style="text-align:center;color:var(--muted);padding:1rem">No recent downloads.</td></tr>\';
  } catch(e) { console.error(\'loadBooks:\', e); }
}

function populateBookSelect(books) {
  const sel = document.getElementById(\'upload-bookkey\');
  if (!sel) return;
  sel.innerHTML = \'<option value="">— Choose a book —</option>\' +
    (books||[]).map(b => `<option value="${b.book_key||b.id}">${b.title}</option>`).join(\'\');
}

async function deleteBook(key) {
  if (!confirm(`Delete "${key}"?`)) return;
  try { await api(\'DELETE\', `/books.php?action=delete&id=${key}`); toast(\'Book removed.\'); loadBooks(); }
  catch(e) { toast(e.message); }
}

function quickUpload(key) {
  const sel = document.getElementById(\'upload-bookkey\');
  if (sel) sel.value = key;
  showSection(\'upload\');
}

// ── UPLOAD PDF ────────────────────────────────────────────────────
let selectedFile = null;
function onFileSelect(inp) {
  selectedFile = inp.files[0];
  const fn = document.getElementById(\'upload-filename\');
  if (fn) fn.textContent = selectedFile ? \'📄 \' + selectedFile.name : \'\';
}
document.addEventListener(\'DOMContentLoaded\', () => {
  const zone = document.getElementById(\'upload-zone\');
  if (!zone) return;
  zone.addEventListener(\'dragover\', e => { e.preventDefault(); zone.classList.add(\'drag\'); });
  zone.addEventListener(\'dragleave\', () => zone.classList.remove(\'drag\'));
  zone.addEventListener(\'drop\', e => {
    e.preventDefault(); zone.classList.remove(\'drag\');
    const f = e.dataTransfer.files[0];
    if (f) { selectedFile = f; const fn = document.getElementById(\'upload-filename\'); if(fn) fn.textContent = \'📄 \' + f.name; }
  });
});
async function doUpload() {
  const key = document.getElementById(\'upload-bookkey\')?.value;
  const sEl = document.getElementById(\'upload-success\');
  const eEl = document.getElementById(\'upload-error\');
  if (sEl) sEl.style.display = \'none\';
  if (eEl) eEl.style.display = \'none\';
  if (!key) { if(eEl){eEl.textContent=\'Select a book.\';eEl.style.display=\'block\';} return; }
  if (!selectedFile) { if(eEl){eEl.textContent=\'Select a PDF.\';eEl.style.display=\'block\';} return; }
  const fd = new FormData();
  fd.append(\'bookKey\', key); fd.append(\'file\', selectedFile);
  try {
    await api(\'POST\', \'/books.php?action=upload\', fd, true);
    if(sEl){sEl.textContent=\'✅ Uploaded!\';sEl.style.display=\'block\';}
    selectedFile = null; loadBooks();
  } catch(e) { if(eEl){eEl.textContent=e.message;eEl.style.display=\'block\';} }
}

// ── ADD BOOK ──────────────────────────────────────────────────────
async function doAddBook() {
  const sEl = document.getElementById(\'add-success\');
  const eEl = document.getElementById(\'add-error\');
  if(sEl) sEl.style.display=\'none\'; if(eEl) eEl.style.display=\'none\';
  const chapRaw = document.getElementById(\'nb-chapters\')?.value.trim();
  const chapters = chapRaw ? chapRaw.split(\'\\n\').filter(Boolean).map(line => {
    const [title, topicsStr] = line.split(\'|\');
    return { title: title?.trim(), topics: topicsStr ? topicsStr.split(\',\').map(t=>t.trim()) : [] };
  }) : [];
  const payload = {
    book_key:    document.getElementById(\'nb-key\')?.value.trim(),
    title:       document.getElementById(\'nb-title\')?.value.trim(),
    subject:     document.getElementById(\'nb-subject\')?.value,
    country:     document.getElementById(\'nb-country\')?.value,
    grade_range: document.getElementById(\'nb-grade\')?.value,
    year:        parseInt(document.getElementById(\'nb-year\')?.value)||null,
    publisher:   document.getElementById(\'nb-publisher\')?.value.trim(),
    curriculum:  document.getElementById(\'nb-curriculum\')?.value.trim(),
    icon:        document.getElementById(\'nb-icon\')?.value.trim()||\'📚\',
    color:       document.getElementById(\'nb-color\')?.value,
    chapters
  };
  try {
    await api(\'POST\', \'/books.php?action=create\', payload);
    if(sEl){sEl.textContent=`✅ Book "${payload.title}" created!`;sEl.style.display=\'block\';}
    loadBooks();
  } catch(e) { if(eEl){eEl.textContent=e.message;eEl.style.display=\'block\';} }
}

// ── TOAST ─────────────────────────────────────────────────────────
function toast(msg, type) {
  const el = document.createElement(\'div\');
  el.className = \'toast\' + (type === \'error\' ? \'\' : \' success\');
  el.textContent = msg;
  document.getElementById(\'toasts\').appendChild(el);
  setTimeout(() => el.remove(), 3500);
}
</script>
</body>
</html>';

// Combine HTML part with clean JS
$newAdmin = $htmlPart . $cleanJS;

// Fix the users table header to have Actions column
$newAdmin = str_replace(
    '<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Country</th><th>Grade</th><th>Points</th><th>Level</th><th>Joined</th><th>Role</th></tr></thead>',
    '<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Country</th><th>Grade</th><th>Points</th><th>Level</th><th>Joined</th><th>Role</th><th>Actions</th></tr></thead>',
    $newAdmin
);

file_put_contents($adminPath, $newAdmin);
echo "admin/index.php rewritten: ✅ (" . strlen($newAdmin) . " bytes)\n";

// ── VERIFY ────────────────────────────────────────────────────────
$v = file_get_contents($adminPath);
echo "\n=== VERIFICATION ===\n";
echo "API path is /api:            " . (strpos($v, "const API = '/api'") !== false ? "✅" : "❌") . "\n";
echo "loadUsers calls /admin/users.php: " . (strpos($v, '/admin/users.php') !== false ? "✅" : "❌") . "\n";
echo "Has doToggleAdmin:           " . (strpos($v, 'doToggleAdmin') !== false ? "✅" : "❌") . "\n";
echo "Has doBanUser:               " . (strpos($v, 'doBanUser') !== false ? "✅" : "❌") . "\n";
echo "Has Actions column:          " . (strpos($v, 'Actions</th>') !== false ? "✅" : "❌") . "\n";
echo "No old broken /auth.php me:  " . (strpos($v, 'action=me') === false ? "✅" : "❌") . "\n";
echo "No /api/admin conflict:      " . (strpos($v, '/api/admin/users') === false ? "✅" : "❌") . "\n";

echo "\n✅ Done! Delete install8.php then:\n";
echo "1. Hard refresh: Ctrl+Shift+R on admin panel\n";
echo "2. Click Users in the sidebar — all 7 users will appear!\n";
echo "</pre><p style='color:red;font-weight:bold;font-family:monospace;padding:20px'>⚠️ DELETE install8.php immediately!</p>";
