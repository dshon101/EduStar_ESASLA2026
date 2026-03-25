<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<pre style='font-family:monospace;font-size:13px;padding:20px;background:#0d0d1a;color:#f0f0ff'>";

require_once __DIR__ . '/config/db.php';

// Show current columns in book_downloads
echo "=== CURRENT book_downloads COLUMNS ===\n";
$cols = db()->query("DESCRIBE book_downloads")->fetchAll();
foreach ($cols as $c) echo "  " . $c['Field'] . " " . $c['Type'] . "\n";

// Add missing columns
echo "\n=== FIXING COLUMNS ===\n";
$fixes = [
    'country'      => "ALTER TABLE book_downloads ADD COLUMN country VARCHAR(100) DEFAULT NULL",
    'subject'      => "ALTER TABLE book_downloads ADD COLUMN subject VARCHAR(100) DEFAULT NULL",
    'source'       => "ALTER TABLE book_downloads ADD COLUMN source VARCHAR(100) DEFAULT NULL",
    'user_id'      => "ALTER TABLE book_downloads ADD COLUMN user_id INT DEFAULT NULL",
];
foreach ($fixes as $col => $sql) {
    try { db()->exec($sql); echo "  Added $col ✅\n"; }
    catch (Exception $e) { echo "  $col: already exists ✅\n"; }
}

// Verify
echo "\n=== UPDATED COLUMNS ===\n";
$cols = db()->query("DESCRIBE book_downloads")->fetchAll();
foreach ($cols as $c) echo "  " . $c['Field'] . " " . $c['Type'] . "\n";

// Test the exact failing query
echo "\n=== TESTING FIXED QUERIES ===\n";
try {
    $r = db()->query("SELECT country, COUNT(*) as cnt FROM book_downloads GROUP BY country ORDER BY cnt DESC LIMIT 5")->fetchAll();
    echo "  byCountry query: ✅\n";
    $r2 = db()->query("SELECT bd.title, bd.country, bd.subject, bd.downloaded_at, u.name as user_name FROM book_downloads bd LEFT JOIN users u ON u.id=bd.user_id ORDER BY bd.downloaded_at DESC LIMIT 5")->fetchAll();
    echo "  recent query: ✅\n";
} catch (Exception $e) { echo "  ❌ " . $e->getMessage() . "\n"; }

echo "\n✅ Done! Delete install11.php then Ctrl+Shift+R on admin.\n";
echo "</pre><p style='color:red;font-weight:bold;font-family:monospace;padding:20px'>⚠️ DELETE install11.php immediately!</p>";
