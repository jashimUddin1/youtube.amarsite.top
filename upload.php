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
  $ok = 'Video uploaded!';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Oversize POST guard BEFORE CSRF (fixes "invalid csrf" when post_max_size is exceeded)
  $postMaxBytes = to_bytes(ini_get('post_max_size'));
  $contentLen   = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
  if ($postMaxBytes > 0 && $contentLen > $postMaxBytes) {
    $err = 'Payload too large: রিকোয়েস্ট সাইজ post_max_size ছাড়িয়েছে। '
         . 'দয়া করে ছোট ফাইল দিন বা সার্ভার কনফিগ বাড়ান (post_max_size, upload_max_filesize, client_max_body_size).';
  } else {
    try {
      check_csrf(); // now safe to verify CSRF
    } catch (Throwable $e) {
      $err = 'CSRF validation failed.';
    }
  }

  if ($err === '') {
    $title = trim($_POST['title'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $thumbFile = $_FILES['thumb'] ?? null;
    $file      = $_FILES['video'] ?? null;

    // Basic validation
    if ($title === '') {
      $err = 'Title দিন।';
    } elseif (!$file || !isset($file['error'])) {
      $err = 'Video ফাইল দিন।';
    } else {
      // File error mapping
      switch ($file['error']) {
        case UPLOAD_ERR_OK:
          break;
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
          $err = 'Video ফাইলটি সর্বোচ্চ সাইজ ছাড়িয়েছে (server/client limit)।';
          break;
        case UPLOAD_ERR_PARTIAL:
          $err = 'Video আংশিক আপলোড হয়েছে, আবার চেষ্টা করুন।';
          break;
        case UPLOAD_ERR_NO_FILE:
          $err = 'কোনো Video ফাইল পাওয়া যায়নি।';
          break;
        case UPLOAD_ERR_NO_TMP_DIR:
          $err = 'সার্ভারের অস্থায়ী ডিরেক্টরি (upload_tmp_dir) নেই।';
          break;
        case UPLOAD_ERR_CANT_WRITE:
          $err = 'ডিস্কে লিখতে ব্যর্থ।';
          break;
        case UPLOAD_ERR_EXTENSION:
          $err = 'এক্সটেনশনের কারণে আপলোড বন্ধ হয়েছে।';
          break;
        default:
          $err = 'Upload error code: ' . (int)$file['error'];
      }
    }

    // Extension & size checks
    if ($err === '') {
      $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
      if (!in_array($ext, $ALLOWED_VIDEO_EXT, true)) {
        $err = 'Allowed: mp4, webm, ogg';
      } else {
        // Max size from app config (set to 1024 for 1GB)
        $maxBytes = (int)$MAX_VIDEO_MB * 1024 * 1024;
        if (($file['size'] ?? 0) > $maxBytes) {
          $err = 'Max size ' . (int)$MAX_VIDEO_MB . 'MB';
        }
      }
    }

    // Move & DB insert
    if ($err === '') {
      // Ensure dirs exist
      if (!is_dir(VIDEO_DIR)) @mkdir(VIDEO_DIR, 0775, true);
      if (!is_dir(THUMB_DIR)) @mkdir(THUMB_DIR, 0775, true);

      // Safe random filename
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
        // Optional thumbnail
        $thumbName = null;
        if ($thumbFile && isset($thumbFile['error']) && $thumbFile['error'] !== UPLOAD_ERR_NO_FILE) {
          if ($thumbFile['error'] === UPLOAD_ERR_OK) {
            $tExt = strtolower(pathinfo($thumbFile['name'] ?? '', PATHINFO_EXTENSION));
            if (in_array($tExt, ['jpg','jpeg','png'], true)) {
              try {
                $thumbName = bin2hex(random_bytes(8)) . '.' . $tExt;
              } catch (Throwable $e) {
                $thumbName = uniqid('th_', true) . '.' . $tExt;
              }
              $thumbDest = rtrim(THUMB_DIR, '/\\') . DIRECTORY_SEPARATOR . $thumbName;

              if (!is_uploaded_file($thumbFile['tmp_name'])) {
                $err = 'Invalid thumbnail upload.';
                @unlink($videoDest);
              } elseif (!@move_uploaded_file($thumbFile['tmp_name'], $thumbDest)) {
                $err = 'Thumbnail upload failed.';
                @unlink($videoDest);
              }
            }
            // Unsupported thumb ext → silently ignore
          } else {
            $err = 'Thumbnail upload error (code ' . (int)$thumbFile['error'] . ').';
            @unlink($videoDest);
          }
        }

        // DB insert
        if ($err === '') {
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

            // Success → PRG
            header('Location: ' . basename(__FILE__) . '?ok=1');
            exit;
          } catch (Throwable $e) {
            @unlink($videoDest);
            if ($thumbName) {
              @unlink(rtrim(THUMB_DIR, '/\\') . DIRECTORY_SEPARATOR . $thumbName);
            }
            $err = 'Database error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
          }
        }
      }
    }
  }
}

include __DIR__ . '/partials/header.php';
?>
<h3>Upload Video</h3>

<?php if ($err): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($err, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if ($ok): ?>
  <div class="alert alert-success"><?= htmlspecialchars($ok, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="col-md-8" novalidate>
  <?php csrf_field(); ?>

  <!-- Client-side max hint (1GB in bytes) -->
  <input type="hidden" name="MAX_FILE_SIZE" value="1073741824">

  <div class="mb-3">
    <label class="form-label" for="title">Title</label>
    <input id="title" name="title" class="form-control" required
           value="<?= htmlspecialchars($_POST['title'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
  </div>

  <div class="mb-3">
    <label class="form-label" for="description">Description</label>
    <textarea id="description" name="description" class="form-control" rows="4"><?= htmlspecialchars($_POST['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
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
</form>

<script>
// Simple client-side size check (1GB)
(function () {
  const input = document.getElementById('video');
  if (!input) return;
  input.addEventListener('change', function (e) {
    const f = e.target.files && e.target.files[0];
    if (!f) return;
    const MAX = 1024 * 1024 * 1024; // 1GB in bytes
    if (f.size > MAX) {
      alert('ফাইলটি 1GB সীমা ছাড়িয়েছে।');
      e.target.value = '';
    }
  });
})();
</script>

<?php include __DIR__ . '/partials/footer.php'; ?>
