<?php
require_once __DIR__ . '/db.php';
$q = trim($_GET['q'] ?? '');
$videos = [];
if ($q !== '') {
$stmt = $pdo->prepare("SELECT v.*, u.name AS author FROM videos v JOIN users u ON v.user_id=u.id WHERE v.title LIKE ? ORDER BY v.created_at DESC");
$stmt->execute(['%' . $q . '%']);
$videos = $stmt->fetchAll();
}
include __DIR__ . '/partials/header.php';
?>
<h3>Search: <?= htmlspecialchars($q) ?></h3>
<div class="row g-3">
<?php foreach ($videos as $v): ?>
<div class="col-6 col-md-4 col-lg-3">
<div class="card h-100">
<a href="watch.php?v=<?= $v['id'] ?>" class="text-decoration-none">
<img class="card-img-top" src="<?= $v['thumb'] ? 'uploads/thumbs/' . htmlspecialchars($v['thumb']) : 'assets/img/placeholder.jpg' ?>" alt="thumb">
<div class="card-body">
<h6 class="card-title text-dark text-truncate" title="<?= htmlspecialchars($v['title']) ?>"><?= htmlspecialchars($v['title']) ?></h6>
<div class="small text-muted">By <?= htmlspecialchars($v['author']) ?> • <?= (int)$v['views'] ?> views</div>
</div>
</a>
</div>
</div>
<?php endforeach; ?>
<?php if ($q && !$videos): ?>
<div class="col-12"><div class="alert alert-warning">No results.</div></div>
<?php endif; ?>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>