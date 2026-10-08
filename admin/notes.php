<?php
require_once 'auth.php';

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!verify_csrf($_POST['csrf'] ?? '')) flash('error', 'Invalid session.');
    else {
        $id = (int)($_POST['id'] ?? 0);
        $st = $pdo->prepare("SELECT * FROM notes WHERE id=?");
        $st->execute([$id]);
        if ($n = $st->fetch()) {
            @unlink(UPLOAD_DIR . 'notes/' . $n['filename']);
            $pdo->prepare("DELETE FROM notes WHERE id=?")->execute([$id]);
            flash('success', 'Note deleted.');
        } else {
            flash('error', 'Note not found.');
        }
    }
    redirect('notes.php');
}

// Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    if (!verify_csrf($_POST['csrf'] ?? '')) flash('error', 'Invalid session.');
    else {
        $id = (int)$_POST['id'];
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);

        // Get subject from category
        $stmt = $pdo->prepare("SELECT name FROM categories WHERE id=?");
        $stmt->execute([$category_id]);
        $subject = $stmt->fetchColumn() ?: 'General';

        $pdo->prepare("UPDATE notes SET title=?, description=?, category_id=?, subject=? WHERE id=?")
            ->execute([$title, $description, $category_id ?: null, $subject, $id]);
        flash('success', 'Note updated.');
    }
    redirect('notes.php');
}

$filter_cat = (int)($_GET['cat'] ?? 0);
$sql = "SELECT n.*, u.username, c.name AS category_name, c.icon AS category_icon,
        (SELECT AVG(rating) FROM ratings WHERE note_id=n.id) AS avg_rating,
        (SELECT COUNT(*) FROM ratings WHERE note_id=n.id) AS rating_count
        FROM notes n 
        JOIN users u ON u.id=n.user_id 
        LEFT JOIN categories c ON c.id=n.category_id";
$params = [];
if ($filter_cat > 0) {
    $sql .= " WHERE n.category_id = ?";
    $params[] = $filter_cat;
}
$sql .= " ORDER BY n.created_at DESC";

$st = $pdo->prepare($sql);
$st->execute($params);
$notes = $st->fetchAll();

$cats = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

render_header('Manage Notes', 'admin');
?>
<main class="container">
  <div class="page-head">
    <div>
      <h1>Manage Notes</h1>
      <p class="sub"><?= count($notes) ?> note<?= count($notes) === 1 ? '' : 's' ?> in the library</p>
    </div>
    <a class="btn-primary btn-admin" href="upload.php">+ Upload</a>
  </div>

  <!-- Category filter -->
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:24px">
    <a href="notes.php" style="padding:8px 16px;border-radius:999px;font-size:.85rem;font-weight:600;text-decoration:none;
       <?= $filter_cat === 0 ? 'background:linear-gradient(135deg,#f59e0b,#ef4444);color:#fff' : 'background:#f1f5f9;color:#475569' ?>">
      All
    </a>
    <?php foreach ($cats as $c): ?>
      <a href="notes.php?cat=<?= (int)$c['id'] ?>" style="padding:8px 16px;border-radius:999px;font-size:.85rem;font-weight:600;text-decoration:none;
         <?= $filter_cat === (int)$c['id'] ? 'background:linear-gradient(135deg,#f59e0b,#ef4444);color:#fff' : 'background:#f1f5f9;color:#475569' ?>">
        <?= e($c['icon']) ?> <?= e($c['name']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (!$notes): ?>
    <div class="empty-state">
      <div class="empty-emoji">📭</div>
      <h3>No notes yet</h3>
      <a class="btn-primary btn-admin" href="upload.php">Upload a note</a>
    </div>
  <?php else: ?>
    <div class="note-list">
      <?php foreach ($notes as $n):
        $ext = strtoupper(pathinfo($n['original_name'], PATHINFO_EXTENSION));
      ?>
        <div class="note-row">
          <div class="note-thumb admin-thumb"><?= e($ext) ?></div>
          <div class="note-info">
            <h3><?= e($n['title']) ?></h3>
            <p class="desc"><?= e(mb_strimwidth($n['description'] ?? '', 0, 150, '…')) ?></p>
            <div class="meta">
              <span class="badge"><?= e($n['category_icon'] ?? '📁') ?> <?= e($n['category_name'] ?? $n['subject']) ?></span>
              <span>👤 <?= e($n['username']) ?></span>
              <span>⬇ <?= (int)$n['downloads'] ?></span>
              <span><?= format_bytes((int)$n['filesize']) ?></span>
              <?php if ($n['rating_count'] > 0): ?>
                <span style="color:#f59e0b;font-weight:700">⭐ <?= number_format((float)$n['avg_rating'], 1) ?> (<?= (int)$n['rating_count'] ?>)</span>
              <?php endif; ?>
              <span><?= date('M j, Y', strtotime($n['created_at'])) ?></span>
            </div>
          </div>
          <div class="note-actions">
            <a class="btn-ghost" href="../user/view_note.php?id=<?= (int)$n['id'] ?>">Preview</a>
            <button class="btn-ghost" onclick='openEdit(<?= htmlspecialchars(json_encode($n), ENT_QUOTES) ?>)'>Edit</button>
            <form method="post" onsubmit="return confirm('Delete this note permanently?')" style="display:inline">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
              <button class="btn-danger" type="submit">Delete</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<!-- Edit modal -->
<div id="editModal" class="modal">
  <div class="modal-box">
    <h3>Edit note</h3>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="e-id">
      <label>Title<input type="text" name="title" id="e-title" required></label>
      <label>Description<textarea name="description" id="e-desc" rows="3"></textarea></label>
      <label>Category
        <select name="category_id" id="e-cat" style="width:100%;padding:11px;border:1.5px solid #e2e8f0;border-radius:10px;margin-top:5px;font-family:inherit">
          <option value="0">— None —</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= (int)$c['id'] ?>"><?= e($c['icon']) ?> <?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <div class="modal-actions">
        <button type="button" class="btn-ghost" onclick="closeEdit()">Cancel</button>
        <button type="submit" class="btn-primary btn-admin">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEdit(n) {
  document.getElementById('e-id').value = n.id;
  document.getElementById('e-title').value = n.title;
  document.getElementById('e-desc').value = n.description || '';
  document.getElementById('e-cat').value = n.category_id || 0;
  document.getElementById('editModal').classList.add('open');
}
function closeEdit() { document.getElementById('editModal').classList.remove('open'); }
</script>
<?php render_footer(); ?>