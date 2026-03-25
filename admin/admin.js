// ================================================================
// EduStar Admin JS v2 — Robust error handling
// ================================================================
const API = '/api';
let adminToken = localStorage.getItem('edustar_admin_token');
let allUsers   = [];
let logSearchTimer = null;
let currentLogPage = 1;
let currentTicketId = null;

// ── AUTH ──────────────────────────────────────────────────────────
async function adminLogin() {
  var email = document.getElementById('adm-email').value.trim();
  var pw    = document.getElementById('adm-pw').value;
  var errEl = document.getElementById('adm-err');
  errEl.classList.remove('show');
  try {
    // Login does NOT need a token — use direct fetch here
    var res = await fetch(API + '/auth.php?action=login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email: email, password: pw })
    });
    var data = await res.json();
    if (!data.ok) throw new Error(data.error || 'Login failed');
    if (!data.user.isAdmin) throw new Error('This account does not have admin access.');
    localStorage.setItem('edustar_admin_token', data.token);
    adminToken = data.token;
    window._adminVerifyDone = true; // signal that a fresh login happened
    showApp();
  } catch(e) {
    errEl.textContent = '❌ ' + e.message;
    errEl.classList.add('show');
  }
}

function adminLogout() {
  localStorage.removeItem('edustar_admin_token');
  adminToken = null;
  location.reload();
}

function showApp() {
  document.getElementById('auth-guard').style.display = 'none';
  document.getElementById('admin-app').style.display  = 'block';
  // Load independently so one failure never blocks another
  loadUsers();
  loadDashboardStats();
  loadBooks();
}

window.addEventListener('load', function() {
  if (adminToken) {
    // Verify token is still valid before showing app
    verifyAndShow();
  }
});

async function verifyAndShow() {
  window._adminVerifyDone = false;
  var tokenAtStart = adminToken; // snapshot the token we're verifying
  try {
    var res = await fetch(API + '/auth.php?action=me', {
      headers: { 'Authorization': 'Bearer ' + tokenAtStart, 'X-Auth-Token': tokenAtStart }
    });
    var data = await res.json();
    if (!data.ok || !data.user.isAdmin) {
      // Only clear if the user hasn't logged in with a new token since we started
      if (adminToken === tokenAtStart) {
        localStorage.removeItem('edustar_admin_token');
        adminToken = null;
      }
      return;
    }
    // Only show app if a fresh login hasn't already taken over
    if (!window._adminVerifyDone && adminToken === tokenAtStart) showApp();
  } catch(e) {
    if (!window._adminVerifyDone && adminToken === tokenAtStart) showApp();
  }
}

// ── API HELPER ────────────────────────────────────────────────────
async function api(method, path, body, isForm) {
  var opts = { method: method, headers: {} };
  if (adminToken) {
    opts.headers['Authorization'] = 'Bearer ' + adminToken;
    opts.headers['X-Auth-Token']  = adminToken; // fallback for LiteSpeed servers
  }
  if (body && !isForm) {
    opts.headers['Content-Type'] = 'application/json';
    opts.body = JSON.stringify(body);
  } else if (body && isForm) {
    opts.body = body;
  }
  var res  = await fetch(API + path, opts);
  var text = await res.text();
  var data;
  try { data = JSON.parse(text); }
  catch(e) { throw new Error('Server returned non-JSON response (HTTP ' + res.status + '). Check server error logs.'); }
  if (!data.ok) {
    if (res.status === 401) {
      // Only force re-login if this IS the current token (not a stale parallel call)
      var currentToken = localStorage.getItem('edustar_admin_token');
      if (!currentToken || opts.headers['Authorization'] === 'Bearer ' + currentToken) {
        localStorage.removeItem('edustar_admin_token');
        adminToken = null;
        document.getElementById('admin-app').style.display  = 'none';
        document.getElementById('auth-guard').style.display = 'flex';
        var errEl = document.getElementById('adm-err');
        if (errEl) { errEl.textContent = '⚠️ Session expired. Please log in again.'; errEl.classList.add('show'); }
      }
      throw new Error('Session expired');
    }
    throw new Error(data.error || 'API error');
  }
  return data;
}

