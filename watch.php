<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$id = (int)($_GET['v'] ?? 0);

// ভিডিও লোড
$stmt = $pdo->prepare('SELECT v.*, u.name AS author FROM videos v JOIN users u ON v.user_id = u.id WHERE v.id = ?');
$stmt->execute([$id]);
$video = $stmt->fetch();

if (!$video) {
    http_response_code(404);
    die('Video not found');
}

// ভিউ কাউন্ট +1
$pdo->prepare('UPDATE videos SET views = views + 1 WHERE id = ?')->execute([$id]);

// কমেন্ট সাবমিট
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    check_csrf();
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
    $body = trim($_POST['comment']);
    if ($body) {
        $pdo->prepare('INSERT INTO comments (video_id, user_id, body) VALUES (?, ?, ?)')
            ->execute([$id, $_SESSION['user']['id'], $body]);
        header('Location: watch.php?v=' . $id);
        exit;
    }
}

// কমেন্ট লোড
$comments = $pdo->prepare('
    SELECT c.*, u.name 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.video_id = ? 
    ORDER BY c.created_at DESC
');
$comments->execute([$id]);
$comments = $comments->fetchAll();

// লাইক কাউন্ট
$likesCount = $pdo->prepare('SELECT COUNT(*) AS n FROM likes WHERE video_id = ?');
$likesCount->execute([$id]);
$likes = (int)$likesCount->fetch()['n'];

// ইউজার আগে লাইক করেছে কিনা
$liked = false;
if (is_logged_in()) {
    $chk = $pdo->prepare('SELECT 1 FROM likes WHERE video_id = ? AND user_id = ?');
    $chk->execute([$id, $_SESSION['user']['id']]);
    $liked = (bool)$chk->fetch();
}

include __DIR__ . '/partials/header.php';
?>

<div class="row">
  <div class="col-lg-8">

    <!-- ভিডিও -->
    <video controls class="w-100 mb-2 rounded" src="uploads/videos/<?= htmlspecialchars($video['filename']) ?>"></video>

    <!-- টাইটেল -->
    <h4><?= htmlspecialchars($video['title']) ?></h4>

    <!-- বিস্তারিত -->
    <div class="text-muted mb-2">
      By <?= htmlspecialchars($video['author']) ?> • 
      <?= (int)$video['views'] + 1 ?> views • 
      <?= htmlspecialchars($video['created_at']) ?>
    </div>

    <!-- Download, Edit, Delete -->
    <div class="mb-3">
      <!-- Download -->
      <a href="uploads/videos/<?= htmlspecialchars($video['filename']) ?>" 
         class="btn btn-outline-primary btn-sm" 
         download>
         ⬇️ Download
      </a>

      <!-- Edit/Delete only for owner -->
      <?php if (is_logged_in() && $_SESSION['user']['id'] == $video['user_id']): ?>
        <a href="edit.php?v=<?= $video['id'] ?>" class="btn btn-outline-warning btn-sm">✏️ Edit</a>
        <a href="delete.php?v=<?= $video['id'] ?>" 
           class="btn btn-outline-danger btn-sm"
           onclick="return confirm('আপনি কি নিশ্চিত ভিডিওটি মুছে ফেলতে চান?');">
           🗑️ Delete
        </a>
      <?php endif; ?>
    </div>

    <!-- Like -->
    <form action="like.php" method="post" class="mb-3 d-inline">
      <?php csrf_field(); ?>
      <input type="hidden" name="video_id" value="<?= $id ?>">
      <button class="btn btn-sm <?= $liked ? 'btn-success' : 'btn-outline-success' ?>">
        👍 Like (<?= $likes ?>)
      </button>
    </form>

    <!-- Description -->
    <p class="mt-3"><?= nl2br(htmlspecialchars($video['description'] ?? '')) ?></p>

    <hr>
    <h5>Comments</h5>

    <!-- Comment Box -->
    <?php if (is_logged_in()): ?>
      <form method="post" class="mb-3">
        <?php csrf_field(); ?>
        <textarea name="comment" class="form-control" rows="3" placeholder="Add a comment..." required></textarea>
        <button class="btn btn-primary mt-2">Post</button>
      </form>
    <?php else: ?>
      <div class="alert alert-info">Login করে কমেন্ট করুন।</div>
    <?php endif; ?>

    <!-- Comments List -->
    <?php foreach ($comments as $c): ?>
      <div class="mb-3 p-2 border rounded">
        <strong><?= htmlspecialchars($c['name']) ?></strong>
        <div class="small text-muted"><?= htmlspecialchars($c['created_at']) ?></div>
        <div><?= nl2br(htmlspecialchars($c['body'])) ?></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
