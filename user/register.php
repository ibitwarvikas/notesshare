<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

if (is_user())  redirect('dashboard.php');
if (is_admin()) redirect('../admin/dashboard.php');

$errors = []; $username = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) $errors[] = 'Invalid session.';
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) $errors[] = 'Username must be 3–20 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))        $errors[] = 'Please enter a valid email.';
    if (strlen($password) < 6)                              $errors[] = 'Password must be ≥ 6 characters.';
    if ($password !== $confirm)                             $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $st = $pdo->prepare("SELECT id FROM users WHERE username=? OR email=?");
        $st->execute([$username, $email]);
        if ($st->fetch()) $errors[] = 'Username or email already taken.';
        else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO users (username,email,password_hash,role) VALUES (?,?,?,'user')")
                ->execute([$username, $email, $hash]);
            $_SESSION['user_id'] = (int)$pdo->lastInsertId();
            $_SESSION['role']    = 'user';
            flash('success', 'Welcome to NoteShare!');
            redirect('dashboard.php');
        }
    }
}
render_header('Sign up', 'user');
?>
<main class="container auth-wrap">
  <div class="auth-card">
    <h2>Create your account</h2>
    <p class="sub">Join NoteShare to browse and download study notes.</p>
    <?php if ($errors): ?>
      <div class="alert alert-error"><?php foreach ($errors as $e) echo '<div>• '.e($e).'</div>'; ?></div>
    <?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <label>Username<input type="text" name="username" value="<?= e($username) ?>" required maxlength="20"></label>
      <label>Email<input type="email" name="email" value="<?= e($email) ?>" required></label>
      <label>Password<input type="password" name="password" required minlength="6"></label>
      <label>Confirm password<input type="password" name="confirm" required minlength="6"></label>
      <button class="btn-primary btn-block" type="submit">Create account</button>
    </form>
    <p class="auth-alt">Already registered? <a href="login.php">Sign in</a></p>
  </div>
</main>
<?php render_footer(); ?>