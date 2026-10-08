<?php
require_once __DIR__ . '/config.php';

echo "<style>body{font-family:Arial;padding:30px;background:#f0f0f0}
.box{max-width:800px;margin:0 auto;background:#fff;padding:30px;border-radius:12px;box-shadow:0 2px 10px rgba(0,0,0,.1)}
.ok{background:#d4edda;padding:12px;border-radius:8px;color:#155724;margin:8px 0}
.err{background:#f8d7da;padding:12px;border-radius:8px;color:#721c24;margin:8px 0}
a.btn{display:inline-block;background:#6366f1;color:#fff;padding:12px 24px;text-decoration:none;border-radius:8px;margin:10px 10px 0 0}
</style>";

echo "<div class='box'><h1>🔧 Setup</h1>";

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) UNIQUE NOT NULL,
        icon VARCHAR(10) DEFAULT '📁',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    echo "<div class='ok'>✅ Categories table created</div>";
} catch (PDOException $e) {
    echo "<div class='err'>⚠️ " . $e->getMessage() . "</div>";
}

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS ratings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        note_id INT NOT NULL,
        rating TINYINT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_rating (user_id, note_id),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (note_id) REFERENCES notes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    echo "<div class='ok'>✅ Ratings table created</div>";
} catch (PDOException $e) {
    echo "<div class='err'>⚠️ " . $e->getMessage() . "</div>";
}

try {
    $pdo->exec("ALTER TABLE notes ADD COLUMN category_id INT DEFAULT NULL");
    echo "<div class='ok'>✅ category_id column added</div>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate') !== false) {
        echo "<div class='ok'>ℹ️ category_id already exists</div>";
    } else {
        echo "<div class='err'>⚠️ " . $e->getMessage() . "</div>";
    }
}

$cats = [
    ['Mathematics', '📐'], ['Physics', '⚛️'], ['Computer Science', '💻'],
    ['Chemistry', '🧪'], ['Biology', '🧬'], ['English', '📖'],
    ['History', '🏛️'], ['Economics', '📊'], ['General', '📁'],
];
$added = 0;
foreach ($cats as [$name, $icon]) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, icon) VALUES (?, ?)");
    $stmt->execute([$name, $icon]);
    if ($stmt->rowCount() > 0) $added++;
}
echo "<div class='ok'>✅ {$added} new categories added</div>";

$notes = $pdo->query("SELECT id, subject FROM notes WHERE category_id IS NULL")->fetchAll();
$mapped = 0;
foreach ($notes as $n) {
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
    $stmt->execute([$n['subject']]);
    $cat = $stmt->fetch();
    if ($cat) {
        $pdo->prepare("UPDATE notes SET category_id = ? WHERE id = ?")->execute([$cat['id'], $n['id']]);
        $mapped++;
    }
}
echo "<div class='ok'>✅ {$mapped} notes linked to categories</div>";

echo "<h2>🎉 Setup Complete!</h2>";
echo "<a href='/noteshare/admin/categories.php' class='btn'>→ Categories</a>";
echo "<a href='/noteshare/user/browse.php' class='btn'>→ Browse</a>";
echo "<div class='err' style='margin-top:20px'><b>⚠️ Delete this file!</b></div>";
echo "</div>";