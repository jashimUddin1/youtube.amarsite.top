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

// ফাইল মুছুন
@unlink(VIDEO_DIR . $video['filename']);
if (!empty($video['thumb'])) {
  @unlink(THUMB_DIR . $video['thumb']);
}

// DB থেকে রিমুভ
$pdo->prepare('DELETE FROM videos WHERE id=?')->execute([$id]);

header('Location: index.php?deleted=1');
exit;
