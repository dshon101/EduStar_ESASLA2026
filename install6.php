<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<pre style='font-family:monospace;font-size:13px;padding:20px;background:#0d0d1a;color:#f0f0ff'>";

$adminPath = __DIR__ . '/admin/index.php';
$content = file_get_contents($adminPath);

// ── FIX 1: Replace renderUserTable with working version ──────────
$old = 'function renderUserTable(users) {
  document.querySelector(\'#users-table tbody\').innerHTML = users.length
    ? users.map(u => `<tr>
        <td>${u.id}</td><td>${u.name}</td><td>${u.email}</td>
        <td>${u.country}</td><td>${u.grade}</td><td>${u.points}</td>
        <td>${u.level}</td><td>${(u.joinDate||\'\').split(\'T\')[0]}</td>
        <td>${u.isAdmin?\'<span class="badge badge-admin">Admin</span>\':\'<span class="badge badge-active">User</span>\'}</td>
      </tr>`).join(\'\')
    : \'<tr><td colspan="9" style="text-align:center;color:var(--muted);padding:2rem">User list requires server-side admin endpoint — see /api/admin/users.php</td></tr>\';
}';

$new = 'function renderUserTable(users) {
  document.querySelector(\'#users-table tbody\').innerHTML = users.length
    ? users.map(u => `<tr>
        <td>${u.id}</td>
        <td><strong>${u.name}</strong></td>
        <td style="font-size:0.78rem">${u.email}</td>
        <td>${u.country||\'—\'}</td>
        <td>${u.grade||\'—\'}</td>
        <td><strong style="color:var(--gold)">${u.points||0}</strong></td>
        <td>Lv.${u.level||1}</td>
        <td style="font-size:0.75rem;color:var(--muted)">${(u.created_at||\'\'). slice(0,10)}</td>
        <td>${u.is_admin?\'<span class="badge badge-admin">👑 Admin</span>\':\'<span class="badge badge-active">User</span>\'}</td>
        <td style="display:flex;gap:0.4rem">
          <button onclick="doToggleAdmin(${u.id})" style="background:rgba(255,107,43,0.1);color:var(--sun);border:1px solid rgba(255,107,43,0.3);border-radius:50px;padding:0.28rem 0.65rem;font-size:0.72rem;cursor:pointer">${u.is_admin?\'👤 Demote\':\'⭐ Admin\'}</button>
          ${u.is_active?`<button onclick="doBanUser(${u.id},\'${(u.name||\'user\').replace(/\'/g,\'\')}\`)" style="background:rgba(255,71,87,0.1);color:#FF6B7A;border:1px solid rgba(255,71,87,0.3);border-radius:50px;padding:0.28rem 0.65rem;font-size:0.72rem;cursor:pointer">🚫 Ban</button>`:\'\'}
        </td>
      </tr>`).join(\'\')
    : \'<tr><td colspan="10" style="text-align:center;color:var(--muted);padding:2rem">No users found.</td></tr>\';
}
async function doToggleAdmin(id) {
  if (!confirm(\'Toggle admin status for this user?\')) return;
  try { const d = await api(\'GET\', `/admin/users.php?action=toggle_admin&id=${id}`); toast(d.is_admin?\'✅ Promoted to Admin\':\'✅ Admin removed\'); loadUsers(); }
  catch(e) { toast(e.message); }
}
async function doBanUser(id, name) {
  if (!confirm(`Ban ${name}? They will not be able to log in.`)) return;
  try { await api(\'GET\', `/admin/users.php?action=deactivate&id=${id}`); toast(\'✅ User banned\'); loadUsers(); }
  catch(e) { toast(e.message); }
}';

if (strpos($content, $old) !== false) {
    $content = str_replace($old, $new, $content);
    echo "renderUserTable: ✅ Replaced\n";
} else {
    // Already patched by install5 but toggleAdminRole failed — just append the functions
    echo "renderUserTable: already patched, appending action functions...\n";
    $inject = "\nasync function doToggleAdmin(id) {\n  if (!confirm('Toggle admin status for this user?')) return;\n  try { const d = await api('GET', `/admin/users.php?action=toggle_admin&id=\${id}`); toast(d.is_admin?'✅ Promoted to Admin':'✅ Admin removed'); loadUsers(); }\n  catch(e) { toast(e.message); }\n}\nasync function doBanUser(id, name) {\n  if (!confirm(`Ban \${name}?`)) return;\n  try { await api('GET', `/admin/users.php?action=deactivate&id=\${id}`); toast('✅ User banned'); loadUsers(); }\n  catch(e) { toast(e.message); }\n}\n";
    $content = str_replace('// ── TOAST', $inject . '// ── TOAST', $content);
    echo "Action functions: ✅ Injected\n";
}

// ── FIX 2: loadBooks — replace broken version with stats version ──
$oldLoadBooks = 'async function loadBooks() {
  try {
    const data = await api(\'GET\', \'/books.php?action=list\');
    allBooks = data.books;
    renderBooksTable(allBooks);
    populateBookSelect(allBooks);
    document.getElementById(\'stat-books\').textContent = allBooks.length;
    document.getElementById(\'stat-downloads\').textContent =
      allBooks.reduce((s, b) => s + (b.download_count||0), 0);
  } catch(e) { console.error(e); }
}';

$newLoadBooks = 'async function loadBooks() {
  try {
    const data = await api(\'GET\', \'/books.php?action=stats\');
    if (document.getElementById(\'bs-total\'))       document.getElementById(\'bs-total\').textContent       = data.total || 0;
    if (document.getElementById(\'bs-top-country\'))  document.getElementById(\'bs-top-country\').textContent  = data.byCountry?.[0]?.country || \'—\';
    if (document.getElementById(\'bs-top-subject\'))  document.getElementById(\'bs-top-subject\').textContent  = data.bySubject?.[0]?.subject  || \'—\';
    if (document.getElementById(\'stat-downloads\'))  document.getElementById(\'stat-downloads\').textContent  = data.total || 0;
    const top = document.getElementById(\'books-top-tbody\');
    if (top) top.innerHTML = (data.topBooks||[]).length
      ? data.topBooks.map(b=>`<tr><td>${b.title}</td><td>${b.country}</td><td>${b.subject}</td><td><strong style="color:var(--gold)">${b.cnt}</strong></td></tr>`).join(\'\')
      : \'<tr><td colspan="4" style="text-align:center;color:var(--muted);padding:1.5rem">No downloads yet — students haven\\\'t downloaded any books.</td></tr>\';
    const rec = document.getElementById(\'books-recent-tbody\');
    if (rec) rec.innerHTML = (data.recent||[]).length
      ? data.recent.map(d=>`<tr><td style="font-size:0.82rem">${d.title}</td><td>${d.country}</td><td>${d.subject}</td><td>${d.user_name||\'Guest\'}</td><td style="font-size:0.75rem;color:var(--muted)">${(d.downloaded_at||\'\'). slice(0,16)}</td></tr>`).join(\'\')
      : \'<tr><td colspan="5" style="text-align:center;color:var(--muted);padding:1rem">No recent downloads.</td></tr>\';
  } catch(e) { console.error(\'loadBooks:\', e); }
}';

if (strpos($content, $oldLoadBooks) !== false) {
    $content = str_replace($oldLoadBooks, $newLoadBooks, $content);
    echo "loadBooks: ✅ Replaced with stats version\n";
} else {
    echo "loadBooks: already patched ✅\n";
}

// ── WRITE BACK ────────────────────────────────────────────────────
file_put_contents($adminPath, $content);

// ── VERIFY ────────────────────────────────────────────────────────
$v = file_get_contents($adminPath);
echo "\n── VERIFICATION ─────────────────────────────────────────────\n";
echo "Calls /admin/users.php:  " . (strpos($v, '/admin/users.php') !== false ? "✅" : "❌") . "\n";
echo "Has doToggleAdmin:       " . (strpos($v, 'doToggleAdmin') !== false ? "✅" : "❌") . "\n";
echo "Has doBanUser:           " . (strpos($v, 'doBanUser') !== false ? "✅" : "❌") . "\n";
echo "Has Actions column:      " . (strpos($v, 'Actions</th>') !== false ? "✅" : "❌") . "\n";
echo "loadBooks calls stats:   " . (strpos($v, 'action=stats') !== false ? "✅" : "❌") . "\n";

echo "\n✅ Done! Delete install6.php, then LOG OUT and back into admin.\n";
echo "</pre><p style='color:red;font-weight:bold;font-family:monospace;padding:20px'>⚠️ DELETE install6.php immediately!</p>";