function set(id, val) {
  var el = document.getElementById(id);
  if (el) el.textContent = val;
}

function showTableError(tbodyId, colspan, msg) {
  var tbody = document.getElementById(tbodyId);
  if (tbody) tbody.innerHTML = '<tr><td colspan="' + colspan + '" style="text-align:center;color:#FF6B7A;padding:2rem">❌ ' + esc(msg) + '</td></tr>';
}

function showTableEmpty(tbodyId, colspan, msg) {
  var tbody = document.getElementById(tbodyId);
  if (tbody) tbody.innerHTML = '<tr><td colspan="' + colspan + '" style="text-align:center;color:var(--muted);padding:2rem">' + msg + '</td></tr>';
}

// ── SECTION NAVIGATION ────────────────────────────────────────────
function showSection(id) {
  document.querySelectorAll('.admin-section').forEach(function(s) { s.classList.remove('active'); });
  document.querySelectorAll('.sidebar-link').forEach(function(l) { l.classList.remove('active'); });
  var sec = document.getElementById('sec-' + id);
  if (sec) sec.classList.add('active');
  if (event && event.currentTarget) event.currentTarget.classList.add('active');
  if (id === 'tickets')   loadTickets('');
  if (id === 'community') loadCommunityPosts();
  if (id === 'logs')      loadLogs(1);
  if (id === 'books')     loadBooks();
  if (id === 'users')     loadUsers();
}

// ── DASHBOARD STATS (separate from users) ─────────────────────────
async function loadDashboardStats() {
  try {
    var ud = await api('GET', '/admin/users.php?action=list');
    if (ud.stats) {
      set('stat-users',        ud.stats.users        || 0);
      set('stat-quizzes',      ud.stats.quizzes      || 0);
      set('stat-lessons',      ud.stats.lessons      || 0);
      set('stat-open-tickets', ud.stats.open_tickets || 0);
      set('stat-posts',        ud.stats.posts        || 0);
      if (ud.stats.open_tickets > 0) {
        var badge = document.getElementById('open-ticket-badge');
        if (badge) { badge.textContent = ud.stats.open_tickets; badge.style.display = 'inline'; }
      }
    }
  } catch(e) { console.warn('Stats failed:', e.message); }

  // Leaderboard — fully isolated
  try {
    var lb = await api('GET', '/quiz.php?action=leaderboard');
    var medals = ['🥇','🥈','🥉'];
    var lbEl = document.querySelector('#lb-table tbody');
    if (lbEl) {
      if (lb.leaderboard && lb.leaderboard.length) {
        lbEl.innerHTML = lb.leaderboard.map(function(u, i) {
          return '<tr><td>' + (medals[i]||'#'+(i+1)) + '</td>'
            + '<td><strong>' + esc(u.name) + '</strong></td>'
            + '<td>' + (u.country||'—') + '</td>'
            + '<td style="color:var(--gold)">' + (u.points||0) + '</td>'
            + '<td>Lv.' + (u.level||1) + '</td></tr>';
        }).join('');
      } else {
        lbEl.innerHTML = '<tr><td colspan="5" style="text-align:center;color:var(--muted);padding:1.5rem">No students yet.</td></tr>';
      }
    }
  } catch(e) { console.warn('Leaderboard failed:', e.message); }

  // Book stats — fully isolated
  try {
    var bd = await api('GET', '/books.php?action=stats');
    set('stat-downloads', bd.total || 0);
  } catch(e) {}
  try {
    var bl = await api('GET', '/books.php?action=list');
    set('stat-books', (bl.books || []).length);
  } catch(e) {}
}

