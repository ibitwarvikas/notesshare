<?php
require_once 'auth.php';

$q = trim($_GET['q'] ?? '');
$subject = trim($_GET['subject'] ?? '');

$sql = "SELECT n.*, u.username,
        COALESCE(AVG(r.rating), 0) AS avg_rating,
        COUNT(r.id) AS rating_count
        FROM notes n 
        JOIN users u ON u.id=n.user_id 
        LEFT JOIN ratings r ON r.note_id=n.id
        WHERE 1=1";
$params = [];

if ($q !== '') {
    $sql .= " AND (n.title LIKE ? OR n.description LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
if ($subject !== '') {
    $sql .= " AND n.subject = ?";
    $params[] = $subject;
}

$sql .= " GROUP BY n.id ORDER BY n.created_at DESC LIMIT 100";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$notes = $stmt->fetchAll();

$subjects = $pdo->query("SELECT DISTINCT subject FROM notes ORDER BY subject")->fetchAll(PDO::FETCH_COLUMN);

render_header('Browse Notes', 'user');
?>
<main class="container">
  <div class="page-head">
    <div>
      <h1>Browse notes</h1>
      <p class="sub"><?= count($notes) ?> note<?= count($notes) === 1 ? '' : 's' ?> available</p>
    </div>
  </div>

  <form class="search-bar search-light" method="get">
    <input type="text" name="q" placeholder="Search notes…" value="<?= e($q) ?>">
    <select name="subject">
      <option value="">All subjects</option>
      <?php foreach ($subjects as $s): ?>
        <option value="<?= e($s) ?>" <?= $subject === $s ? 'selected' : '' ?>><?= e($s) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit">Search</button>
  </form>

  <?php if (!$notes): ?>
    <p class="no-results">No notes found.</p>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($notes as $n):
        $ext = strtoupper(pathinfo($n['original_name'], PATHINFO_EXTENSION));
      ?>
        <article class="card">
          <div class="card-top">
            <span class="badge"><?= e($n['subject']) ?></span>
            <span class="ext"><?= e($ext) ?></span>
          </div>

          <?php if ($n['rating_count'] > 0): ?>
            <div style="margin-bottom:8px;font-size:.82rem;font-weight:700;color:#f59e0b">
              ⭐ <?= number_format((float)$n['avg_rating'], 1) ?>
              <span style="color:#94a3b8;font-weight:500">
                (<?= (int)$n['rating_count'] ?> rating<?= $n['rating_count'] == 1 ? '' : 's' ?>)
              </span>
            </div>
          <?php endif; ?>

          <h3>
            <a href="view_note.php?id=<?= (int)$n['id'] ?>">
              <?= e($n['title']) ?>
            </a>
          </h3>
          <p class="desc"><?= e(mb_strimwidth($n['description'] ?? '', 0, 130, '…')) ?></p>
          <div class="meta">
            <span>👤 <?= e($n['username']) ?></span>
            <span>⬇ <?= (int)$n['downloads'] ?></span>
            <span><?= format_bytes((int)$n['filesize']) ?></span>
          </div>
          <a class="btn-download" href="view_note.php?id=<?= (int)$n['id'] ?>">📖 Read Note</a>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>
<?php render_footer(); ?>