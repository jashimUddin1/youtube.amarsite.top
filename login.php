<?php
require_once __DIR__ . '/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
check_csrf();
$email = trim($_POST['email'] ?? '');
$pass = $_POST['password'] ?? '';
$stmt = $pdo->prepare('SELECT * FROM users WHERE email=? LIMIT 1');
$stmt->execute([$email]);
$u = $stmt->fetch();
if ($u && password_verify($pass, $u['password_hash'])) {
$_SESSION['user'] = ['id'=>$u['id'],'name'=>$u['name'],'email'=>$u['email']];
header('Location: index.php'); exit;
} else { $err = 'Invalid credentials'; }
}
include __DIR__ . '/partials/header.php';
?>
<h3>Login</h3>
<?php if (!empty($_GET['registered'])): ?><div class="alert alert-success">Registration successful. Please login.</div><?php endif; ?>
<?php if (!empty($err)): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>
<form method="post" class="col-md-6">
<?php csrf_field(); ?>
<div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
<button class="btn btn-primary">Login</button>
</form>
<?php include __DIR__ . '/partials/footer.php'; ?>