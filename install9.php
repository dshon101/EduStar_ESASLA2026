<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<pre style='font-family:monospace;font-size:13px;padding:20px;background:#0d0d1a;color:#f0f0ff'>";

$adminPath = __DIR__ . '/admin/index.php';
$current   = file_get_contents($adminPath);

// Extract HTML up to and including <script>
$scriptPos  = strrpos($current, '<script>');
$htmlPart   = substr($current, 0, $scriptPos + 8);

// Write the JS separately to avoid any escaping issues
$jsFile = __DIR__ . '/admin/admin.js';

file_put_contents($jsFile, <<<'JSEOF'
const API = '/api';
let adminToken = localStorage.getItem('edustar_admin_token');
let allUsers = [], allBooks = [];

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
    el.textContent = e.message;
    el.style.display = 'block';
  }
}

function adminLogout() {
  localStorage.removeItem('edustar_admin_token');
  location.reload();
}

function showApp() {
  document.getElementById('auth-guard').style.display = 'none';
  document.getElementById('admin-app').style.display  = 'block';
  loadDashboard();
  loadUsers();
  loadBooks();
}

window.addEventListener('load', () => { if (adminToken) showApp(); });

async function api(method, path, body, isForm) {
  const opts = { method, headers: { Authorization: 'Bearer ' + adminToken } };
  if (body && !isForm) { opts.headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(body); }
  else if (body && isForm) { opts.body = body; }
  const res  = await fetch(API + path, opts);
  const data = await res.json();
  if (!data.ok) throw new Error(data.error || 'API error');
  return data;
}

function showSection(id) {
  document.querySelectorAll('.admin-section').forEach(s => s.classList.remove('active'));
  document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
  document.getElementById('sec-' + id).classList.add('active');
  event.currentTarget.classList.add('active');
}

async function loadDashboard() {
  try {
    const lb = await api('GET', '/quiz.php?action=leaderboard');
    const medals = ['🥇','🥈','🥉'];
    document.querySelector('#lb-table tbody').innerHTML = lb.leaderboard.map((u,i) =>
      '<tr><td>' + (medals[i]||'#'+(i+1)) + '</td><td>' + u.name + '</td><td>' + u.country + '</td><td>' + u.points + '</td><td>Lv.' + u.level + '</td></tr>'
    ).join('');
  } catch(e) { console.error('loadDashboard:', e); }
}

async function loadUsers() {
  try {
    const data = await api('GET', '/admin/users.php?action=list');
    allUsers = data.users || [];
    renderUserTable(allUsers);
    if (data.stats) {
      var s = data.stats;
      var set = function(id, val) { var el = document.getElementById(id); if(el) el.textContent = val; };
      set('stat-users',     s.users     || 0);
      set('stat-quizzes',   s.quizzes   || 0);
      set('stat-downloads', s.downloads || 0);
    }
  } catch(e) { console.error('loadUsers error:', e.message); }
}

