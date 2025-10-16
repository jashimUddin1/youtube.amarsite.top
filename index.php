<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// ভিডিও লিস্ট আনো
$stmt = $pdo->query('
  SELECT v.*, u.name AS author 
  FROM videos v 
  JOIN users u ON v.user_id = u.id 
  ORDER BY v.created_at DESC 
  LIMIT 24
');
$videos = $stmt->fetchAll();

include __DIR__ . '/partials/header.php';
?>

<h3 class="mb-3">Latest Videos</h3>

<?php if (!empty($_GET['deleted'])): ?>
  <div class="alert alert-success">✅ ভিডিও সফলভাবে মুছে ফেলা হয়েছে।</div>
<?php endif; ?>

<div class="row g-3">
<?php foreach ($videos as $v): 
  $filePath = 'uploads/videos/' . $v['filename'];
  $fileSize = file_exists($filePath) ? round(filesize($filePath) / (1024 * 1024), 2) : 0; // MB তে সাইজ
?>
  <div class="col-6 col-md-4 col-lg-3">
    <div class="card h-100 shadow-sm position-relative">

      <!-- 3-dot menu -->
      <div class="dropdown position-absolute top-0 end-0 m-2">
        <button class="btn btn-sm btn-light border-0" 
                type="button" 
                id="dropdownMenu<?= $v['id'] ?>" 
                data-bs-toggle="dropdown" 
                aria-expanded="false"
                title="More options">
          ⋮
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenu<?= $v['id'] ?>">
          <!-- Details -->
          <li>
            <button class="dropdown-item" 
                    data-bs-toggle="modal" 
                    data-bs-target="#detailsModal<?= $v['id'] ?>">
              📺 Details
            </button>
          </li>

          <!-- Edit/Delete -->
          <?php if (is_logged_in() && $_SESSION['user']['id'] == $v['user_id']): ?>
            <li><a class="dropdown-item text-warning" href="edit.php?v=<?= $v['id'] ?>">✏️ Edit</a></li>
            <li>
              <a class="dropdown-item text-danger" 
                 href="delete.php?v=<?= $v['id'] ?>" 
                 onclick="return confirm('আপনি কি নিশ্চিত ভিডিওটি মুছে ফেলতে চান?');">
                 🗑️ Delete
              </a>
            </li>
          <?php endif; ?>
        </ul>
      </div>

      <!-- থাম্ব -->
      <a href="watch.php?v=<?= $v['id'] ?>" class="text-decoration-none">
        <img class="card-img-top" 
             src="<?= $v['thumb'] ? 'uploads/thumbs/' . htmlspecialchars($v['thumb']) : 'assets/img/placeholder.jpg' ?>" 
             alt="thumb">
      </a>

      <!-- ইনফো -->
      <div class="card-body">
        <h6 class="card-title text-dark text-truncate mb-2" 
            title="<?= htmlspecialchars($v['title']) ?>">
            <?= htmlspecialchars($v['title']) ?>
        </h6>
        <div class="small text-muted mb-2">
          By <?= htmlspecialchars($v['author']) ?> • <?= (int)$v['views'] ?> views
        </div>

        <!-- Download -->
        <a href="uploads/videos/<?= htmlspecialchars($v['filename']) ?>" 
           class="btn btn-outline-primary btn-sm w-100" 
           download>
           ⬇️ Download
        </a>
      </div>
    </div>
  </div>

  <!-- Details Modal -->
  <div class="modal fade" id="detailsModal<?= $v['id'] ?>" tabindex="-1" aria-labelledby="detailsLabel<?= $v['id'] ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="detailsLabel<?= $v['id'] ?>">📺 <?= htmlspecialchars($v['title']) ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">

          <p><strong>Uploader:</strong> <?= htmlspecialchars($v['author']) ?></p>
          <p><strong>Upload Date:</strong> <?= htmlspecialchars($v['created_at']) ?></p>
          <p><strong>File Name:</strong> <?= htmlspecialchars($v['filename']) ?></p>
          <p><strong>File Size:</strong> <?= $fileSize ?> MB</p>

          <p><strong>Video Duration:</strong> 
            <span id="duration<?= $v['id'] ?>">Loading...</span>
          </p>

          <video id="video<?= $v['id'] ?>" src="<?= $filePath ?>" preload="metadata" class="d-none"></video>

        </div>
        <div class="modal-footer">
          <a href="watch.php?v=<?= $v['id'] ?>" class="btn btn-secondary">Open Full Page</a>
          <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>

  <script>
  // Duration calculate dynamically
  document.addEventListener('DOMContentLoaded', function() {
    const videoEl = document.getElementById('video<?= $v['id'] ?>');
    const durEl = document.getElementById('duration<?= $v['id'] ?>');
    if (videoEl) {
      videoEl.addEventListener('loadedmetadata', function() {
        const sec = Math.floor(videoEl.duration);
        const min = Math.floor(sec / 60);
        const s = sec % 60;
        durEl.textContent = min + 'm ' + s + 's';
      });
    }
  });
  </script>

<?php endforeach; ?>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>