// ── USERS ─────────────────────────────────────────────────────────
async function loadUsers() {
  var tbody = document.querySelector('#users-table tbody');
  if (tbody) tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;color:var(--muted);padding:2rem">⏳ Loading users...</td></tr>';
  try {
    var d = await api('GET', '/admin/users.php?action=list');
    allUsers = d.users || [];
    renderUserTable(allUsers);
    // Also update stat cards if on dashboard
    if (d.stats) {
      set('stat-users',        d.stats.users        || 0);
      set('stat-open-tickets', d.stats.open_tickets || 0);
      set('stat-posts',        d.stats.posts        || 0);
    }
  } catch(e) {
    if (tbody) tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;color:#FF6B7A;padding:2rem">❌ ' + esc(e.message) + '<br><br><button onclick="loadUsers()" style="background:rgba(255,107,43,0.15);color:var(--sun);border:1px solid rgba(255,107,43,0.3);border-radius:50px;padding:0.4rem 1rem;cursor:pointer;font-family:\'DM Sans\',sans-serif">🔄 Retry</button></td></tr>';
  }
}

function renderUserTable(users) {
  var tbody = document.querySelector('#users-table tbody');
  if (!tbody) return;
  if (!users || !users.length) {
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;color:var(--muted);padding:2rem">No users found.</td></tr>';
    return;
  }
  tbody.innerHTML = users.map(function(u) {
    var joined   = (u.created_at || '').slice(0, 10);
    var status   = u.is_active
      ? '<span class="badge badge-active">Active</span>'
      : '<span class="badge badge-banned">Banned</span>';
    var adminBtn = '<button class="action-btn ab-warn" onclick="doToggleAdmin(' + u.id + ')">'
      + (u.is_admin ? '👤 Demote' : '⭐ Make Admin') + '</button>';
    var banBtn   = u.is_active
      ? '<button class="action-btn ab-ban" onclick="doBanUser(' + u.id + ',\'' + esc(u.name) + '\')" style="margin-left:4px">🚫 Ban</button>'
      : '<button class="action-btn ab-green" onclick="doUnbanUser(' + u.id + ')" style="margin-left:4px">✅ Unban</button>';
    var delBtn   = '<button class="action-btn ab-ban" onclick="doDeleteUser(' + u.id + ',\'' + esc(u.name) + '\')" style="margin-left:4px" title="Delete permanently">🗑️</button>';
    return '<tr>'
      + '<td>' + u.id + '</td>'
      + '<td><strong>' + esc(u.name||'—') + '</strong>' + (u.is_admin ? ' <span style="color:var(--sun);font-size:0.7rem">ADMIN</span>' : '') + '</td>'
      + '<td style="font-size:0.78rem">' + esc(u.email||'—') + '</td>'
      + '<td>' + (u.country||'—') + '</td>'
      + '<td>' + esc(u.grade||'—') + '</td>'
      + '<td style="color:var(--gold);font-weight:600">' + (u.points||0) + '</td>'
      + '<td style="font-size:0.75rem;color:var(--muted)">' + joined + '</td>'
      + '<td>' + status + '</td>'
      + '<td style="display:flex;gap:3px;flex-wrap:wrap;align-items:center">' + adminBtn + banBtn + delBtn + '</td>'
      + '</tr>';
  }).join('');
}

function searchUsers(q) {
  if (!q.trim()) { renderUserTable(allUsers); return; }
  var fl = q.toLowerCase();
  renderUserTable(allUsers.filter(function(u) {
    return (u.name||'').toLowerCase().includes(fl)
        || (u.email||'').toLowerCase().includes(fl)
        || (u.country||'').toLowerCase().includes(fl)
        || (u.grade||'').toLowerCase().includes(fl);
  }));
}

async function doToggleAdmin(id) {
  if (!confirm('Toggle admin status for this user?')) return;
  try {
    var d = await api('GET', '/admin/users.php?action=toggle_admin&id=' + id);
    toast(d.is_admin ? '✅ Promoted to Admin' : '✅ Admin removed');
    loadUsers();
  } catch(e) { toast('❌ ' + e.message, 'error'); }
}

async function doBanUser(id, name) {
  if (!confirm('Ban ' + name + '? They will not be able to log in.')) return;
  try {
    await api('GET', '/admin/users.php?action=deactivate&id=' + id);
    toast('✅ User banned');
    loadUsers();
  } catch(e) { toast('❌ ' + e.message, 'error'); }
}

async function doUnbanUser(id) {
  try {
    await api('GET', '/admin/users.php?action=activate&id=' + id);
    toast('✅ User unbanned');
    loadUsers();
  } catch(e) { toast('❌ ' + e.message, 'error'); }
}

