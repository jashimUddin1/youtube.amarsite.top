<?php
// Basic app config
$BASE_URL = rtrim((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']), '/\\');


// DB config for local
// define('DB_HOST', '127.0.0.1');
// define('DB_NAME', 'youtube_clone');
// define('DB_USER', 'root');
// define('DB_PASS', ''); 


// DB config for server
define('DB_HOST', 'localhost');
define('DB_NAME', 'amarsite_youtube_clone');
define('DB_USER', 'amarsite');
define('DB_PASS', '7[Vq3B7NeJp4g;a');



// Upload config
define('VIDEO_DIR', __DIR__ . '/uploads/videos/');
define('THUMB_DIR', __DIR__ . '/uploads/thumbs/');
$ALLOWED_VIDEO_EXT = ['mp4','webm','ogg'];
$MAX_VIDEO_MB = 200; // প্রয়োজনে কম/বেশি


// Session
session_start();


// CSRF helper
if (empty($_SESSION['csrf'])) {
$_SESSION['csrf'] = bin2hex(random_bytes(16));
}
function csrf_field() { echo '<input type="hidden" name="csrf" value="' . htmlspecialchars($_SESSION['csrf']) . '">'; }
function check_csrf() { if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) { http_response_code(403); exit('Invalid CSRF'); } }


function is_logged_in() { return !empty($_SESSION['user']); }
function require_login() { if (!is_logged_in()) { header('Location: login.php'); exit; }}
?>