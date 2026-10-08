<?php
require_once 'auth.php';

$total_notes    = (int)$pdo->query("SELECT COUNT(*) FROM notes")->fetchColumn();
$total_users    = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
$total_dl       = (int)$pdo->query("SELECT COALESCE(SUM(downloads),0) FROM notes")->fetchColumn();
$storage_used   = (int)$pdo->query("SELECT COALESCE(SUM(filesize),0) FROM notes")->fetchColumn();
$total_cats     = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$total_ratings  = (int)$pdo->query("SELECT COUNT(*) FROM ratings")->fetchColumn();

$recent = $pdo->query("
    SELECT n.*, u.username, c.name AS category_name, c.icon AS category_icon
    FROM notes n 
    JOIN users u ON u.id=n.user_id
    LEFT JOIN categories c ON c.id=n.category_id
    ORDER BY n.created_at DESC 
    LIMIT 6
")->fetchAll();

render_header('Admin Dashboard', 'admin');
?>
<main class="container">
  <div class="page-head">
    <div>
      <h1>Admin Dashboard</h1>
      <p class="sub">Overview of your note library and users.</p>
    </div>
    <a class="btn-primary btn-admin" href="upload.php">+ Upload note</a>
  </div>

  <div class="stats stats-admin">
    <div class="stat-card">
      <div class="stat-icon admin-icon">📝</div>
      <div><div class="stat-num"><?= $total_notes ?></div><div class="stat-label">Total notes</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon admin-icon">👥</div>
      <div><div class="stat-num"><?= $total_users ?></div><div class="stat-label">Registered users</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon admin-icon">⬇</div>
      <div><div class="stat-num"><?= $total_dl ?></div><div class="stat-label">Total downloads</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon admin-icon">💾</div>
      <div><div class="stat-num"><?= format_bytes($storage_used) ?></div><div class="stat-label">Storage used</div></div>
    </div>
  </div>

  <div class="stats stats-admin" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));margin-bottom:40px">
    <div class="stat-card">
      <div class="stat-icon admin-icon">🏷️</div>
      <div><div class="stat-num"><?= $total_cats ?></div><div class="stat-label">Categories</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon admin-icon">⭐</div>
      <div><div class="stat-num"><?= $total_ratings ?></div><div class="stat-label">Total ratings</div></div>
    </div>
  </div>

  <div class="section-head">
    <h2>Recent uploads</h2>
    <a class="link" href="notes.php">Manage all →</a>
  </div>

  <?php if (!$recent): ?>
    <div class="empty-state">
      <div class="empty-emoji">📭</div>
      <h3>No notes yet</h3>
      <p>Upload your first note to get started.</p>
      <a class="btn-primary btn-admin" href="upload.php">Upload a note</a>
    </div>
  <?php else: ?>
    <div class="note-list">
      <?php foreach ($recent as $n): 
        $ext = strtoupper(pathinfo($n['original_name'], PATHINFO_EXTENSION)); 
      ?>
        <div class="note-row">
          <div class="note-thumb admin-thumb"><?= e($ext) ?></div>
          <div class="note-info">
            <h3><?= e($n['title']) ?></h3>
            <p class="desc"><?= e(mb_strimwidth($n['description'] ?? '', 0, 140, '…')) ?></p>
            <div class="meta">
              <span class="badge"><?= e($n['category_icon'] ?? '📁') ?> <?= e($n['category_name'] ?? $n['subject']) ?></span>
              <span>👤 <?= e($n['username']) ?></span>
              <span>⬇ <?= (int)$n['downloads'] ?></span>
              <span><?= format_bytes((int)$n['filesize']) ?></span>
              <span><?= date('M j, Y', strtotime($n['created_at'])) ?></span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
<?php render_footer(); ?>