async function doDeleteUser(id, name) {
  if (!confirm('PERMANENTLY delete ' + name + '? This cannot be undone.')) return;
  if (!confirm('Final confirmation — delete ' + name + ' and ALL their data?')) return;
  try {
    await api('GET', '/admin/users.php?action=delete&id=' + id);
    toast('✅ User deleted');
    loadUsers();
    loadDashboardStats();
  } catch(e) { toast('❌ ' + e.message, 'error'); }
}

// ── BOOKS ─────────────────────────────────────────────────────────
async function loadBooks() {
  try {
    var d = await api('GET', '/books.php?action=stats');
    set('bs-total',       d.total || 0);
    set('bs-top-country', (d.byCountry && d.byCountry[0]) ? d.byCountry[0].country : '—');
    set('bs-top-subject', (d.bySubject && d.bySubject[0]) ? d.bySubject[0].subject  : '—');
    set('stat-downloads', d.total || 0);
    var top = document.getElementById('books-top-tbody');
    if (top) {
      top.innerHTML = (d.topBooks && d.topBooks.length)
        ? d.topBooks.map(function(b) {
            return '<tr><td>' + esc(b.title) + '</td><td>' + esc(b.country) + '</td><td>' + esc(b.subject) + '</td><td style="color:var(--gold);font-weight:600">' + b.cnt + '</td></tr>';
          }).join('')
        : '<tr><td colspan="4" style="text-align:center;color:var(--muted);padding:1.5rem">No downloads yet.</td></tr>';
    }
    var rec = document.getElementById('books-recent-tbody');
    if (rec) {
      rec.innerHTML = (d.recent && d.recent.length)
        ? d.recent.map(function(r) {
            return '<tr><td style="font-size:0.82rem">' + esc(r.title) + '</td><td>' + esc(r.country) + '</td><td>' + esc(r.subject) + '</td><td>' + esc(r.user_name||'Guest') + '</td><td style="font-size:0.75rem;color:var(--muted)">' + (r.downloaded_at||'').slice(0,16) + '</td></tr>';
          }).join('')
        : '<tr><td colspan="5" style="text-align:center;color:var(--muted);padding:1rem">No recent downloads.</td></tr>';
    }
  } catch(e) { console.warn('loadBooks:', e.message); }

  try {
    var bl = await api('GET', '/books.php?action=list');
    populateBookSelect(bl.books || []);
    set('stat-books', (bl.books || []).length);
  } catch(e) {}
}

function populateBookSelect(books) {
  var sel = document.getElementById('upload-bookkey');
  if (!sel) return;
  sel.innerHTML = '<option value="">— Choose a book —</option>'
    + books.map(function(b) {
        return '<option value="' + esc(b.book_key||b.id) + '">' + esc(b.title) + '</option>';
      }).join('');
}

var selectedFile = null;
function onFileSelect(inp) {
  selectedFile = inp.files[0];
  var fn = document.getElementById('upload-filename');
  if (fn) fn.textContent = selectedFile ? '📄 ' + selectedFile.name : '';
}

document.addEventListener('DOMContentLoaded', function() {
  var zone = document.getElementById('upload-zone');
  if (zone) {
    zone.addEventListener('dragover',  function(e) { e.preventDefault(); zone.classList.add('drag'); });
    zone.addEventListener('dragleave', function()  { zone.classList.remove('drag'); });
    zone.addEventListener('drop',      function(e) {
      e.preventDefault(); zone.classList.remove('drag');
      var f = e.dataTransfer.files[0];
      if (f) { selectedFile = f; var fn = document.getElementById('upload-filename'); if(fn) fn.textContent = '📄 ' + f.name; }
    });
  }
  var ls = document.getElementById('log-search');
  if (ls) ls.addEventListener('input', debounceLogSearch);
  var lf = document.getElementById('log-action-filter');
  if (lf) lf.addEventListener('change', function() { loadLogs(1); });
});

