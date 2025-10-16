<?php
require_once __DIR__ . '/db.php';
require_login();
if ($_SERVER['REQUEST_METHOD']==='POST') {
check_csrf();
$vid = (int)($_POST['video_id'] ?? 0);
// toggle like
$stmt = $pdo->prepare('SELECT id FROM likes WHERE video_id=? AND user_id=?');
$stmt->execute([$vid, $_SESSION['user']['id']]);
if ($row = $stmt->fetch()) {
$pdo->prepare('DELETE FROM likes WHERE id=?')->execute([$row['id']]);
} else {
$pdo->prepare('INSERT INTO likes (video_id,user_id) VALUES (?,?)')->execute([$vid, $_SESSION['user']['id']]);
}
}
header('Location: watch.php?v=' . $vid);