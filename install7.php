<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<pre style='font-family:monospace;font-size:13px;padding:20px;background:#0d0d1a;color:#f0f0ff'>";

$adminPath = __DIR__ . '/admin/index.php';
$content = file_get_contents($adminPath);

// ── SHOW CURRENT STATE ────────────────────────────────────────────
echo "=== CURRENT STATE ===\n";
echo "Calls /admin/users.php:  " . (strpos($content, '/admin/users.php') !== false ? "✅ YES" : "❌ NO") . "\n";
echo "Has doToggleAdmin:       " . (strpos($content, 'doToggleAdmin') !== false ? "✅ YES" : "❌ NO") . "\n";
echo "Has doBanUser:           " . (strpos($content, 'doBanUser') !== false ? "✅ YES" : "❌ NO") . "\n";
echo "Has Actions column:      " . (strpos($content, 'Actions</th>') !== false ? "✅ YES" : "❌ NO") . "\n";
echo "Has old broken loadUsers:" . (strpos($content, 'action=me') !== false ? "❌ YES (bad)" : "✅ NO (good)") . "\n";

// ── SHOW THE CURRENT loadUsers FUNCTION ──────────────────────────
echo "\n=== CURRENT loadUsers FUNCTION ===\n";
preg_match('/async function loadUsers\(\)[^}]+\}[^}]*\}/s', $content, $m);
echo ($m[0] ?? "NOT FOUND") . "\n";

// ── SHOW CURRENT renderUserTable FUNCTION ────────────────────────
echo "\n=== CURRENT renderUserTable (first 500 chars) ===\n";
preg_match('/function renderUserTable\(users\).{1,500}/s', $content, $m2);
echo ($m2[0] ?? "NOT FOUND") . "\n";

// ── NOW DO A FULL REWRITE OF JUST THE JS SECTION ─────────────────
echo "\n=== PATCHING ===\n";

