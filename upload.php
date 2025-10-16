<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_login();

$err = '';
$ok  = '';

// Helper: convert php.ini size (e.g., "1024M") to bytes
function to_bytes($val) {
  $val = trim((string)$val);
  if ($val === '') return 0;
  $last = strtolower($val[strlen($val) - 1]);
  $num  = (int)$val;
  return match ($last) {
    'g' => $num * 1024 * 1024 * 1024,
    'm' => $num * 1024 * 1024,
    'k' => $num * 1024,
    default => (int)$val,
  };
}

// PRG success message
if (($_GET['ok'] ?? '') === '1') {
  $ok = '✅ Video uploaded successfully!';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $postMaxBytes = to_bytes(ini_get('post_max_size'));
  $contentLen   = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
  if ($postMaxBytes > 0 && $contentLen > $postMaxBytes) {
    $err = 'Payload too large: রিকোয়েস্ট সাইজ post_max_size ছাড়িয়েছে।';
  } else {
    try {
      check_csrf();
    } catch (Throwable $e) {
      $err = 'CSRF validation failed.';
    }
  }

  if ($err === '') {
    $title = trim($_POST['title'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $thumbFile = $_FILES['thumb'] ?? null;
    $file      = $_FILES['video'] ?? null;

    if ($title === '') {
      $err = 'Title দিন।';
    } elseif (!$file || !isset($file['error'])) {
      $err = 'Video ফাইল দিন।';
    } else {
      switch ($file['error']) {
        case UPLOAD_ERR_OK: break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
          $err = 'Video ফাইলটি সর্বোচ্চ সাইজ ছাড়িয়েছে।'; break;
        case UPLOAD_ERR_PARTIAL:
          $err = 'Video আংশিক আপলোড হয়েছে, আবার চেষ্টা করুন।'; break;
        case UPLOAD_ERR_NO_FILE:
          $err = 'কোনো Video ফাইল পাওয়া যায়নি।'; break;
        default:
          $err = 'Upload error code: ' . (int)$file['error'];
      }
    }

    if ($err === '') {
      $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
      if (!in_array($ext, $ALLOWED_VIDEO_EXT, true)) {
        $err = 'Allowed: mp4, webm, ogg';
      } else {
        $maxBytes = (int)$MAX_VIDEO_MB * 1024 * 1024;
        if (($file['size'] ?? 0) > $maxBytes) {
          $err = 'Max size ' . (int)$MAX_VIDEO_MB . 'MB';
        }
      }
    }

    if ($err === '') {
      if (!is_dir(VIDEO_DIR)) @mkdir(VIDEO_DIR, 0775, true);
      if (!is_dir(THUMB_DIR)) @mkdir(THUMB_DIR, 0775, true);

      try {
        $safeVideo = bin2hex(random_bytes(8)) . '.' . $ext;
      } catch (Throwable $e) {
        $safeVideo = uniqid('vid_', true) . '.' . $ext;
      }
      $videoDest = rtrim(VIDEO_DIR, '/\\') . DIRECTORY_SEPARATOR . $safeVideo;

      if (!is_uploaded_file($file['tmp_name'])) {
        $err = 'Invalid upload source.';
      } elseif (!@move_uploaded_file($file['tmp_name'], $videoDest)) {
        $err = 'Video upload failed.';
      } else {
        $thumbName = null;
        if ($thumbFile && $thumbFile['error'] === UPLOAD_ERR_OK) {
          $tExt = strtolower(pathinfo($thumbFile['name'] ?? '', PATHINFO_EXTENSION));
          if (in_array($tExt, ['jpg', 'jpeg', 'png'], true)) {
            $thumbName = bin2hex(random_bytes(8)) . '.' . $tExt;
            $thumbDest = rtrim(THUMB_DIR, '/\\') . DIRECTORY_SEPARATOR . $thumbName;
            move_uploaded_file($thumbFile['tmp_name'], $thumbDest);
          }
        }

        try {
          $stmt = $pdo->prepare(
            'INSERT INTO videos (user_id, title, description, filename, thumb)
             VALUES (?, ?, ?, ?, ?)'
          );
          $stmt->execute([
            $_SESSION['user']['id'] ?? null,
            $title,
            $desc,
            $safeVideo,
            $thumbName
          ]);

          header('Location: ' . basename(__FILE__) . '?ok=1');
          exit;
        } catch (Throwable $e) {
          @unlink($videoDest);
          if ($thumbName) @unlink(THUMB_DIR . '/' . $thumbName);
          $err = 'Database error: ' . htmlspecialchars($e->getMessage());
        }
      }
    }
  }
}

include __DIR__ . '/partials/header.php';
?>
<h3>Upload Video</h3>

<?php if ($err): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
<?php endif; ?>

<?php if ($ok): ?>
  <div class="alert alert-success"><?= htmlspecialchars($ok) ?></div>
<?php endif; ?>

<form id="uploadForm" method="post" enctype="multipart/form-data" class="col-md-8" novalidate>
  <?php csrf_field(); ?>
  <input type="hidden" name="MAX_FILE_SIZE" value="1073741824">

  <div class="mb-3">
    <label class="form-label" for="title">Title</label>
    <input id="title" name="title" class="form-control" required
           value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
  </div>

  <div class="mb-3">
    <label class="form-label" for="description">Description</label>
    <textarea id="description" name="description" class="form-control" rows="4"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
  </div>

  <div class="mb-3">
    <label class="form-label" for="video">Video (mp4/webm/ogg)</label>
    <input id="video" type="file" name="video"
           accept="video/mp4,video/webm,video/ogg" class="form-control" required>
    <div class="form-text">
      Max: <?= (int)$MAX_VIDEO_MB ?>MB &middot;
      Server: upload_max_filesize=<?= ini_get('upload_max_filesize') ?>, post_max_size=<?= ini_get('post_max_size') ?>
    </div>
  </div>

  <div class="mb-3">
    <label class="form-label" for="thumb">Thumbnail (jpg/png, optional)</label>
    <input id="thumb" type="file" name="thumb" accept="image/*" class="form-control">
  </div>

  <button class="btn btn-primary">Upload</button>

  <!-- Progress Bar -->
  <div class="progress mt-3" style="height: 22px; display:none;" id="progressContainer">
    <div class="progress-bar progress-bar-striped progress-bar-animated"
         id="progressBar" role="progressbar" style="width:0%">0%</div>
  </div>
</form>

<script>
// Show upload progress
document.getElementById('uploadForm').addEventListener('submit', function(e) {
  e.preventDefault();

  const form = e.target;
  const data = new FormData(form);
  const xhr = new XMLHttpRequest();

  const progressContainer = document.getElementById('progressContainer');
  const progressBar = document.getElementById('progressBar');
  progressContainer.style.display = 'block';
  progressBar.style.width = '0%';
  progressBar.textContent = '0%';

  xhr.upload.addEventListener('progress', function(e) {
    if (e.lengthComputable) {
      const percent = Math.round((e.loaded / e.total) * 100);
      progressBar.style.width = percent + '%';
      progressBar.textContent = percent + '%';
    }
  });

  xhr.onreadystatechange = function() {
    if (xhr.readyState === 4) {
      if (xhr.status === 200) {
        progressBar.classList.remove('progress-bar-animated');
        progressBar.classList.add('bg-success');
        progressBar.textContent = '✅ Upload Complete!';
        setTimeout(() => window.location.href = 'upload.php?ok=1', 1200);
      } else {
        progressBar.classList.add('bg-danger');
        progressBar.textContent = '❌ Upload Failed!';
      }
    }
  };

  xhr.open('POST', form.action);
  xhr.send(data);
});
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
