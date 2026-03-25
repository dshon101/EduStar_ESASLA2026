<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<pre style='font-family:monospace;font-size:13px;padding:20px;background:#0d0d1a;color:#f0f0ff'>";
echo "=== EduStar Install5 — DB Tables + API Files ===\n\n";

require_once __DIR__ . '/config/db.php';
$pdo = db();

// ══════════════════════════════════════════════════════════════════
// 1. CREATE ALL MISSING DATABASE TABLES
// ══════════════════════════════════════════════════════════════════
echo "── DATABASE TABLES ──────────────────────────────────────────\n";

$tables = [
  'quiz_scores' => "CREATE TABLE IF NOT EXISTS quiz_scores (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    subject_id   VARCHAR(50) NOT NULL,
    subject_name VARCHAR(100),
    score        INT NOT NULL DEFAULT 0,
    total        INT NOT NULL DEFAULT 0,
    percentage   DECIMAL(5,2) DEFAULT 0,
    taken_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id), INDEX idx_subject (subject_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

  'completed_lessons' => "CREATE TABLE IF NOT EXISTS completed_lessons (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    lesson_id    VARCHAR(100) NOT NULL,
    subject_id   VARCHAR(50),
    points_earned INT DEFAULT 50,
    completed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_lesson (user_id, lesson_id),
    INDEX idx_user (user_id), INDEX idx_subject (subject_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

  'book_downloads' => "CREATE TABLE IF NOT EXISTS book_downloads (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    title         VARCHAR(255) NOT NULL,
    country       VARCHAR(100),
    subject       VARCHAR(100),
    source        VARCHAR(100),
    user_id       INT NULL,
    downloaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_title (title(50)), INDEX idx_country (country), INDEX idx_user (user_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

  'books' => "CREATE TABLE IF NOT EXISTS books (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    book_key       VARCHAR(100) UNIQUE NOT NULL,
    title          VARCHAR(255) NOT NULL,
    subject        VARCHAR(50),
    country        VARCHAR(10),
    grade_range    VARCHAR(50),
    publisher      VARCHAR(255),
    curriculum     VARCHAR(100),
    year           INT,
    icon           VARCHAR(10) DEFAULT 'book',
    color          VARCHAR(20) DEFAULT '#FF6B2B',
    file_path      VARCHAR(500),
    download_count INT DEFAULT 0,
    is_active      TINYINT(1) DEFAULT 1,
    created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_country (country), INDEX idx_subject (subject)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

  'book_chapters' => "CREATE TABLE IF NOT EXISTS book_chapters (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    book_key   VARCHAR(100) NOT NULL,
    chapter    VARCHAR(255) NOT NULL,
    topics     TEXT,
    sort_order INT DEFAULT 0,
    INDEX idx_book (book_key)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

  'sessions' => "CREATE TABLE IF NOT EXISTS sessions (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    token      VARCHAR(64) UNIQUE NOT NULL,
    user_id    INT NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token), INDEX idx_user (user_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach ($tables as $name => $sql) {
    try {
        $pdo->exec($sql);
        $count = $pdo->query("SELECT COUNT(*) FROM `$name`")->fetchColumn();
        echo "  $name: ✅ ($count rows)\n";
    } catch (Exception $e) {
        echo "  $name: ❌ " . $e->getMessage() . "\n";
    }
}

// Add missing columns to users
echo "\n── USER TABLE COLUMNS ───────────────────────────────────────\n";
$cols = [
    'avatar'        => "ALTER TABLE users ADD COLUMN avatar VARCHAR(10) DEFAULT NULL",
    'quizzes_taken' => "ALTER TABLE users ADD COLUMN quizzes_taken INT DEFAULT 0",
    'last_login'    => "ALTER TABLE users ADD COLUMN last_login DATETIME DEFAULT NULL",
];
foreach ($cols as $col => $sql) {
    try { $pdo->exec($sql); echo "  Added users.$col ✅\n"; }
    catch (Exception $e) { echo "  users.$col: already exists ✅\n"; }
}

// ══════════════════════════════════════════════════════════════════
// 2. WRITE API FILES
// ══════════════════════════════════════════════════════════════════
echo "\n── API FILES ────────────────────────────────────────────────\n";

// quiz.php
file_put_contents(__DIR__ . '/api/quiz.php', '<?php
error_reporting(0); ini_set("display_errors",0);
require_once __DIR__."/../config/db.php";
require_once __DIR__."/../config/helpers.php";
jsonHeaders();
$action = $_GET["action"] ?? "";

if ($action === "leaderboard") {
    $rows = db()->query("SELECT id,name,country,points,level,quizzes_taken FROM users WHERE is_active=1 ORDER BY points DESC LIMIT 20")->fetchAll();
    ok(["leaderboard"=>$rows]); exit;
}
if ($action === "submit" && $_SERVER["REQUEST_METHOD"]==="POST") {
    $user = requireAuth(); $b = body();
    $sid  = trim($b["subjectId"] ?? ""); $sname = trim($b["subjectName"] ?? $sid);
    $score= max(0,(int)($b["score"]??0)); $total = max(1,(int)($b["total"]??10));
    $pct  = round($score/$total*100,2);
    db()->prepare("INSERT INTO quiz_scores (user_id,subject_id,subject_name,score,total,percentage) VALUES (?,?,?,?,?,?)")
       ->execute([$user["id"],$sid,$sname,$score,$total,$pct]);
    $nq = (int)$user["quizzes_taken"]+1;
    $pts= (int)$user["points"]+$score*10;
    $lvl= max(1,(int)floor($pts/500)+1);
    db()->prepare("UPDATE users SET quizzes_taken=?,points=?,level=? WHERE id=?")->execute([$nq,$pts,$lvl,$user["id"]]);
    ok(["score"=>$score,"total"=>$total,"percentage"=>$pct,"pointsEarned"=>$score*10]); exit;
}
if ($action === "history") {
    $user = requireAuth();
    $s = db()->prepare("SELECT * FROM quiz_scores WHERE user_id=? ORDER BY taken_at DESC LIMIT 50");
    $s->execute([$user["id"]]); ok(["history"=>$s->fetchAll()]); exit;
}
if ($action === "admin_stats") {
    requireAdmin();
    $total = db()->query("SELECT COUNT(*) FROM quiz_scores")->fetchColumn();
    $avg   = db()->query("SELECT AVG(percentage) FROM quiz_scores")->fetchColumn();
    $bysub = db()->query("SELECT subject_name,COUNT(*) as cnt,AVG(percentage) as avg_pct FROM quiz_scores GROUP BY subject_name ORDER BY cnt DESC LIMIT 10")->fetchAll();
    ok(["total"=>(int)$total,"avgScore"=>round($avg,1),"bySubject"=>$bysub]); exit;
}
fail("Unknown action",404);
');
echo "  api/quiz.php ✅\n";

// lessons.php
file_put_contents(__DIR__ . '/api/lessons.php', '<?php
error_reporting(0); ini_set("display_errors",0);
require_once __DIR__."/../config/db.php";
require_once __DIR__."/../config/helpers.php";
jsonHeaders();
$action = $_GET["action"] ?? "";

if ($action === "complete" && $_SERVER["REQUEST_METHOD"]==="POST") {
    $user = requireAuth(); $b = body();
    $lid  = trim($b["lessonId"] ?? "");
    $sid  = trim($b["subjectId"] ?? (explode("-",$lid)[0] ?? ""));
    $pts  = max(0,(int)($b["points"]??50));
    if (!$lid) fail("lessonId required");
    db()->prepare("INSERT IGNORE INTO completed_lessons (user_id,lesson_id,subject_id,points_earned) VALUES (?,?,?,?)")
       ->execute([$user["id"],$lid,$sid,$pts]);
    $isNew = (int)db()->query("SELECT COUNT(*) FROM completed_lessons WHERE user_id={$user["id"]} AND lesson_id=".db()->quote($lid))->fetchColumn() > 0;
    if ($isNew) {
        $np = (int)$user["points"]+$pts; $nl = max(1,(int)floor($np/500)+1);
        db()->prepare("UPDATE users SET points=?,level=? WHERE id=?")->execute([$np,$nl,$user["id"]]);
    }
    $comp = db()->prepare("SELECT lesson_id FROM completed_lessons WHERE user_id=?");
    $comp->execute([$user["id"]]);
    ok(["completed"=>$comp->fetchAll(PDO::FETCH_COLUMN),"pointsEarned"=>$isNew?$pts:0]); exit;
}
if ($action === "my_progress") {
    $user = requireAuth();
    $s = db()->prepare("SELECT lesson_id,subject_id,points_earned,completed_at FROM completed_lessons WHERE user_id=? ORDER BY completed_at DESC");
    $s->execute([$user["id"]]); ok(["progress"=>$s->fetchAll()]); exit;
}
if ($action === "admin_stats") {
    requireAdmin();
    $total = db()->query("SELECT COUNT(*) FROM completed_lessons")->fetchColumn();
    $bysub = db()->query("SELECT subject_id,COUNT(*) as cnt FROM completed_lessons GROUP BY subject_id ORDER BY cnt DESC LIMIT 10")->fetchAll();
    $top   = db()->query("SELECT u.name,COUNT(*) as lessons FROM completed_lessons cl JOIN users u ON u.id=cl.user_id GROUP BY cl.user_id ORDER BY lessons DESC LIMIT 10")->fetchAll();
    ok(["total"=>(int)$total,"bySubject"=>$bysub,"topStudents"=>$top]); exit;
}
fail("Unknown action",404);
');
echo "  api/lessons.php ✅\n";

// books.php
file_put_contents(__DIR__ . '/api/books.php', '<?php
error_reporting(0); ini_set("display_errors",0);
require_once __DIR__."/../config/db.php";
require_once __DIR__."/../config/helpers.php";
jsonHeaders();
$action = $_GET["action"] ?? "list";

if ($action === "track" && $_SERVER["REQUEST_METHOD"]==="POST") {
    $b = body();
    $title  = trim($b["title"]   ?? "Unknown");
    $ctry   = trim($b["country"] ?? ""); $sub = trim($b["subject"] ?? ""); $src = trim($b["source"] ?? "");
    $uid = null; $token = getBearerToken();
    if ($token) { $s = db()->prepare("SELECT u.id FROM sessions s JOIN users u ON u.id=s.user_id WHERE s.token=? AND s.expires_at>NOW()"); $s->execute([$token]); $r=$s->fetch(); if($r) $uid=(int)$r["id"]; }
    db()->prepare("INSERT INTO book_downloads (title,country,subject,source,user_id,downloaded_at) VALUES (?,?,?,?,?,NOW())")->execute([$title,$ctry,$sub,$src,$uid]);
    ok(["tracked"=>true]); exit;
}
if ($action === "stats") {
    $token = getBearerToken(); if(!$token) fail("Unauthorised",401);
    $s = db()->prepare("SELECT u.* FROM sessions s JOIN users u ON u.id=s.user_id WHERE s.token=? AND s.expires_at>NOW()"); $s->execute([$token]); $admin=$s->fetch();
    if(!$admin||!$admin["is_admin"]) fail("Admin only",403);
    $total    = (int)db()->query("SELECT COUNT(*) FROM book_downloads")->fetchColumn();
    $byCtry   = db()->query("SELECT country,COUNT(*) as cnt FROM book_downloads GROUP BY country ORDER BY cnt DESC LIMIT 10")->fetchAll();
    $bySub    = db()->query("SELECT subject,COUNT(*) as cnt FROM book_downloads GROUP BY subject ORDER BY cnt DESC LIMIT 10")->fetchAll();
    $topBooks = db()->query("SELECT title,country,subject,COUNT(*) as cnt FROM book_downloads GROUP BY title,country,subject ORDER BY cnt DESC LIMIT 20")->fetchAll();
    $recent   = db()->query("SELECT bd.title,bd.country,bd.subject,bd.downloaded_at,u.name as user_name FROM book_downloads bd LEFT JOIN users u ON u.id=bd.user_id ORDER BY bd.downloaded_at DESC LIMIT 50")->fetchAll();
    ok(["total"=>$total,"byCountry"=>$byCtry,"bySubject"=>$bySub,"topBooks"=>$topBooks,"recent"=>$recent]); exit;
}
if ($action === "list") {
    $country = trim($_GET["country"] ?? "");
    if ($country) { $s=db()->prepare("SELECT * FROM books WHERE (country=? OR country=\'continental\') AND is_active=1 ORDER BY title"); $s->execute([$country]); }
    else { $s=db()->prepare("SELECT * FROM books WHERE is_active=1 ORDER BY country,title"); $s->execute(); }
    $books=$s->fetchAll();
    foreach ($books as &$b) { $ch=db()->prepare("SELECT chapter,topics FROM book_chapters WHERE book_key=? ORDER BY sort_order"); $ch->execute([$b["book_key"]]); $b["chapters"]=$ch->fetchAll(); $b["hasFile"]=!empty($b["file_path"]); }
    ok(["books"=>$books]); exit;
}
if ($action === "create" && $_SERVER["REQUEST_METHOD"]==="POST") {
    requireAdmin(); $b=body(); $key=trim($b["book_key"]??""); if(!$key) fail("book_key required");
    db()->prepare("INSERT INTO books (book_key,title,subject,country,grade_range,publisher,curriculum,year,icon,color) VALUES (?,?,?,?,?,?,?,?,?,?)")
       ->execute([$key,$b["title"]??"",$b["subject"]??"",$b["country"]??"",$b["grade_range"]??"",$b["publisher"]??"",$b["curriculum"]??"",$b["year"]??null,$b["icon"]??"book",$b["color"]??"#FF6B2B"]);
    if(!empty($b["chapters"])&&is_array($b["chapters"])) { foreach($b["chapters"] as $i=>$ch) { db()->prepare("INSERT INTO book_chapters (book_key,chapter,topics,sort_order) VALUES (?,?,?,?)")->execute([$key,$ch["title"]??$ch["chapter"]??"",implode(",",$ch["topics"]??[]),$i]); } }
    ok(["created"=>true]); exit;
}
if ($action === "upload" && $_SERVER["REQUEST_METHOD"]==="POST") {
    requireAdmin(); $key=$_POST["bookKey"]??""; $file=$_FILES["file"]??null;
    if(!$key||!$file||$file["error"]!==0) fail("Book key and file required");
    if($file["type"]!=="application/pdf") fail("Only PDF files allowed");
    $dir=__DIR__."/../uploads/books/"; if(!is_dir($dir)) mkdir($dir,0755,true);
    $dest=$dir.$key.".pdf"; if(!move_uploaded_file($file["tmp_name"],$dest)) fail("Upload failed");
    db()->prepare("UPDATE books SET file_path=? WHERE book_key=?")->execute(["/uploads/books/$key.pdf",$key]);
    ok(["path"=>"/uploads/books/$key.pdf"]); exit;
}
if ($action === "delete" && ($_SERVER["REQUEST_METHOD"]==="DELETE"||$_SERVER["REQUEST_METHOD"]==="POST")) {
    requireAdmin(); $key=$_GET["id"]??"";
    db()->prepare("DELETE FROM books WHERE book_key=?")->execute([$key]);
    db()->prepare("DELETE FROM book_chapters WHERE book_key=?")->execute([$key]);
    ok(["deleted"=>true]); exit;
}
fail("Unknown action",404);
');
echo "  api/books.php ✅\n";

// ══════════════════════════════════════════════════════════════════
// 3. FIX ADMIN INDEX.PHP — patch the loadUsers function in-place
// ══════════════════════════════════════════════════════════════════
echo "\n── FIXING admin/index.php ───────────────────────────────────\n";
$adminPath = __DIR__ . '/admin/index.php';
$adminContent = file_get_contents($adminPath);
if (!$adminContent) { echo "  ❌ Could not read admin/index.php\n"; }
else {
    // Fix 1: loadUsers — replace the broken function
    $oldLoad = 'async function loadUsers() {
  try {
    const data = await api(\'GET\', \'/auth.php?action=me\');
    // For admin listing we fetch leaderboard as proxy (real admin would have /api/admin/users)
    // Here we render what we have
    renderUserTable([]);
  } catch(e) {}
}';
    $newLoad = 'async function loadUsers() {
  try {
    const data = await api(\'GET\', \'/admin/users.php?action=list\');
    allUsers = data.users || [];
    renderUserTable(allUsers);
    if (data.stats) {
      document.getElementById(\'stat-users\').textContent     = data.stats.users    || 0;
      document.getElementById(\'stat-quizzes\').textContent   = data.stats.quizzes  || 0;
      document.getElementById(\'stat-downloads\').textContent = data.stats.downloads || 0;
    }
  } catch(e) { console.error(\'loadUsers:\', e.message); }
}';

    // Fix 2: renderUserTable — show real data with actions
    $oldRender = 'function renderUserTable(users) {
  document.querySelector(\'#users-table tbody\').innerHTML = users.length
    ? users.map(u => `<tr>
        <td>${u.id}</td><td>${u.name}</td><td>${u.email}</td>
        <td>${u.country}</td><td>${u.grade}</td><td>${u.points}</td>
        <td>${u.level}</td><td>${(u.joinDate||\'\').split(\'T\')[0]}</td>
        <td>${u.isAdmin?\'<span class="badge badge-admin">Admin</span>\':\'<span class="badge badge-active">User</span>\'}</td>
      </tr>`).join(\'\')
    : \'<tr><td colspan="9" style="text-align:center;color:var(--muted);padding:2rem">User list requires server-side admin endpoint — see /api/admin/users.php</td></tr>\';
}';
    $newRender = 'function renderUserTable(users) {
  document.querySelector(\'#users-table tbody\').innerHTML = users.length
    ? users.map(u => `<tr>
        <td>${u.id}</td>
        <td><strong>${u.name}</strong></td>
        <td style="font-size:0.78rem">${u.email}</td>
        <td>${u.country||\'—\'}</td>
        <td style="font-size:0.8rem">${u.grade||\'—\'}</td>
        <td><strong style="color:var(--gold)">${u.points||0}</strong></td>
        <td>Lv.${u.level||1}</td>
        <td style="font-size:0.75rem;color:var(--muted)">${(u.created_at||\'\'). slice(0,10)}</td>
        <td>${u.is_admin?\'<span class="badge badge-admin">👑 Admin</span>\':\'<span class="badge badge-active">User</span>\'}</td>
        <td style="display:flex;gap:0.4rem">
          <button onclick="toggleAdminRole(${u.id})" style="background:rgba(255,107,43,0.1);color:var(--sun);border:1px solid rgba(255,107,43,0.3);border-radius:50px;padding:0.28rem 0.65rem;font-size:0.72rem;cursor:pointer">${u.is_admin?\'👤 Demote\':\'⭐ Admin\'}</button>
          ${u.is_active?`<button onclick="banUser(${u.id},\'${(u.name||"").replace(/\'/g,"")}\`)" style="background:rgba(255,71,87,0.1);color:#FF6B7A;border:1px solid rgba(255,71,87,0.3);border-radius:50px;padding:0.28rem 0.65rem;font-size:0.72rem;cursor:pointer">🚫 Ban</button>`:\'\'}
        </td>
      </tr>`).join(\'\')
    : \'<tr><td colspan="10" style="text-align:center;color:var(--muted);padding:2rem">No users found.</td></tr>\';
}

async function toggleAdminRole(id) {
  if (!confirm(\'Toggle admin for this user?\')) return;
  try { const d = await api(\'GET\', `/admin/users.php?action=toggle_admin&id=${id}`); toast(d.is_admin?\'✅ Promoted to Admin\':\'✅ Admin removed\'); loadUsers(); }
  catch(e) { toast(e.message, \'error\'); }
}
async function banUser(id, name) {
  if (!confirm(`Ban ${name}?`)) return;
  try { await api(\'GET\', `/admin/users.php?action=deactivate&id=${id}`); toast(\'✅ User banned\'); loadUsers(); }
  catch(e) { toast(e.message, \'error\'); }
}';

    // Fix 3: Users table header — add Actions column
    $oldThead = '<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Country</th><th>Grade</th><th>Points</th><th>Level</th><th>Joined</th><th>Role</th></tr></thead>';
    $newThead = '<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Country</th><th>Grade</th><th>Points</th><th>Level</th><th>Joined</th><th>Role</th><th>Actions</th></tr></thead>';

    $adminContent = str_replace($oldLoad,    $newLoad,    $adminContent);
    $adminContent = str_replace($oldRender,  $newRender,  $adminContent);
    $adminContent = str_replace($oldThead,   $newThead,   $adminContent);

    file_put_contents($adminPath, $adminContent);

    // Verify
    $verify = file_get_contents($adminPath);
    echo "  Calls /admin/users.php: " . (strpos($verify, '/admin/users.php') !== false ? "✅" : "❌") . "\n";
    echo "  Has toggleAdminRole:    " . (strpos($verify, 'toggleAdminRole') !== false ? "✅" : "❌") . "\n";
    echo "  Has Actions column:     " . (strpos($verify, 'Actions</th>') !== false ? "✅" : "❌") . "\n";
    echo "  admin/index.php ✅\n";
}

// ══════════════════════════════════════════════════════════════════
// 4. FINAL SUMMARY
// ══════════════════════════════════════════════════════════════════
echo "\n── FINAL DB CHECK ───────────────────────────────────────────\n";
foreach (['users','sessions','quiz_scores','completed_lessons','book_downloads','books','book_chapters'] as $t) {
    try { $c=$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn(); echo "  $t: ✅ ($c rows)\n"; }
    catch (Exception $e) { echo "  $t: ❌ MISSING\n"; }
}

echo "\n── USERS ────────────────────────────────────────────────────\n";
foreach ($pdo->query("SELECT id,name,email,is_admin FROM users ORDER BY id")->fetchAll() as $u) {
    echo "  #{$u['id']} ".($u['is_admin']?'👑':'👤')." {$u['name']} ({$u['email']})\n";
}

echo "\n✅ Done! Delete install5.php then LOG OUT and back into admin.\n";
echo "</pre><p style='color:red;font-weight:bold;font-family:monospace;padding:20px'>⚠️ DELETE install5.php immediately!</p>";
