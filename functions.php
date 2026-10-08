<?php
// functions.php — helpers & shared layout

function is_logged_in(): bool { return isset($_SESSION['user_id']); }
function is_admin(): bool { return is_logged_in() && ($_SESSION['role'] ?? '') === 'admin'; }
function is_user(): bool  { return is_logged_in() && ($_SESSION['role'] ?? '') === 'user'; }

function current_user(): ?array {
    global $pdo;
    static $u = null;
    if (!is_logged_in()) return null;
    if ($u === null) {
        $st = $pdo->prepare("SELECT * FROM users WHERE id=?");
        $st->execute([$_SESSION['user_id']]);
        $u = $st->fetch() ?: null;
    }
    return $u;
}

function redirect(string $u): void { header("Location: $u"); exit; }

function e(?string $s): string { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function verify_csrf(?string $t): bool {
    return $t && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}

function flash(?string $k = null, ?string $m = null) {
    if ($k !== null && $m !== null) { $_SESSION['flash'][$k] = $m; return null; }
    if ($k === null) return null;
    $v = $_SESSION['flash'][$k] ?? null; unset($_SESSION['flash'][$k]); return $v;
}

function format_bytes(int $b): string {
    if ($b >= 1073741824) return round($b / 1073741824, 2) . ' GB';
    if ($b >= 1048576)    return round($b / 1048576, 2) . ' MB';
    if ($b >= 1024)       return round($b / 1024, 1) . ' KB';
    return $b . ' B';
}

function avatar_url(array $u): string {
    if (!empty($u['avatar'])) {
        $p = UPLOAD_DIR . 'avatars/' . $u['avatar'];
        if (file_exists($p)) return BASE_URL . '/uploads/avatars/' . rawurlencode($u['avatar']);
    }
    $init = strtoupper(substr($u['username'] ?? 'U', 0, 1));
    $isA = ($u['role'] ?? '') === 'admin';
    $c1 = $isA ? '#f59e0b' : '#6366f1';
    $c2 = $isA ? '#ef4444' : '#a855f7';
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80">'
         . "<defs><linearGradient id=\"g\" x1=\"0\" x2=\"1\" y1=\"0\" y2=\"1\">"
         . "<stop offset=\"0\" stop-color=\"$c1\"/>"
         . "<stop offset=\"1\" stop-color=\"$c2\"/>"
         . '</linearGradient></defs>'
         . '<rect width="80" height="80" rx="40" fill="url(#g)"/>'
         . '<text x="50%" y="50%" font-size="34" fill="white" text-anchor="middle" '
         . 'dy=".35em" font-family="Arial" font-weight="700">' . $init . '</text></svg>';
    return 'data:image/svg+xml,' . rawurlencode($svg);
}

function render_header(string $title, string $panel = 'user'): void {
    $user = current_user();
    $ok   = flash('success');
    $err  = flash('error');
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · NoteShare<?= $panel === 'admin' ? ' Admin' : '' ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/style.css">
</head>
<body class="panel-<?= e($panel) ?>">
<header class="navbar <?= $panel === 'admin' ? 'navbar-admin' : '' ?>">
  <div class="nav-inner">
    <a href="<?= BASE_URL ?>/index.php" class="brand">
      <span class="brand-icon"><?= $panel === 'admin' ? '🛡️' : '📚' ?></span>
      <span>NoteShare<?= $panel === 'admin' ? ' <small>Admin</small>' : '' ?></span>
    </a>
    <nav class="nav-links">
      <?php if ($panel === 'admin'): ?>
        <a href="dashboard.php">Dashboard</a>
        <a href="upload.php">Upload</a>
        <a href="notes.php">Notes</a>
        <a href="categories.php">Categories</a>
        <a href="users.php">Users</a>
      <?php elseif (is_logged_in()): ?>
        <a href="dashboard.php">Dashboard</a>
        <a href="browse.php">Browse</a>
      <?php endif; ?>

      <?php if ($user): ?>
        <div class="user-menu">
          <img src="<?= e(avatar_url($user)) ?>" class="avatar-sm" alt="avatar">
          <div class="dropdown">
            <div class="dd-head">
              <strong><?= e($user['username']) ?></strong>
              <small><?= e($user['role']) ?> · <?= e($user['email']) ?></small>
            </div>
            <a href="profile.php">Profile</a>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php" class="danger">Logout</a>
          </div>
        </div>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/user/login.php">User Login</a>
        <a href="<?= BASE_URL ?>/admin/login.php" class="btn-nav">Admin Login</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<?php if ($ok):  ?><div class="flash flash-success"><?= e($ok) ?></div><?php endif; ?>
<?php if ($err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endif; ?>
<?php }

function render_footer(): void { ?>
<footer class="site-footer">
  <div class="container">© <?= date('Y') ?> NoteShare — Share knowledge freely.</div>
</footer>
<script>
  // Flash messages auto-hide after 3 seconds
  document.querySelectorAll('.flash').forEach(function(el) {
    setTimeout(function() {
      el.style.transition = 'opacity 0.5s ease, transform 0.5s ease, max-height 0.5s ease, padding 0.5s ease';
      el.style.opacity = '0';
      el.style.transform = 'translateY(-10px)';
      el.style.maxHeight = '0';
      el.style.padding = '0 20px';
      el.style.overflow = 'hidden';
      setTimeout(function() { el.remove(); }, 500);
    }, 3000);
  });
</script>
</body></html>
<?php }