<?php
require_once 'auth.php';
$me = current_user();

// Change role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'role') {
    if (!verify_csrf($_POST['csrf'] ?? '')) flash('error', 'Invalid session.');
    else {
        $id = (int)$_POST['id'];
        $role = $_POST['role'] ?? 'user';
        if (in_array($role, ['admin', 'user']) && $id !== $me['id']) {
            $pdo->prepare("UPDATE users SET role=? WHERE id=?")->execute([$role, $id]);
            flash('success', 'User role updated.');
        } else {
            flash('error', 'Cannot change that role.');
        }
    }
    redirect('users.php');
}

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!verify_csrf($_POST['csrf'] ?? '')) flash('error', 'Invalid session.');
    else {
        $id = (int)$_POST['id'];
        if ($id === $me['id']) {
            flash('error', 'You cannot delete your own account here.');
        } else {
            $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
            flash('success', 'User deleted.');
        }
    }
    redirect('users.php');
}

$users = $pdo->query("
    SELECT u.*,
        (SELECT COUNT(*) FROM notes WHERE user_id = u.id) AS note_count,
        (SELECT COUNT(*) FROM ratings WHERE user_id = u.id) AS rating_count
    FROM users u 
    ORDER BY u.role DESC, u.created_at DESC
")->fetchAll();

render_header('Users', 'admin');
?>
<main class="container">
  <div class="page-head">
    <div>
      <h1>Users</h1>
      <p class="sub"><?= count($users) ?> account<?= count($users) === 1 ? '' : 's' ?></p>
    </div>
  </div>

  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>User</th>
          <th>Email</th>
          <th>Role</th>
          <th>Notes</th>
          <th>Ratings</th>
          <th>Joined</th>
          <th style="text-align:right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td>
              <div class="user-cell">
                <img src="<?= e(avatar_url($u)) ?>" class="avatar-xs" alt="">
                <strong><?= e($u['username']) ?></strong>
                <?php if ($u['id'] === $me['id']): ?><span class="you-tag">you</span><?php endif; ?>
              </div>
            </td>
            <td class="muted"><?= e($u['email']) ?></td>
            <td>
              <span class="role-tag role-<?= e($u['role']) ?>"><?= e($u['role']) ?></span>
            </td>
            <td><?= (int)$u['note_count'] ?></td>
            <td><?= (int)$u['rating_count'] ?></td>
            <td class="muted"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
            <td style="text-align:right">
              <?php if ($u['id'] !== $me['id']): ?>
                <form method="post" style="display:inline">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="action" value="role">
                  <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                  <input type="hidden" name="role" value="<?= $u['role'] === 'admin' ? 'user' : 'admin' ?>">
                  <button class="btn-ghost" type="submit">
                    <?= $u['role'] === 'admin' ? 'Demote' : 'Promote' ?>
                  </button>
                </form>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete this user and all their notes?')">
                  <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                  <button class="btn-danger" type="submit">Delete</button>
                </form>
              <?php else: ?>
                <span class="muted small">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>
<?php render_footer(); ?>