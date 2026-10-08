<?php
require_once 'auth.php';
$user = current_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) $errors[] = 'Invalid session.';
    $action = $_POST['action'] ?? '';

    if (!$errors && $action === 'profile') {
        $username = trim($_POST['username'] ?? '');
        $bio      = trim($_POST['bio'] ?? '');
        if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) $errors[] = 'Username must be 3–20 chars.';
        else {
            $st = $pdo->prepare("SELECT id FROM users WHERE username=? AND id<>?");
            $st->execute([$username, $user['id']]);
            if ($st->fetch()) $errors[] = 'Username already taken.';

            $avatar = $user['avatar'];
            if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, ['png','jpg','jpeg','gif','webp'])) $errors[] = 'Avatar must be an image.';
                elseif ($_FILES['avatar']['size'] > 2*1024*1024) $errors[] = 'Avatar must be under 2 MB.';
                else {
                    $new = 'u'.$user['id'].'_'.bin2hex(random_bytes(6)).'.'.$ext;
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], UPLOAD_DIR.'avatars/'.$new)) {
                        if ($avatar && file_exists(UPLOAD_DIR.'avatars/'.$avatar)) @unlink(UPLOAD_DIR.'avatars/'.$avatar);
                        $avatar = $new;
                    }
                }
            }
            if (!$errors) {
                $pdo->prepare("UPDATE users SET username=?, bio=?, avatar=? WHERE id=?")->execute([$username, $bio, $avatar, $user['id']]);
                flash('success', 'Profile updated.');
                redirect('profile.php');
            }
        }
    }

    if (!$errors && $action === 'password') {
        $cur = $_POST['current'] ?? ''; $new = $_POST['new'] ?? ''; $cfm = $_POST['confirm'] ?? '';
        if (!password_verify($cur, $user['password_hash'])) $errors[] = 'Current password is incorrect.';
        elseif (strlen($new) < 6) $errors[] = 'New password must be ≥ 6 chars.';
        elseif ($new !== $cfm)   $errors[] = 'Passwords do not match.';
        else {
            $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            flash('success', 'Password changed.');
            redirect('profile.php');
        }
    }
}
$st = $pdo->prepare("SELECT * FROM users WHERE id=?"); $st->execute([$user['id']]); $user = $st->fetch();

render_header('My Profile', 'user');
?>
<main class="container">
  <div class="profile-wrap">
    <aside class="profile-side">
      <img class="avatar-lg" src="<?= e(avatar_url($user)) ?>" alt="">
      <h2><?= e($user['username']) ?></h2>
      <p class="muted"><?= e($user['email']) ?></p>
      <?php if ($user['bio']): ?><p class="bio"><?= nl2br(e($user['bio'])) ?></p><?php endif; ?>
      <p class="muted small">User · Member since <?= date('M Y', strtotime($user['created_at'])) ?></p>
    </aside>
    <section class="profile-main">
      <?php if ($errors): ?><div class="alert alert-error"><?php foreach ($errors as $e) echo '<div>• '.e($e).'</div>'; ?></div><?php endif; ?>
      <div class="form-card">
        <h2>Edit profile</h2>
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="profile">
          <label>Username<input type="text" name="username" value="<?= e($user['username']) ?>" required></label>
          <label>Bio<textarea name="bio" rows="3"><?= e($user['bio']) ?></textarea></label>
          <label>Avatar<input type="file" name="avatar" accept="image/*"><small>PNG/JPG/GIF/WEBP · max 2 MB</small></label>
          <button class="btn-primary" type="submit">Save changes</button>
        </form>
      </div>
      <div class="form-card">
        <h2>Change password</h2>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
          <input type="hidden" name="action" value="password">
          <label>Current password<input type="password" name="current" required></label>
          <label>New password<input type="password" name="new" required minlength="6"></label>
          <label>Confirm<input type="password" name="confirm" required minlength="6"></label>
          <button class="btn-primary" type="submit">Update password</button>
        </form>
      </div>
    </section>
  </div>
</main>
<?php render_footer(); ?>