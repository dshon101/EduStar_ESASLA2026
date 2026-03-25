<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<pre style='font-family:monospace;font-size:13px;padding:20px;background:#0d0d1a;color:#f0f0ff'>";

require_once __DIR__ . '/config/db.php';

// Add title column if missing
echo "=== FIXING book_downloads TABLE ===\n";
$moreFixes = [
    'title' => "ALTER TABLE book_downloads ADD COLUMN title VARCHAR(255) DEFAULT NULL",
];
foreach ($moreFixes as $col => $sql) {
    try { db()->exec($sql); echo "  Added $col ✅\n"; }
    catch (Exception $e) { echo "  $col: already exists ✅\n"; }
}

// Verify all columns now
echo "\n=== ALL COLUMNS NOW ===\n";
$cols = db()->query("DESCRIBE book_downloads")->fetchAll();
foreach ($cols as $c) echo "  " . $c['Field'] . " — " . $c['Type'] . "\n";

// Test all queries
echo "\n=== TESTING ALL STATS QUERIES ===\n";
try {
    $t = db()->query("SELECT COUNT(*) FROM book_downloads")->fetchColumn();
    echo "  total: ✅ ($t)\n";

    db()->query("SELECT country, COUNT(*) as cnt FROM book_downloads GROUP BY country ORDER BY cnt DESC LIMIT 5")->fetchAll();
    echo "  byCountry: ✅\n";

    db()->query("SELECT subject, COUNT(*) as cnt FROM book_downloads GROUP BY subject ORDER BY cnt DESC LIMIT 5")->fetchAll();
    echo "  bySubject: ✅\n";

    db()->query("SELECT title, country, subject, COUNT(*) as cnt FROM book_downloads GROUP BY title,country,subject ORDER BY cnt DESC LIMIT 20")->fetchAll();
    echo "  topBooks: ✅\n";

    db()->query("SELECT bd.title, bd.country, bd.subject, bd.downloaded_at, u.name as user_name FROM book_downloads bd LEFT JOIN users u ON u.id=bd.user_id ORDER BY bd.downloaded_at DESC LIMIT 50")->fetchAll();
    echo "  recent: ✅\n";
} catch (Exception $e) { echo "  ❌ " . $e->getMessage() . "\n"; }

echo "\n✅ Done! Delete install12.php then Ctrl+Shift+R on admin.\n";
echo "</pre><p style='color:red;font-weight:bold;font-family:monospace;padding:20px'>⚠️ DELETE install12.php immediately!</p>";
