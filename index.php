<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

if (is_admin()) redirect('admin/dashboard.php');
if (is_user())  redirect('user/dashboard.php');

// Categories मिळवा
$cats = $pdo->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM notes WHERE category_id = c.id) AS cnt 
    FROM categories c 
    ORDER BY c.name
")->fetchAll();

// Recent notes
$recent = $pdo->query("
    SELECT n.*, c.icon AS cat_icon, c.name AS cat_name,
           (SELECT AVG(rating) FROM ratings WHERE note_id = n.id) AS avg_rating
    FROM notes n 
    LEFT JOIN categories c ON c.id = n.category_id
    ORDER BY n.created_at DESC 
    LIMIT 6
")->fetchAll();

$total_notes = (int)$pdo->query("SELECT COUNT(*) FROM notes")->fetchColumn();
$total_cats  = (int)$pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

render_header('Welcome');
?>

<section class="hero">
  <div class="container">
    <h1>📚 NoteShare</h1>
    <p>A curated library of study notes.<br>Browse freely — admins upload, everyone learns.</p>
    <div class="hero-buttons">
      <?php if (is_logged_in()): ?>
        <a class="btn-primary" href="<?= BASE_URL ?>/user/dashboard.php">Go to Dashboard</a>
      <?php else: ?>
        <a class="btn-primary" href="<?= BASE_URL ?>/user/register.php">Create user account</a>
        <a class="btn-ghost-lg" href="<?= BASE_URL ?>/user/login.php">Sign in</a>
      <?php endif; ?>
    </div>
  </div>
</section>

<main class="container">

  <!-- Stats -->
  <div class="stats" style="margin-top:30px;margin-bottom:40px">
    <div class="stat-card">
      <div class="stat-icon">📝</div>
      <div><div class="stat-num"><?= $total_notes ?></div><div class="stat-label">Notes available</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">🏷️</div>
      <div><div class="stat-num"><?= $total_cats ?></div><div class="stat-label">Categories</div></div>
    </div>
    <div class="stat-card">
      <div class="stat-icon">👥</div>
      <div><div class="stat-num">Free</div><div class="stat-label">For everyone</div></div>
    </div>
  </div>

  <!-- Categories -->
  <div class="section-head">
    <h2>Browse by category</h2>
  </div>

  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;margin-bottom:40px">
    <?php foreach ($cats as $c): ?>
      <div style="background:#fff;border:1px solid var(--border);border-radius:14px;padding:20px;text-align:center;transition:.2s"
           onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 10px 24px -10px rgba(99,102,241,.4)'"
           onmouseout="this.style.transform='';this.style.boxShadow=''">
        <div style="font-size:2rem;margin-bottom:8px"><?= e($c['icon']) ?></div>
        <div style="font-weight:700;color:#0f172a;font-size:.9rem"><?= e($c['name']) ?></div>
        <div style="font-size:.75rem;color:#94a3b8;margin-top:2px"><?= (int)$c['cnt'] ?> note(s)</div>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Recent Notes -->
  <div class="section-head">
    <h2>Latest notes</h2>
  </div>

  <?php if (!$recent): ?>
    <p class="no-results">No notes yet.</p>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($recent as $n): ?>
        <article class="card">
          <div class="card-top">
            <span class="badge"><?= e($n['cat_icon'] ?? '📁') ?> <?= e($n['cat_name'] ?? $n['subject']) ?></span>
            <?php if (!empty($n['avg_rating'])): ?>
              <span style="font-size:.78rem;font-weight:700;color:#f59e0b">⭐ <?= number_format((float)$n['avg_rating'], 1) ?></span>
            <?php endif; ?>
          </div>
          <h3><?= e($n['title']) ?></h3>
          <p class="desc"><?= e(mb_strimwidth($n['description'] ?? '', 0, 120, '…')) ?></p>
          <div class="meta">
            <span>⬇ <?= (int)$n['downloads'] ?></span>
            <span><?= format_bytes((int)$n['filesize']) ?></span>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Login CTA -->
  <div class="split-cards" style="margin-top:50px">
    <div class="split-card">
      <div class="split-icon">👤</div>
      <h2>User Panel</h2>
      <p>Register, browse, download notes, and manage your profile.</p>
      <a class="btn-primary" href="<?= BASE_URL ?>/user/login.php">User login →</a>
    </div>
    <div class="split-card admin">
      <div class="split-icon">🛡️</div>
      <h2>Admin Panel</h2>
      <p>Upload notes, manage categories, and oversee users.</p>
      <a class="btn-primary" href="<?= BASE_URL ?>/admin/login.php">Admin login →</a>
    </div>
  </div>
</main>

<?php render_footer(); ?>