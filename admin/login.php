<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

if (is_admin()) redirect('dashboard.php');
if (is_user())  redirect('../user/dashboard.php');

$errors = [];
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf'] ?? '')) $errors[] = 'Invalid session.';
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (!$errors && ($identifier === '' || $password === ''))
        $errors[] = 'Please fill in all fields.';

    if (!$errors) {
        $st = $pdo->prepare("SELECT * FROM users WHERE (username=? OR email=?) AND role='admin' LIMIT 1");
        $st->execute([$identifier, $identifier]);
        $u = $st->fetch();

        if ($u && password_verify($password, $u['password_hash'])) {
            $_SESSION['user_id'] = (int)$u['id'];
            $_SESSION['role']    = 'admin';
            flash('success', 'Welcome, ' . $u['username'] . ' (admin).');
            redirect('dashboard.php');
        } else {
            $errors[] = 'Invalid admin credentials.';
        }
    }
}

render_header('Admin Login', 'admin');
?>
<main class="container auth-wrap">
  <div class="auth-card admin-card">
    <div class="admin-badge">🛡️ Admin Access</div>
    <h2>Administrator Sign in</h2>
    <p class="sub">Only administrators may access this panel.</p>

    <?php if ($errors): ?>
      <div class="alert alert-error">
        <?php foreach ($errors as $e) echo '<div>• ' . e($e) . '</div>'; ?>
      </div>
    <?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
      <label>Username or email
        <input type="text" name="identifier" value="<?= e($identifier) ?>" required autofocus>
      </label>
      <label>Password
        <input type="password" name="password" required>
      </label>
      <button class="btn-primary btn-block btn-admin" type="submit">Sign in as admin</button>
    </form>
    <p class="auth-alt small"><a href="../user/login.php">← Back to user login</a></p>
  </div>
</main>
<?php render_footer(); ?>