async function doUpload() {
  var key = document.getElementById('upload-bookkey') ? document.getElementById('upload-bookkey').value : '';
  var sEl = document.getElementById('upload-success');
  var eEl = document.getElementById('upload-error');
  if (sEl) sEl.classList.remove('show');
  if (eEl) eEl.classList.remove('show');
  if (!key)          { if(eEl){eEl.textContent='Select a book.';eEl.classList.add('show');} return; }
  if (!selectedFile) { if(eEl){eEl.textContent='Select a PDF.';eEl.classList.add('show');} return; }
  var fd = new FormData();
  fd.append('bookKey', key);
  fd.append('file', selectedFile);
  try {
    await api('POST', '/books.php?action=upload', fd, true);
    if(sEl){sEl.textContent='✅ Uploaded!';sEl.classList.add('show');}
    selectedFile = null;
    loadBooks();
  } catch(e) { if(eEl){eEl.textContent=e.message;eEl.classList.add('show');} }
}

async function doAddBook() {
  var sEl = document.getElementById('add-success');
  var eEl = document.getElementById('add-error');
  if(sEl) sEl.classList.remove('show');
  if(eEl) eEl.classList.remove('show');
  function gv(id) { var el=document.getElementById(id); return el ? el.value.trim() : ''; }
  var chapRaw  = gv('nb-chapters');
  var chapters = chapRaw ? chapRaw.split('\n').filter(Boolean).map(function(l) {
    var p = l.split('|');
    return { title: p[0]?p[0].trim():'', topics: p[1]?p[1].split(',').map(function(t){return t.trim();}):[] };
  }) : [];
  var payload = {
    book_key:    gv('nb-key'),   title:       gv('nb-title'),
    subject:     gv('nb-subject'), country:   gv('nb-country'),
    grade_range: gv('nb-grade'), year:        parseInt(gv('nb-year'))||null,
    publisher:   gv('nb-publisher'), curriculum: gv('nb-curriculum'),
    icon:        gv('nb-icon')||'📚', color:   gv('nb-color'),
    chapters: chapters
  };
  try {
    await api('POST', '/books.php?action=create', payload);
    if(sEl){sEl.textContent='✅ Book "'+payload.title+'" created!';sEl.classList.add('show');}
    loadBooks();
  } catch(e) { if(eEl){eEl.textContent=e.message;eEl.classList.add('show');} }
}

// ── SUPPORT TICKETS ───────────────────────────────────────────────
async function loadTickets(status) {
  var tbody = document.getElementById('tickets-tbody');
  if (!tbody) return;
  tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--muted);padding:2rem">⏳ Loading...</td></tr>';
  try {
    var url = '/support.php?action=admin_list' + (status ? '&status=' + status : '');
    var d   = await api('GET', url);
    var tickets = d.tickets || [];
    if (!tickets.length) {
      tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--muted);padding:2rem">No tickets found.</td></tr>';
      return;
    }
    tbody.innerHTML = tickets.map(function(t) {
      var badge     = '<span class="badge status-' + t.status + '">' + statusLabel(t.status) + '</span>';
      var replyBtn  = '<button class="action-btn ab-warn" onclick="openTicketModal(' + t.id + ')">💬 Reply</button>';
      var deleteBtn = '<button class="action-btn ab-ban" onclick="adminDeleteTicket(' + t.id + ')" style="margin-left:3px" title="Delete ticket">🗑️</button>';
      return '<tr id="ticket-row-' + t.id + '">'
        + '<td>#' + t.id + '</td>'
        + '<td>' + esc(t.name) + '</td>'
        + '<td style="font-size:0.78rem">' + esc(t.email) + '</td>'
        + '<td style="max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + esc(t.subject) + '</td>'
        + '<td>' + esc(t.category) + '</td>'
        + '<td>' + badge + '</td>'
        + '<td style="font-size:0.75rem;color:var(--muted)">' + (t.created_at||'').slice(0,10) + '</td>'
        + '<td style="display:flex;gap:3px">' + replyBtn + deleteBtn + '</td>'
        + '</tr>';
    }).join('');
  } catch(e) {
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#FF6B7A;padding:2rem">❌ ' + esc(e.message) + '</td></tr>';
  }
}

