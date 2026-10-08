<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

if (is_user())  redirect('dashboard.php');
if (is_admin()) redirect('../admin/dashboard.php');

$errors = []; $identifier = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) $errors[] = 'Invalid session.';
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';
    if (!$errors && ($identifier === '' || $password === '')) $errors[] = 'Please fill in all fields.';

    if (!$errors) {
        $st = $pdo->prepare("SELECT * FROM users WHERE (username=? OR email=?) AND role='user' LIMIT 1");
        $st->execute([$identifier, $identifier]);
        $u = $st->fetch();
        if ($u && password_verify($password, $u['password_hash'])) {
            $_SESSION['user_id'] = (int)$u['id'];
            $_SESSION['role']    = 'user';
            flash('success', 'Welcome back, '.$u['username'].'!');
            redirect('dashboard.php');
        } else $errors[] = 'Invalid credentials.';
    }
}
render_header('User Login', 'user');
?>
<main class="container auth-wrap">
  <div class="auth-card">
    <h2>User Sign in</h2>
    <p class="sub">Browse and download study notes.</p>
    <?php if ($errors): ?>
      <div class="alert alert-error"><?php foreach ($errors as $e) echo '<div>• '.e($e).'</div>'; ?></div>
    <?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <label>Username or email<input type="text" name="identifier" value="<?= e($identifier) ?>" required></label>
      <label>Password<input type="password" name="password" required></label>
      <button class="btn-primary btn-block" type="submit">Sign in</button>
    </form>
    <p class="auth-alt">New here? <a href="register.php">Create an account</a></p>
    <p class="auth-alt small"><a href="../admin/login.php">Admin login →</a></p>
  </div>
</main>
<?php render_footer(); ?>