function renderUserTable(users) {
  var tbody = document.querySelector('#users-table tbody');
  if (!tbody) return;
  if (!users || !users.length) {
    tbody.innerHTML = '<tr><td colspan="10" style="text-align:center;color:var(--muted);padding:2rem">No users found.</td></tr>';
    return;
  }
  var rows = '';
  for (var i = 0; i < users.length; i++) {
    var u = users[i];
    var name    = (u.name    || '—').replace(/'/g, '&#39;');
    var email   = u.email   || '—';
    var country = u.country || '—';
    var grade   = u.grade   || '—';
    var joined  = (u.created_at || '').slice(0, 10);
    var roleBadge = u.is_admin
      ? '<span class="badge badge-admin">👑 Admin</span>'
      : '<span class="badge badge-active">User</span>';
    var adminBtn = '<button onclick="doToggleAdmin(' + u.id + ')" style="background:rgba(255,107,43,0.1);color:var(--sun);border:1px solid rgba(255,107,43,0.3);border-radius:50px;padding:0.3rem 0.7rem;font-size:0.72rem;cursor:pointer">' + (u.is_admin ? '👤 Demote' : '⭐ Admin') + '</button>';
    var banBtn = u.is_active
      ? '<button onclick="doBanUser(' + u.id + ',\'' + name + '\')" style="background:rgba(255,71,87,0.1);color:#FF6B7A;border:1px solid rgba(255,71,87,0.3);border-radius:50px;padding:0.3rem 0.7rem;font-size:0.72rem;cursor:pointer">🚫 Ban</button>'
      : '';
    rows += '<tr>'
      + '<td>' + u.id + '</td>'
      + '<td><strong>' + (u.name||'—') + '</strong></td>'
      + '<td style="font-size:0.78rem">' + email + '</td>'
      + '<td>' + country + '</td>'
      + '<td>' + grade + '</td>'
      + '<td><strong style="color:var(--gold)">' + (u.points||0) + '</strong></td>'
      + '<td>Lv.' + (u.level||1) + '</td>'
      + '<td style="font-size:0.75rem;color:var(--muted)">' + joined + '</td>'
      + '<td>' + roleBadge + '</td>'
      + '<td style="display:flex;gap:0.35rem">' + adminBtn + ' ' + banBtn + '</td>'
      + '</tr>';
  }
  tbody.innerHTML = rows;
}

function filterUserTable(q) {
  document.querySelectorAll('#users-table tbody tr').forEach(function(r) {
    r.style.display = r.innerText.toLowerCase().includes(q.toLowerCase()) ? '' : 'none';
  });
}

async function doToggleAdmin(id) {
  if (!confirm('Toggle admin status for this user?')) return;
  try {
    var d = await api('GET', '/admin/users.php?action=toggle_admin&id=' + id);
    toast(d.is_admin ? '✅ Promoted to Admin' : '✅ Admin removed');
    loadUsers();
  } catch(e) { toast(e.message); }
}

async function doBanUser(id, name) {
  if (!confirm('Ban ' + name + '? They will not be able to log in.')) return;
  try {
    await api('GET', '/admin/users.php?action=deactivate&id=' + id);
    toast('✅ User banned');
    loadUsers();
  } catch(e) { toast(e.message); }
}

async function loadBooks() {
  try {
    var data = await api('GET', '/books.php?action=stats');
    var set = function(id, val) { var el = document.getElementById(id); if(el) el.textContent = val; };
    set('bs-total',       data.total || 0);
    set('bs-top-country', (data.byCountry && data.byCountry[0]) ? data.byCountry[0].country : '—');
    set('bs-top-subject', (data.bySubject && data.bySubject[0]) ? data.bySubject[0].subject  : '—');
    set('stat-downloads', data.total || 0);
    var top = document.getElementById('books-top-tbody');
    if (top) {
      if (data.topBooks && data.topBooks.length) {
        top.innerHTML = data.topBooks.map(function(b) {
          return '<tr><td>' + b.title + '</td><td>' + b.country + '</td><td>' + b.subject + '</td><td><strong style="color:var(--gold)">' + b.cnt + '</strong></td></tr>';
        }).join('');
      } else {
        top.innerHTML = '<tr><td colspan="4" style="text-align:center;color:var(--muted);padding:1.5rem">No downloads yet.</td></tr>';
      }
    }
    var rec = document.getElementById('books-recent-tbody');
    if (rec) {
      if (data.recent && data.recent.length) {
        rec.innerHTML = data.recent.map(function(d) {
          return '<tr><td style="font-size:0.82rem">' + d.title + '</td><td>' + d.country + '</td><td>' + d.subject + '</td><td>' + (d.user_name||'Guest') + '</td><td style="font-size:0.75rem;color:var(--muted)">' + (d.downloaded_at||'').slice(0,16) + '</td></tr>';
        }).join('');
      } else {
        rec.innerHTML = '<tr><td colspan="5" style="text-align:center;color:var(--muted);padding:1rem">No recent downloads.</td></tr>';
      }
    }
  } catch(e) { console.error('loadBooks:', e); }
}

function populateBookSelect(books) {
  var sel = document.getElementById('upload-bookkey');
  if (!sel) return;
  sel.innerHTML = '<option value="">— Choose a book —</option>' +
    (books||[]).map(function(b) { return '<option value="' + (b.book_key||b.id) + '">' + b.title + '</option>'; }).join('');
}

async function deleteBook(key) {
  if (!confirm('Delete "' + key + '"?')) return;
  try { await api('DELETE', '/books.php?action=delete&id=' + key); toast('Book removed.'); loadBooks(); }
  catch(e) { toast(e.message); }
}

function quickUpload(key) {
  var sel = document.getElementById('upload-bookkey');
  if (sel) sel.value = key;
  showSection('upload');
}

var selectedFile = null;
function onFileSelect(inp) {
  selectedFile = inp.files[0];
  var fn = document.getElementById('upload-filename');
  if (fn) fn.textContent = selectedFile ? '📄 ' + selectedFile.name : '';
}

document.addEventListener('DOMContentLoaded', function() {
  var zone = document.getElementById('upload-zone');
  if (!zone) return;
  zone.addEventListener('dragover', function(e) { e.preventDefault(); zone.classList.add('drag'); });
  zone.addEventListener('dragleave', function() { zone.classList.remove('drag'); });
  zone.addEventListener('drop', function(e) {
    e.preventDefault(); zone.classList.remove('drag');
    var f = e.dataTransfer.files[0];
    if (f) { selectedFile = f; var fn = document.getElementById('upload-filename'); if(fn) fn.textContent = '📄 ' + f.name; }
  });
});

async function doUpload() {
  var key = document.getElementById('upload-bookkey') ? document.getElementById('upload-bookkey').value : '';
  var sEl = document.getElementById('upload-success');
  var eEl = document.getElementById('upload-error');
  if (sEl) sEl.style.display = 'none';
  if (eEl) eEl.style.display = 'none';
  if (!key) { if(eEl){eEl.textContent='Select a book.';eEl.style.display='block';} return; }
  if (!selectedFile) { if(eEl){eEl.textContent='Select a PDF.';eEl.style.display='block';} return; }
  var fd = new FormData();
  fd.append('bookKey', key); fd.append('file', selectedFile);
  try {
    await api('POST', '/books.php?action=upload', fd, true);
    if(sEl){sEl.textContent='✅ Uploaded!';sEl.style.display='block';}
    selectedFile = null; loadBooks();
  } catch(e) { if(eEl){eEl.textContent=e.message;eEl.style.display='block';} }
}

async function doAddBook() {
  var sEl = document.getElementById('add-success');
  var eEl = document.getElementById('add-error');
  if(sEl) sEl.style.display='none'; if(eEl) eEl.style.display='none';
  var chapRaw = document.getElementById('nb-chapters') ? document.getElementById('nb-chapters').value.trim() : '';
  var chapters = chapRaw ? chapRaw.split('\n').filter(Boolean).map(function(line) {
    var parts = line.split('|');
    return { title: parts[0] ? parts[0].trim() : '', topics: parts[1] ? parts[1].split(',').map(function(t){return t.trim();}) : [] };
  }) : [];
  var payload = {
    book_key:    document.getElementById('nb-key')        ? document.getElementById('nb-key').value.trim()        : '',
    title:       document.getElementById('nb-title')      ? document.getElementById('nb-title').value.trim()      : '',
    subject:     document.getElementById('nb-subject')    ? document.getElementById('nb-subject').value           : '',
    country:     document.getElementById('nb-country')    ? document.getElementById('nb-country').value           : '',
    grade_range: document.getElementById('nb-grade')      ? document.getElementById('nb-grade').value             : '',
    year:        document.getElementById('nb-year')       ? parseInt(document.getElementById('nb-year').value)||null : null,
    publisher:   document.getElementById('nb-publisher')  ? document.getElementById('nb-publisher').value.trim()  : '',
    curriculum:  document.getElementById('nb-curriculum') ? document.getElementById('nb-curriculum').value.trim() : '',
    icon:        document.getElementById('nb-icon')       ? document.getElementById('nb-icon').value.trim()||'📚' : '📚',
    color:       document.getElementById('nb-color')      ? document.getElementById('nb-color').value             : '#FF6B2B',
    chapters: chapters
  };
  try {
    await api('POST', '/books.php?action=create', payload);
    if(sEl){sEl.textContent='✅ Book "'+payload.title+'" created!';sEl.style.display='block';}
    loadBooks();
  } catch(e) { if(eEl){eEl.textContent=e.message;eEl.style.display='block';} }
}

function toast(msg, type) {
  var el = document.createElement('div');
  el.className = 'toast' + (type === 'error' ? '' : ' success');
  el.textContent = msg;
  var container = document.getElementById('toasts');
  if (container) container.appendChild(el);
  setTimeout(function() { if(el.parentNode) el.parentNode.removeChild(el); }, 3500);
}
JSEOF
);

