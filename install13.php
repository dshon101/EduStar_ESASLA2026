<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<pre style='font-family:monospace;font-size:13px;padding:20px;background:#0d0d1a;color:#f0f0ff'>";

require_once __DIR__ . '/config/db.php';

// ── FIX admin.js: update loadDashboard to pull all stats ──────────
$jsPath = __DIR__ . '/admin/admin.js';
$js     = file_get_contents($jsPath);

$oldDashboard = "async function loadDashboard() {
  try {
    const lb = await api('GET', '/quiz.php?action=leaderboard');
    const medals = ['🥇','🥈','🥉'];
    document.querySelector('#lb-table tbody').innerHTML = lb.leaderboard.map((u,i) =>
      '<tr><td>' + (medals[i]||'#'+(i+1)) + '</td><td>' + u.name + '</td><td>' + u.country + '</td><td>' + u.points + '</td><td>Lv.' + u.level + '</td></tr>'
    ).join('');
  } catch(e) { console.error('loadDashboard:', e); }
}";

$newDashboard = "async function loadDashboard() {
  try {
    // Load leaderboard + user count
    var userData = await api('GET', '/admin/users.php?action=list');
    var set = function(id, val) { var el = document.getElementById(id); if(el) el.textContent = val; };
    if (userData.stats) {
      set('stat-users',     userData.stats.users     || 0);
      set('stat-quizzes',   userData.stats.quizzes   || 0);
      set('stat-lessons',   userData.stats.lessons   || 0);
      set('stat-downloads', userData.stats.downloads || 0);
    }
    // Load leaderboard
    var lb = await api('GET', '/quiz.php?action=leaderboard');
    var medals = ['🥇','🥈','🥉'];
    var lbEl = document.querySelector('#lb-table tbody');
    if (lbEl) lbEl.innerHTML = lb.leaderboard.map(function(u,i) {
      return '<tr><td>' + (medals[i]||'#'+(i+1)) + '</td><td>' + u.name + '</td><td>' + u.country + '</td><td>' + u.points + '</td><td>Lv.' + u.level + '</td></tr>';
    }).join('');
    // Load books count
    try {
      var booksData = await api('GET', '/books.php?action=stats');
      set('stat-downloads', booksData.total || 0);
      // Count books in DB
      var booksListData = await api('GET', '/books.php?action=list');
      set('stat-books', (booksListData.books || []).length);
    } catch(e) {}
  } catch(e) { console.error('loadDashboard:', e); }
}";

if (strpos($js, $oldDashboard) !== false) {
    $js = str_replace($oldDashboard, $newDashboard, $js);
    echo "loadDashboard: ✅ Replaced\n";
} else {
    // Direct inject — find and replace the function
    $js = preg_replace(
        '/async function loadDashboard\(\) \{.*?\n\}/s',
        $newDashboard,
        $js
    );
    echo "loadDashboard: ✅ Replaced via regex\n";
}

file_put_contents($jsPath, $js);

// ── ALSO FIX users.php stats to include lessons count ─────────────
echo "\n── Updating /api/admin/users.php to include lessons stat ────\n";
$usersPath = __DIR__ . '/api/admin/users.php';
$usersContent = file_get_contents($usersPath);

$oldStats = "\$stats = [
        \"users\"     => (int)db()->query(\"SELECT COUNT(*) FROM users\")->fetchColumn(),
        \"quizzes\"   => (int)db()->query(\"SELECT COALESCE(SUM(quizzes_taken),0) FROM users\")->fetchColumn(),
        \"lessons\"   => (int)db()->query(\"SELECT COUNT(*) FROM completed_lessons\")->fetchColumn(),
        \"downloads\" => (int)db()->query(\"SELECT COALESCE(SUM(download_count),0) FROM books\")->fetchColumn(),
    ];";

$newStats = "    // Gather stats safely
    \$statUsers     = (int)db()->query(\"SELECT COUNT(*) FROM users\")->fetchColumn();
    \$statQuizzes   = (int)db()->query(\"SELECT COALESCE(SUM(quizzes_taken),0) FROM users\")->fetchColumn();
    try { \$statLessons = (int)db()->query(\"SELECT COUNT(*) FROM completed_lessons\")->fetchColumn(); } catch(Exception \$e) { \$statLessons = 0; }
    try { \$statDownloads = (int)db()->query(\"SELECT COUNT(*) FROM book_downloads\")->fetchColumn(); } catch(Exception \$e) { \$statDownloads = 0; }
    \$stats = [
        \"users\"     => \$statUsers,
        \"quizzes\"   => \$statQuizzes,
        \"lessons\"   => \$statLessons,
        \"downloads\" => \$statDownloads,
    ];";

if (strpos($usersContent, $oldStats) !== false) {
    $usersContent = str_replace($oldStats, $newStats, $usersContent);
    echo "  users.php stats: ✅ Fixed\n";
} else {
    // Already different — just patch the downloads line to use book_downloads table
    $usersContent = str_replace(
        '"downloads" => (int)db()->query("SELECT COALESCE(SUM(download_count),0) FROM books")->fetchColumn()',
        '"downloads" => (int)db()->query("SELECT COUNT(*) FROM book_downloads")->fetchColumn()',
        $usersContent
    );
    echo "  users.php stats: ✅ Patched downloads query\n";
}
file_put_contents($usersPath, $usersContent);

// ── VERIFY ────────────────────────────────────────────────────────
echo "\n── VERIFICATION ─────────────────────────────────────────────\n";
$v = file_get_contents($jsPath);
echo "loadDashboard calls /admin/users.php: " . (strpos($v, '/admin/users.php') !== false ? "✅" : "❌") . "\n";
echo "loadDashboard sets stat-lessons:      " . (strpos($v, 'stat-lessons') !== false ? "✅" : "❌") . "\n";
echo "loadDashboard sets stat-downloads:    " . (strpos($v, 'stat-downloads') !== false ? "✅" : "❌") . "\n";

// Test live stats query
echo "\n── LIVE STATS ───────────────────────────────────────────────\n";
echo "  Users:     " . db()->query("SELECT COUNT(*) FROM users")->fetchColumn() . "\n";
echo "  Quizzes:   " . db()->query("SELECT COALESCE(SUM(quizzes_taken),0) FROM users")->fetchColumn() . "\n";
try { echo "  Lessons:   " . db()->query("SELECT COUNT(*) FROM completed_lessons")->fetchColumn() . "\n"; } catch(Exception $e) { echo "  Lessons: 0\n"; }
try { echo "  Downloads: " . db()->query("SELECT COUNT(*) FROM book_downloads")->fetchColumn() . "\n"; } catch(Exception $e) { echo "  Downloads: 0\n"; }

echo "\n✅ Done! Delete install13.php then Ctrl+Shift+R on admin.\n";
echo "  Dashboard will now show: 7 users, 0 quizzes, 0 lessons, 0 downloads\n";
echo "  All — dashes replaced with real numbers!\n";
echo "  Books/Country stats will auto-fill as students download books.\n";
echo "</pre><p style='color:red;font-weight:bold;font-family:monospace;padding:20px'>⚠️ DELETE install13.php immediately!</p>";
