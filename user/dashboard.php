<?php
require_once 'auth.php';
$user = current_user();

$latest = $pdo->query("SELECT n.*, u.username FROM notes n JOIN users u ON u.id=n.user_id ORDER BY n.created_at DESC LIMIT 6")->fetchAll();
$total = (int)$pdo->query("SELECT COUNT(*) FROM notes")->fetchColumn();
$subjects_count = (int)$pdo->query("SELECT COUNT(DISTINCT subject) FROM notes")->fetchColumn();
$total_dl = (int)$pdo->query("SELECT COALESCE(SUM(downloads),0) FROM notes")->fetchColumn();

render_header('Dashboard', 'user');
?>
<main class="container">
  <div class="page-head">
    <div>
      <h1>Hi, <?= e($user['username']) ?> 👋</h1>
      <p class="sub">Explore the latest notes shared by our admins.</p>
    </div>
    <a class="btn-primary" href="browse.php">Browse all notes</a>
  </div>

  <div class="stats">
    <div class="stat-card"><div class="stat-icon">📝</div><div><div class="stat-num"><?= $total ?></div><div class="stat-label">Notes available</div></div></div>
    <div class="stat-card"><div class="stat-icon">📂</div><div><div class="stat-num"><?= $subjects_count ?></div><div class="stat-label">Subjects</div></div></div>
    <div class="stat-card"><div class="stat-icon">⬇</div><div><div class="stat-num"><?= $total_dl ?></div><div class="stat-label">Total downloads</div></div></div>
  </div>

  <div class="section-head"><h2>Recently added</h2><a class="link" href="browse.php">View all →</a></div>

  <?php if (!$latest): ?>
    <div class="empty-state"><div class="empty-emoji">📭</div><h3>No notes yet</h3><p>Check back later.</p></div>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($latest as $n): $ext = strtoupper(pathinfo($n['original_name'], PATHINFO_EXTENSION)); ?>
        <article class="card">
          <div class="card-top"><span class="badge"><?= e($n['subject']) ?></span><span class="ext"><?= e($ext) ?></span></div>
          <h3><?= e($n['title']) ?></h3>
          <p class="desc"><?= e(mb_strimwidth($n['description'] ?? '', 0, 120, '…')) ?></p>
          <div class="meta"><span>👤 <?= e($n['username']) ?></span><span>⬇ <?= (int)$n['downloads'] ?></span><span><?= format_bytes((int)$n['filesize']) ?></span></div>
          <a class="btn-download" href="download.php?id=<?= (int)$n['id'] ?>">Download</a>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
<?php render_footer(); ?>