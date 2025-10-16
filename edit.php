<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_login();

$id = (int)($_GET['v'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM videos WHERE id=?');
$stmt->execute([$id]);
$video = $stmt->fetch();

if (!$video) { die('Video not found'); }
if ($video['user_id'] != $_SESSION['user']['id']) { die('Unauthorized'); }

$err = $ok = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();
  $title = trim($_POST['title'] ?? '');
  $desc  = trim($_POST['description'] ?? '');

  if ($title === '') {
    $err = 'Title দিন।';
  } else {
    $stmt = $pdo->prepare('UPDATE videos SET title=?, description=? WHERE id=?');
    $stmt->execute([$title, $desc, $id]);
    $ok = 'Video updated!';
    // Refresh data
    $stmt = $pdo->prepare('SELECT * FROM videos WHERE id=?');
    $stmt->execute([$id]);
    $video = $stmt->fetch();
  }
}

include __DIR__ . '/partials/header.php';
?>
<h3>Edit Video</h3>

<?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>
<?php if ($ok): ?><div class="alert alert-success"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

<form method="post" class="col-md-8">
  <?php csrf_field(); ?>
  <div class="mb-3">
    <label class="form-label">Title</label>
    <input name="title" class="form-control" required value="<?= htmlspecialchars($video['title']) ?>">
  </div>
  <div class="mb-3">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($video['description'] ?? '') ?></textarea>
  </div>
  <button class="btn btn-primary">Save Changes</button>
</form>

<?php include __DIR__ . '/partials/footer.php'; ?>