function statusLabel(s) {
  var map = { open:'🔴 Open', in_progress:'🟡 In Progress', resolved:'🟢 Resolved', closed:'⚫ Closed' };
  return map[s] || s;
}

async function openTicketModal(id) {
  currentTicketId = id;
  document.getElementById('modal-ticket-id').textContent = '#' + id;
  document.getElementById('reply-body').value = '';
  document.getElementById('reply-err').classList.remove('show');
  document.getElementById('modal-ticket').classList.add('open');
  try {
    var d = await api('GET', '/support.php?action=admin_list');
    var t = (d.tickets||[]).find(function(x){ return x.id == id; });
    if (t) {
      document.getElementById('modal-ticket-detail').innerHTML =
        '<strong>' + esc(t.subject) + '</strong><br>'
        + '<span style="font-size:0.75rem;color:var(--muted)">'
        + esc(t.name) + ' &lt;' + esc(t.email) + '&gt; — ' + (t.created_at||'').slice(0,16)
        + '</span><br><br>'
        + '<div style="white-space:pre-wrap;font-size:0.83rem">' + esc(t.message) + '</div>'
        + (t.admin_reply
          ? '<div style="margin-top:0.8rem;padding-top:0.8rem;border-top:1px solid rgba(255,255,255,0.08);font-size:0.8rem;color:var(--sun)"><strong>Previous reply:</strong><br>' + esc(t.admin_reply) + '</div>'
          : '');
    }
  } catch(e) {}
}

function closeTicketModal() {
  document.getElementById('modal-ticket').classList.remove('open');
  currentTicketId = null;
}

async function adminDeleteTicket(id) {
  if (!confirm('Permanently delete ticket #' + id + '? This cannot be undone.')) return;
  try {
    var res  = await fetch(API + '/support.php?action=admin_delete&id=' + id, {
      method: 'DELETE',
      headers: adminToken ? { 'Authorization': 'Bearer ' + adminToken, 'X-Auth-Token': adminToken } : {}
    });
    var data = await res.json();
    if (!data.ok) throw new Error(data.error || 'Delete failed');
    var row = document.getElementById('ticket-row-' + id);
    if (row) row.remove();
    toast('✅ Ticket deleted');
    loadDashboardStats();
  } catch(e) { toast('❌ ' + e.message, 'error'); }
}

async function sendTicketReply() {
  var reply  = document.getElementById('reply-body').value.trim();
  var status = document.getElementById('reply-status').value;
  var errEl  = document.getElementById('reply-err');
  if (!reply) { errEl.textContent='⚠️ Reply cannot be empty.'; errEl.classList.add('show'); return; }
  try {
    await api('POST', '/support.php?action=admin_reply', { id: currentTicketId, reply: reply, status: status });
    closeTicketModal();
    toast('✅ Reply sent!');
    loadTickets('');
    loadDashboardStats();
  } catch(e) { errEl.textContent='❌ ' + e.message; errEl.classList.add('show'); }
}

// ── COMMUNITY POSTS ───────────────────────────────────────────────
async function loadCommunityPosts() {
  var tbody = document.getElementById('comm-tbody');
  if (!tbody) return;
  tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--muted);padding:2rem">⏳ Loading...</td></tr>';
  try {
    var res  = await fetch(API + '/community.php?action=list&page=1', {
      headers: adminToken ? { 'Authorization': 'Bearer ' + adminToken } : {}
    });
    var text = await res.text();
    var d    = JSON.parse(text);
    var posts = d.posts || [];
    if (!posts.length) {
      tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:var(--muted);padding:2rem">No community posts yet.</td></tr>';
      return;
    }
    tbody.innerHTML = posts.map(function(p) {
      var removeBtn = '<button class="action-btn ab-ban" onclick="adminDeletePost(' + p.id + ')">🗑️ Remove</button>';
      return '<tr>'
        + '<td>#' + p.id + '</td>'
        + '<td style="max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"><strong>' + esc(p.title) + '</strong></td>'
        + '<td>' + esc(p.author_name) + '</td>'
        + '<td>' + esc(p.category) + '</td>'
        + '<td>❤️ ' + (p.likes||0) + '</td>'
        + '<td>💬 ' + (p.reply_count||0) + '</td>'
        + '<td style="font-size:0.75rem;color:var(--muted)">' + (p.created_at||'').slice(0,10) + '</td>'
        + '<td>' + removeBtn + '</td>'
        + '</tr>';
    }).join('');
  } catch(e) {
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;color:#FF6B7A;padding:2rem">❌ ' + esc(e.message) + '</td></tr>';
  }
}

