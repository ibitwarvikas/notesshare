<?php
require_once 'auth.php';

// Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    if (!verify_csrf($_POST['csrf'] ?? '')) flash('error', 'Invalid session.');
    else {
        $name = trim($_POST['name'] ?? '');
        $icon = trim($_POST['icon'] ?? '📁');
        if ($name === '') flash('error', 'Name is required.');
        else {
            try {
                $pdo->prepare("INSERT INTO categories (name, icon) VALUES (?, ?)")->execute([$name, $icon]);
                flash('success', 'Category added.');
            } catch (PDOException $e) {
                flash('error', 'Category already exists.');
            }
        }
    }
    redirect('categories.php');
}

// Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    if (!verify_csrf($_POST['csrf'] ?? '')) flash('error', 'Invalid session.');
    else {
        $id = (int)$_POST['id'];
        $pdo->prepare("UPDATE categories SET name=?, icon=? WHERE id=?")
            ->execute([trim($_POST['name']), trim($_POST['icon']), $id]);
        flash('success', 'Category updated.');
    }
    redirect('categories.php');
}

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!verify_csrf($_POST['csrf'] ?? '')) flash('error', 'Invalid session.');
    else {
        $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([(int)$_POST['id']]);
        flash('success', 'Category deleted.');
    }
    redirect('categories.php');
}

$cats = $pdo->query("
    SELECT c.*, (SELECT COUNT(*) FROM notes WHERE category_id = c.id) AS note_count
    FROM categories c 
    ORDER BY c.name
")->fetchAll();

render_header('Categories', 'admin');
?>
<main class="container">
  <div class="page-head">
    <div>
      <h1>🏷️ Categories</h1>
      <p class="sub"><?= count($cats) ?> categories · Manage note subjects</p>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 2fr;gap:24px;align-items:start">

    <!-- Add form -->
    <div class="form-card admin-form" style="margin:0;position:sticky;top:88px">
      <h2>Add new category</h2>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="add">
        <label>Icon (emoji)
          <input type="text" name="icon" value="📁" maxlength="4" style="font-size:1.4rem;text-align:center">
          <small>Examples: 📐 ⚛️ 💻 🧪 🧬 📖 🏛️ 📊</small>
        </label>
        <label>Category Name
          <input type="text" name="name" placeholder="e.g. Mathematics" required maxlength="100">
        </label>
        <button class="btn-primary btn-admin" type="submit">+ Add category</button>
      </form>

      <div style="margin-top:24px;padding:16px;background:#fffbeb;border-radius:12px;border:1px solid #fde68a">
        <div style="font-size:.78rem;color:#d97706;font-weight:800;letter-spacing:.4px;text-transform:uppercase;margin-bottom:8px">💡 Popular icons</div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;font-size:1.4rem">
          <span>📐</span><span>⚛️</span><span>💻</span><span>🧪</span><span>🧬</span>
          <span>📖</span><span>🏛️</span><span>📊</span><span>🎨</span><span>🎵</span>
          <span>🌍</span><span>⚖️</span><span>💼</span><span>🔬</span><span>📚</span>
        </div>
      </div>
    </div>

    <!-- List -->
    <div>
      <?php if (!$cats): ?>
        <div class="empty-state">
          <div class="empty-emoji">🏷️</div>
          <h3>No categories yet</h3>
          <p>Add your first category using the form.</p>
        </div>
      <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px">
          <?php foreach ($cats as $c): ?>
            <div style="background:#fff;border:1px solid var(--border);border-radius:14px;padding:20px;transition:.15s"
                 onmouseover="this.style.boxShadow='0 8px 24px -8px rgba(0,0,0,.1)';this.style.borderColor='#fde68a'"
                 onmouseout="this.style.boxShadow='none';this.style.borderColor='var(--border)'">
              <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px">
                <div style="width:48px;height:48px;border-radius:12px;background:linear-gradient(135deg,#fef3c7,#fed7aa);display:flex;align-items:center;justify-content:center;font-size:1.5rem">
                  <?= e($c['icon']) ?>
                </div>
                <div style="flex:1">
                  <div style="font-weight:800;font-size:1rem"><?= e($c['name']) ?></div>
                  <div style="font-size:.78rem;color:#94a3b8"><?= (int)$c['note_count'] ?> note(s)</div>
                </div>
              </div>

              <div style="display:flex;gap:6px">
                <button class="btn-ghost" style="flex:1;font-size:.78rem"
                        onclick='openEdit(<?= json_encode($c, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>✏️ Edit</button>
                <form method="post" style="flex:1" onsubmit="return confirm('Delete this category? Notes will become uncategorized.')">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                  <button class="btn-danger" style="width:100%;font-size:.78rem">🗑</button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<!-- Edit modal -->
<div id="editModal" class="modal">
  <div class="modal-box">
    <h3>Edit category</h3>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" id="e-id">
      <label>Icon
        <input type="text" name="icon" id="e-icon" maxlength="4" style="font-size:1.4rem;text-align:center">
      </label>
      <label>Name
        <input type="text" name="name" id="e-name" required>
      </label>
      <div class="modal-actions">
        <button type="button" class="btn-ghost" onclick="closeEdit()">Cancel</button>
        <button type="submit" class="btn-primary btn-admin">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
function openEdit(c) {
  document.getElementById('e-id').value = c.id;
  document.getElementById('e-icon').value = c.icon || '📁';
  document.getElementById('e-name').value = c.name;
  document.getElementById('editModal').classList.add('open');
}
function closeEdit() { document.getElementById('editModal').classList.remove('open'); }
</script>
<?php render_footer(); ?>