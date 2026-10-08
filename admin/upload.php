<?php
require_once 'auth.php';
global $ALLOWED_NOTE_TYPES;

$user = current_user();
$errors = [];
$title = $description = '';
$category_id = 0;

$cats = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) $errors[] = 'Invalid session.';

    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);

    if ($title === '') $errors[] = 'Title is required.';
    if (strlen($title) > 200) $errors[] = 'Title is too long.';
    if ($category_id <= 0) $errors[] = 'Please select a category.';

    $file = $_FILES['file'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Please choose a file to upload.';
    } elseif ($file['size'] > MAX_FILE_SIZE) {
        $errors[] = 'File exceeds the 10 MB limit.';
    } else {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!array_key_exists($ext, $ALLOWED_NOTE_TYPES))
            $errors[] = 'File type not allowed. Allowed: ' . implode(', ', array_keys($ALLOWED_NOTE_TYPES));
    }

    if (!$errors) {
        $stmt = $pdo->prepare("SELECT name FROM categories WHERE id=?");
        $stmt->execute([$category_id]);
        $subject = $stmt->fetchColumn() ?: 'General';

        $ext    = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $stored = bin2hex(random_bytes(16)) . '.' . $ext;
        $dest   = UPLOAD_DIR . 'notes/' . $stored;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $pdo->prepare("
                INSERT INTO notes 
                (user_id, category_id, title, description, subject, filename, original_name, filesize, filetype)
                VALUES (?,?,?,?,?,?,?,?,?)
            ")->execute([
                $user['id'], $category_id, $title, $description, $subject,
                $stored, $file['name'], $file['size'], $file['type']
            ]);
            flash('success', 'Note uploaded successfully!');
            redirect('notes.php');
        } else {
            $errors[] = 'Failed to save file. Check permissions on /uploads/notes/.';
        }
    }
}

render_header('Upload Note', 'admin');
?>
<main class="container">
  <div class="form-page">
    <div class="form-card admin-form">
      <div class="admin-badge">🛡️ Admin only</div>
      <h2>Upload a new note</h2>
      <p class="sub">This note will appear immediately in the user library.</p>

      <?php if ($errors): ?>
        <div class="alert alert-error">
          <?php foreach ($errors as $e) echo '<div>• ' . e($e) . '</div>'; ?>
        </div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

        <label>Title
          <input type="text" name="title" value="<?= e($title) ?>" required maxlength="200"
                 placeholder="e.g. Data Structures — Complete Notes">
        </label>

        <label>Category
          <select name="category_id" required
                  style="width:100%;padding:12px 15px;margin-top:6px;border:1.5px solid #e2e8f0;border-radius:11px;font-family:inherit;font-size:.95rem;background:#fff">
            <option value="0">— Select category —</option>
            <?php foreach ($cats as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= $category_id === (int)$c['id'] ? 'selected' : '' ?>>
                <?= e($c['icon']) ?> <?= e($c['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>

        <label>Description
          <textarea name="description" rows="4" placeholder="What's inside these notes?"><?= e($description) ?></textarea>
        </label>

        <label>File
          <input type="file" name="file" required>
          <small>PDF · DOC · DOCX · PPT · PPTX · TXT · PNG · JPG · max 10 MB</small>
        </label>

        <button class="btn-primary btn-admin btn-block" type="submit">Upload note</button>
      </form>
    </div>
  </div>
</main>
<?php render_footer(); ?>