async function adminDeletePost(id) {
  if (!confirm('Remove this post from the community?')) return;
  try {
    await fetch(API + '/community.php?action=delete_post&id=' + id, {
      method: 'DELETE',
      headers: adminToken ? { 'Authorization': 'Bearer ' + adminToken } : {}
    });
    toast('✅ Post removed');
    loadCommunityPosts();
  } catch(e) { toast('❌ ' + e.message, 'error'); }
}

// ── SYSTEM LOGS ───────────────────────────────────────────────────
async function loadLogs(page) {
  page = page || 1;
  currentLogPage = page;
  var search = (document.getElementById('log-search')||{value:''}).value.trim();
  var filter = (document.getElementById('log-action-filter')||{value:''}).value;
  var tbody  = document.getElementById('logs-tbody');
  if (!tbody) return;
  tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:var(--muted);padding:2rem">⏳ Loading...</td></tr>';

  var url = '/admin/logs.php?action=list&page=' + page;
  if (search) url += '&search=' + encodeURIComponent(search);
  if (filter) url += '&action_filter=' + encodeURIComponent(filter);

  try {
    var d    = await api('GET', url);
    var logs = d.logs || [];

    // Populate action filter dropdown (first load only)
    var filterEl = document.getElementById('log-action-filter');
    if (filterEl && d.actions && filterEl.options.length <= 1) {
      d.actions.forEach(function(a) {
        var opt = document.createElement('option');
        opt.value = a; opt.textContent = a;
        filterEl.appendChild(opt);
      });
      if (filter) filterEl.value = filter;
    }

    set('log-count', d.total + ' total entries');

    if (!logs.length) {
      tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:var(--muted);padding:2rem">No logs found. Logs will appear here as users interact with the app.</td></tr>';
      document.getElementById('logs-pagination').innerHTML = '';
      return;
    }

    tbody.innerHTML = logs.map(function(l) {
      return '<tr>'
        + '<td style="font-size:0.75rem;color:var(--muted);white-space:nowrap">' + (l.created_at||'').slice(0,16) + '</td>'
        + '<td style="font-size:0.82rem">' + esc(l.actor_name||'System')
          + (l.actor_id ? ' <span style="color:var(--muted);font-size:0.68rem">#'+l.actor_id+'</span>' : '') + '</td>'
        + '<td><span class="log-action">' + esc(l.action) + '</span></td>'
        + '<td style="font-size:0.78rem;color:var(--muted)">'
          + (l.target_type ? esc(l.target_type) + ' #' + esc(l.target_id||'') : '—') + '</td>'
        + '<td style="font-size:0.78rem;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="' + esc(l.detail||'') + '">'
          + esc(l.detail||'—') + '</td>'
        + '</tr>';
    }).join('');

    // Pagination
    var pages = Math.ceil(d.total / 100);
    var pagEl = document.getElementById('logs-pagination');
    if (pagEl) {
      if (pages > 1) {
        var html = '';
        var start = Math.max(1, page-3);
        var end   = Math.min(pages, page+3);
        if (start > 1) html += '<button style="'+pagStyle(false)+'" onclick="loadLogs(1)">1</button><span style="color:var(--muted)">…</span>';
        for (var i=start; i<=end; i++) {
          html += '<button style="'+pagStyle(i===page)+'" onclick="loadLogs('+i+')">' + i + '</button>';
        }
        if (end < pages) html += '<span style="color:var(--muted)">…</span><button style="'+pagStyle(false)+'" onclick="loadLogs('+pages+')">'+pages+'</button>';
        pagEl.innerHTML = html;
      } else {
        pagEl.innerHTML = '';
      }
    }
  } catch(e) {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;color:#FF6B7A;padding:2rem">❌ ' + esc(e.message)
      + '<br><br><button onclick="loadLogs(1)" style="background:rgba(255,107,43,0.15);color:var(--sun);border:1px solid rgba(255,107,43,0.3);border-radius:50px;padding:0.4rem 1rem;cursor:pointer;font-family:\'DM Sans\',sans-serif">🔄 Retry</button></td></tr>';
  }
}

