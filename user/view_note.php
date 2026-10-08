<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

if (!is_logged_in()) {
    flash('error', 'Please sign in.');
    redirect('login.php');
}

$id = (int)($_GET['id'] ?? 0);

// Fetch note with category
$stmt = $pdo->prepare("
    SELECT n.*, u.username, c.name AS category_name, c.icon AS category_icon
    FROM notes n 
    JOIN users u ON u.id = n.user_id 
    LEFT JOIN categories c ON c.id = n.category_id
    WHERE n.id = ?
");
$stmt->execute([$id]);
$note = $stmt->fetch();

if (!$note) {
    http_response_code(404);
    die('Note not found.');
}

$content = $note['content'] ?? '';
$has_content = !empty(trim($content));

// Rating stats
$stmt = $pdo->prepare("SELECT AVG(rating) AS avg_r, COUNT(*) AS cnt FROM ratings WHERE note_id=?");
$stmt->execute([$id]);
$rdata = $stmt->fetch();
$avg_rating = round((float)$rdata['avg_r'], 1);
$rating_count = (int)$rdata['cnt'];

// My rating
$user = current_user();
$stmt = $pdo->prepare("SELECT rating FROM ratings WHERE user_id=? AND note_id=?");
$stmt->execute([$user['id'], $id]);
$my_rating = (int)($stmt->fetchColumn() ?: 0);

render_header($note['title'], 'user');
?>

<style>
.note-page{max-width:900px;margin:0 auto;background:#fff;border:1px solid var(--border);border-radius:20px;overflow:hidden;box-shadow:var(--shadow-md)}
.note-hero{background:linear-gradient(135deg,#6366f1 0%,#a855f7 50%,#ec4899 100%);color:#fff;padding:50px 44px 40px;position:relative;overflow:hidden}
.note-hero::before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 20% 30%,rgba(255,255,255,.15),transparent 40%);pointer-events:none}
.note-hero .badge-hero{display:inline-block;background:rgba(255,255,255,.25);color:#fff;font-size:.75rem;font-weight:700;padding:6px 14px;border-radius:999px;letter-spacing:.5px;text-transform:uppercase;margin-bottom:16px;backdrop-filter:blur(10px)}
.note-hero h1{font-size:2rem;font-weight:900;letter-spacing:-.02em;margin-bottom:12px;line-height:1.2;position:relative}
.note-hero .meta{font-size:.88rem;opacity:.92;display:flex;gap:20px;flex-wrap:wrap;position:relative;align-items:center}
.note-hero .hero-rating{display:flex;align-items:center;gap:6px;background:rgba(255,255,255,.2);padding:6px 14px;border-radius:999px;backdrop-filter:blur(10px)}
.note-body{padding:44px}
.note-content{font-size:1rem;line-height:1.9;color:#1e293b;white-space:pre-wrap;font-family:'SF Mono','Monaco',Consolas,monospace;background:#f8fafc;padding:36px;border-radius:14px;border:1px solid #e2e8f0;overflow-x:auto}
.note-content .section{display:block;font-family:Inter,sans-serif;font-size:1.2rem;font-weight:800;color:#6366f1;margin:28px 0 12px;padding-bottom:8px;border-bottom:2px solid #e2e8f0}
.note-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:28px;padding-top:24px;border-top:1px solid #e2e8f0}

/* Rating widget */
.rating-box{background:#f8fafc;border:1px solid var(--border);border-radius:16px;padding:28px;margin-top:28px;text-align:center}
.rating-box h3{font-size:1.1rem;font-weight:800;margin-bottom:6px}
.rating-box .sub{color:var(--muted);font-size:.85rem;margin-bottom:18px}
.rating-summary{display:flex;align-items:center;justify-content:center;gap:20px;margin-bottom:20px;flex-wrap:wrap}
.rating-big{font-size:3rem;font-weight:900;color:#0f172a;line-height:1;letter-spacing:-.03em}
.rating-meta{text-align:left}
.stars{display:inline-flex;gap:6px;font-size:2.4rem;user-select:none;line-height:1}
.stars .star{color:#cbd5e1;cursor:pointer;transition:transform .12s ease,color .12s ease}
.stars .star.filled{color:#f59e0b}
.stars:not(.read-only) .star:hover{transform:scale(1.15)}
.stars.read-only{cursor:default}
.stars.read-only .star{cursor:default;transition:none}
.rating-msg{margin-top:12px;font-size:.88rem;color:#10b981;font-weight:600;min-height:20px}
@media(max-width:600px){.note-hero{padding:36px 24px}.note-body{padding:24px}.note-hero h1{font-size:1.5rem}}
</style>

<main class="container">
  <a href="browse.php" style="display:inline-block;margin-bottom:20px;color:#64748b;font-weight:500;font-size:.9rem">← Back to browse</a>

  <article class="note-page">
    <div class="note-hero">
      <span class="badge-hero">
        <?= e($note['category_icon'] ?? '📁') ?> <?= e($note['category_name'] ?? $note['subject']) ?>
      </span>
      <h1><?= e($note['title']) ?></h1>
      <div class="meta">
        <span>👤 <?= e($note['username']) ?></span>
        <span>📅 <?= date('F j, Y', strtotime($note['created_at'])) ?></span>
        <span>⬇ <?= (int)$note['downloads'] ?> downloads</span>
        <?php if ($rating_count > 0): ?>
          <span class="hero-rating">⭐ <?= $avg_rating ?> (<?= $rating_count ?>)</span>
        <?php endif; ?>
      </div>
    </div>

    <div class="note-body">
      <?php if ($note['description']): ?>
        <p style="font-size:1.02rem;color:#475569;font-style:italic;margin-bottom:28px;padding-left:20px;border-left:4px solid #6366f1">
          <?= e($note['description']) ?>
        </p>
      <?php endif; ?>

      <?php if ($has_content): ?>
        <div class="note-content"><?php
          $formatted = e($content);
          $formatted = preg_replace('/^(\d+\.\s+[A-Z][A-Z\s&\-]+)$/m', '<span class="section">$1</span>', $formatted);
          echo $formatted;
        ?></div>
      <?php else: ?>
        <div style="text-align:center;padding:60px 20px;color:#64748b">
          <div style="font-size:3rem;margin-bottom:16px">📄</div>
          <h3>Preview not available</h3>
          <p>Download the PDF to view.</p>
        </div>
      <?php endif; ?>

      <div class="note-actions">
        <a class="btn-primary" href="download.php?id=<?= (int)$note['id'] ?>">⬇ Download PDF</a>
        <a class="btn-ghost" href="browse.php">Browse more</a>
      </div>

      <!-- RATING BOX -->
      <div class="rating-box">
        <h3>⭐ Rate this note</h3>
        <p class="sub">Your feedback helps others find quality notes</p>

        <div class="rating-summary">
          <div class="rating-big" id="avgNum"><?= $rating_count ? $avg_rating : '—' ?></div>
          <div class="rating-meta">
            <div class="stars read-only" id="avgStars">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <span class="star <?= $i <= round($avg_rating) ? 'filled' : '' ?>">★</span>
              <?php endfor; ?>
            </div>
            <div style="font-size:.85rem;color:#64748b;margin-top:4px" id="avgCount">
              <?= $rating_count ?> rating<?= $rating_count == 1 ? '' : 's' ?>
            </div>
          </div>
        </div>

        <div style="border-top:1px solid #e2e8f0;padding-top:20px">
          <p style="font-size:.85rem;color:#64748b;margin-bottom:12px">
            <?= $my_rating > 0 ? "You rated this {$my_rating}/5 — click to change" : "Tap a star to rate:" ?>
          </p>
          <div class="stars" id="userStars" data-note="<?= (int)$note['id'] ?>">
            <?php for ($i = 1; $i <= 5; $i++): ?>
              <span class="star <?= $i <= $my_rating ? 'filled' : '' ?>" data-value="<?= $i ?>">★</span>
            <?php endfor; ?>
          </div>
          <div class="rating-msg" id="ratingMsg"></div>
        </div>
      </div>
    </div>
  </article>
</main>

<script>
(function() {
    const starsEl = document.getElementById('userStars');
    const stars = starsEl.querySelectorAll('.star');
    const noteId = starsEl.dataset.note;
    const msg = document.getElementById('ratingMsg');
    let currentRating = <?= $my_rating ?>;

    function paint(n) {
        stars.forEach((s, i) => s.classList.toggle('filled', i < n));
    }

    stars.forEach(star => {
        star.addEventListener('mouseenter', () => paint(parseInt(star.dataset.value)));
        star.addEventListener('click', () => {
            const value = parseInt(star.dataset.value);
            starsEl.style.pointerEvents = 'none';

            fetch('rate.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'note_id=' + encodeURIComponent(noteId) + '&rating=' + value
            })
            .then(r => r.json())
            .then(data => {
                starsEl.style.pointerEvents = 'auto';
                if (data.error) {
                    msg.style.color = '#ef4444';
                    msg.textContent = '❌ ' + data.error;
                    return;
                }
                currentRating = value;
                msg.style.color = '#10b981';
                msg.textContent = '✅ Thanks for rating ' + value + ' star' + (value > 1 ? 's' : '') + '!';
                document.getElementById('avgNum').textContent = data.avg;
                document.getElementById('avgCount').textContent = data.count + ' rating' + (data.count === 1 ? '' : 's');
                document.querySelectorAll('#avgStars .star').forEach((s, i) => {
                    s.classList.toggle('filled', i < Math.round(data.avg));
                });
                paint(value);
            })
            .catch(() => {
                starsEl.style.pointerEvents = 'auto';
                msg.style.color = '#ef4444';
                msg.textContent = '❌ Error saving rating.';
            });
        });
    });

    starsEl.addEventListener('mouseleave', () => paint(currentRating));
})();
</script>

<?php render_footer(); ?>