echo "admin/admin.js written ✅\n";

// Now rebuild index.php to load admin.js externally
$newAdmin = $htmlPart . "\n</script>\n<script src=\"/admin/admin.js\"></script>\n</body>\n</html>";

// Fix users table header
$newAdmin = str_replace(
    '<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Country</th><th>Grade</th><th>Points</th><th>Level</th><th>Joined</th><th>Role</th></tr></thead>',
    '<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Country</th><th>Grade</th><th>Points</th><th>Level</th><th>Joined</th><th>Role</th><th>Actions</th></tr></thead>',
    $newAdmin
);

// Remove any leftover </script></body></html> that was in htmlPart
$newAdmin = str_replace("</script>\n</body>\n</html>\n</script>", "</script>", $newAdmin);

file_put_contents($adminPath, $newAdmin);
echo "admin/index.php rebuilt ✅ (" . strlen($newAdmin) . " bytes)\n";

// Verify admin.js syntax by checking for common issues
$js = file_get_contents($jsFile);
echo "\n=== VERIFICATION ===\n";
echo "admin.js exists:             " . (file_exists($jsFile) ? "✅" : "❌") . "\n";
echo "Has adminLogin:              " . (strpos($js, 'function adminLogin') !== false ? "✅" : "❌") . "\n";
echo "Has loadUsers:               " . (strpos($js, 'async function loadUsers') !== false ? "✅" : "❌") . "\n";
echo "Has renderUserTable:         " . (strpos($js, 'function renderUserTable') !== false ? "✅" : "❌") . "\n";
echo "Has doToggleAdmin:           " . (strpos($js, 'async function doToggleAdmin') !== false ? "✅" : "❌") . "\n";
echo "Calls /admin/users.php:      " . (strpos($js, '/admin/users.php') !== false ? "✅" : "❌") . "\n";
echo "No backtick in btn onclick:  " . (strpos($js, 'onclick=`') === false ? "✅" : "❌") . "\n";
echo "index.php loads admin.js:    " . (strpos(file_get_contents($adminPath), 'admin.js') !== false ? "✅" : "❌") . "\n";

echo "\n✅ Done! Delete install9.php then:\n";
echo "1. Ctrl+Shift+R hard refresh on admin\n";
echo "2. Log in — Users section will show all 7 users!\n";
echo "</pre><p style='color:red;font-weight:bold;font-family:monospace;padding:20px'>⚠️ DELETE install9.php immediately!</p>";