function pagStyle(active) {
  return 'width:32px;height:32px;border-radius:50%;border:1px solid '+(active?'rgba(255,107,43,0.4)':'var(--border)')
    +';background:'+(active?'rgba(255,107,43,0.12)':'transparent')
    +';color:'+(active?'var(--sun)':'var(--text)')
    +';font-size:0.82rem;cursor:pointer;font-family:\'DM Sans\',sans-serif';
}

function debounceLogSearch() {
  clearTimeout(logSearchTimer);
  logSearchTimer = setTimeout(function() { loadLogs(1); }, 400);
}

// ── EXPORT LOGS ───────────────────────────────────────────────────
async function exportLogs(format) {
  if (format === 'csv') {
    try {
      var res  = await fetch(API + '/admin/logs.php?action=export&format=csv', {
        headers: { 'Authorization': 'Bearer ' + adminToken }
      });
      var text = await res.text();
      var blob = new Blob([text], { type: 'text/csv;charset=utf-8;' });
      var url  = URL.createObjectURL(blob);
      var a    = document.createElement('a');
      a.href   = url;
      a.download = 'edustar_logs_' + new Date().toISOString().slice(0,10) + '.csv';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
      toast('✅ CSV downloaded');
    } catch(e) { toast('❌ Export failed: ' + e.message, 'error'); }
    return;
  }

  if (format === 'excel') {
    try {
      var d    = await api('GET', '/admin/logs.php?action=export');
      var logs = d.logs || [];
      var header = ['ID','Actor ID','Actor Name','Action','Target Type','Target ID','Detail','IP','Date/Time'];
      var rows   = logs.map(function(l) {
        return [l.id,l.actor_id||'',l.actor_name||'',l.action||'',l.target_type||'',l.target_id||'',l.detail||'',l.ip||'',l.created_at||''];
      });
      var xls = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">'
        + '<head></head><body><table border="1">'
        + '<tr>' + header.map(function(h){ return '<th style="background:#0D0D1A;color:#FF6B2B;font-weight:bold">'+h+'</th>'; }).join('') + '</tr>'
        + rows.map(function(r){ return '<tr>'+r.map(function(c){ return '<td>'+esc(String(c))+'</td>'; }).join('')+'</tr>'; }).join('')
        + '</table></body></html>';
      var blob = new Blob([xls], { type: 'application/vnd.ms-excel;charset=utf-8;' });
      var url  = URL.createObjectURL(blob);
      var a    = document.createElement('a');
      a.href   = url;
      a.download = 'edustar_logs_' + new Date().toISOString().slice(0,10) + '.xls';
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      URL.revokeObjectURL(url);
      toast('✅ Excel downloaded');
    } catch(e) { toast('❌ Export failed: ' + e.message, 'error'); }
  }
}

async function clearOldLogs() {
  if (!confirm('Clear all system logs older than 90 days?')) return;
  try {
    await api('GET', '/admin/logs.php?action=clear');
    toast('✅ Old logs cleared');
    loadLogs(1);
  } catch(e) { toast('❌ ' + e.message, 'error'); }
}

// ── HELPERS ───────────────────────────────────────────────────────
function esc(s) {
  return String(s||'')
    .replace(/&/g,'&amp;')
    .replace(/</g,'&lt;')
    .replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;');
}

function toast(msg, type) {
  var el = document.createElement('div');
  el.className = 'toast ' + (type === 'error' ? 'error' : 'success');
  el.textContent = msg;
  var c = document.getElementById('toasts');
  if (c) c.appendChild(el);
  requestAnimationFrame(function() { el.classList.add('show'); });
  setTimeout(function() { if (el.parentNode) el.parentNode.removeChild(el); }, 3500);
}