// Strategy: find the <script> tag and replace everything from loadUsers to filterUserTable
$newUsersJS = '
// ── USERS ─────────────────────────────────────────────────────────
async function loadUsers() {
  try {
    const data = await api(\'GET\', \'/admin/users.php?action=list\');
    allUsers = data.users || [];
    renderUserTable(allUsers);
    if (data.stats) {
      if(document.getElementById(\'stat-users\'))     document.getElementById(\'stat-users\').textContent     = data.stats.users    || 0;
      if(document.getElementById(\'stat-quizzes\'))   document.getElementById(\'stat-quizzes\').textContent   = data.stats.quizzes  || 0;
      if(document.getElementById(\'stat-downloads\')) document.getElementById(\'stat-downloads\').textContent = data.stats.downloads || 0;
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
  tbody.innerHTML = users.map(u => `<tr>
    <td>${u.id}</td>
    <td><strong>${u.name||\'—\'}</strong></td>
    <td style="font-size:0.78rem">${u.email||\'—\'}</td>
    <td>${u.country||\'—\'}</td>
    <td>${u.grade||\'—\'}</td>
    <td><strong style="color:var(--gold)">${u.points||0}</strong></td>
    <td>Lv.${u.level||1}</td>
    <td style="font-size:0.75rem;color:var(--muted)">${(u.created_at||\'\'). slice(0,10)}</td>
    <td>${u.is_admin ? \'<span class="badge badge-admin">👑 Admin</span>\' : \'<span class="badge badge-active">User</span>\'}</td>
    <td style="display:flex;gap:0.35rem;flex-wrap:wrap">
      <button onclick="doToggleAdmin(${u.id})" style="background:rgba(255,107,43,0.1);color:var(--sun);border:1px solid rgba(255,107,43,0.3);border-radius:50px;padding:0.28rem 0.65rem;font-size:0.72rem;cursor:pointer">${u.is_admin?\'👤 Demote\':\'⭐ Admin\'}</button>
      ${u.is_active ? `<button onclick="doBanUser(${u.id},\'${(u.name||\'user\').replace(/\'/g,\'\`)}" style="background:rgba(255,71,87,0.1);color:#FF6B7A;border:1px solid rgba(255,71,87,0.3);border-radius:50px;padding:0.28rem 0.65rem;font-size:0.72rem;cursor:pointer">🚫 Ban</button>` : \'\'}
    </td>
  </tr>`).join(\'\');
}

function filterUserTable(q) {
  const rows = document.querySelectorAll(\'#users-table tbody tr\');
  rows.forEach(r => r.style.display = r.innerText.toLowerCase().includes(q.toLowerCase()) ? \'\' : \'none\');
}

async function doToggleAdmin(id) {
  if (!confirm(\'Toggle admin status for this user?\')) return;
  try {
    const d = await api(\'GET\', `/admin/users.php?action=toggle_admin&id=${id}`);
    toast(d.is_admin ? \'✅ Promoted to Admin\' : \'✅ Admin removed\');
    loadUsers();
  } catch(e) { toast(e.message); }
}

async function doBanUser(id, name) {
  if (!confirm(`Ban ${name}? They will not be able to log in.`)) return;
  try {
    await api(\'GET\', `/admin/users.php?action=deactivate&id=${id}`);
    toast(\'✅ User banned\');
    loadUsers();
  } catch(e) { toast(e.message); }
}
';

// Replace the entire users block using a regex
$pattern = '/\/\/ ── USERS ─+.*?function filterUserTable\(q\) \{.*?\n\}/s';
if (preg_match($pattern, $content)) {
    $content = preg_replace($pattern, $newUsersJS, $content);
    echo "Users JS block: ✅ Replaced via regex\n";
} else {
    // Fallback: find filterUserTable and inject before it
    $filterPos = strpos($content, 'function filterUserTable');
    if ($filterPos !== false) {
        // Find the start of the users block
        $usersPos = strrpos(substr($content, 0, $filterPos), '// ── USERS');
        if ($usersPos !== false) {
            // Find end of filterUserTable function
            $afterFilter = strpos($content, "\n}\n", $filterPos) + 3;
            $content = substr($content, 0, $usersPos) . $newUsersJS . substr($content, $afterFilter);
            echo "Users JS block: ✅ Replaced via position search\n";
        } else {
            // Just inject before filterUserTable
            $content = str_replace('function filterUserTable', $newUsersJS . "\nfunction filterUserTable_OLD_REMOVED", $content);
            echo "Users JS block: ✅ Injected before filterUserTable\n";
        }
    } else {
        echo "Users JS block: ❌ Could not find injection point\n";
    }
}

// Fix users table header to have Actions column
$content = str_replace(
    '<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Country</th><th>Grade</th><th>Points</th><th>Level</th><th>Joined</th><th>Role</th></tr></thead>',
    '<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Country</th><th>Grade</th><th>Points</th><th>Level</th><th>Joined</th><th>Role</th><th>Actions</th></tr></thead>',
    $content
);

// Write back
file_put_contents($adminPath, $content);

// Final verify
$v = file_get_contents($adminPath);
echo "\n=== FINAL VERIFICATION ===\n";
echo "Calls /admin/users.php:  " . (strpos($v, '/admin/users.php') !== false ? "✅" : "❌") . "\n";
echo "Has doToggleAdmin:       " . (strpos($v, 'doToggleAdmin') !== false ? "✅" : "❌") . "\n";
echo "Has doBanUser:           " . (strpos($v, 'doBanUser') !== false ? "✅" : "❌") . "\n";
echo "Has Actions column:      " . (strpos($v, 'Actions</th>') !== false ? "✅" : "❌") . "\n";
echo "No old broken loadUsers: " . (strpos($v, "action='me'") === false && strpos($v, '"action=me"') === false ? "✅" : "❌") . "\n";

echo "\n✅ Done! Delete install7.php then HARD REFRESH admin (Ctrl+Shift+R)\n";
echo "</pre><p style='color:red;font-weight:bold;font-family:monospace;padding:20px'>⚠️ DELETE install7.php immediately!</p>";
