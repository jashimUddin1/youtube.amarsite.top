<?php
require_once __DIR__ . '/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
check_csrf();
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$pass = $_POST['password'] ?? '';
if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && strlen($pass) >= 6) {
$hash = password_hash($pass, PASSWORD_BCRYPT);
$stmt = $pdo->prepare('INSERT INTO users (name,email,password_hash) VALUES (?,?,?)');
try {
$stmt->execute([$name,$email,$hash]);
header('Location: login.php?registered=1'); exit;
} catch (PDOException $e) {
$err = 'Email already in use.';
}
} else { $err = 'Provide valid name, email, password (6+).'; }
}
include __DIR__ . '/partials/header.php';
?>
<h3>Register</h3>
<?php if (!empty($err)): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>
<form method="post" class="col-md-6">
<?php csrf_field(); ?>
<div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
<div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
<button class="btn btn-primary">Create Account</button>
</form>
<?php include __DIR__ . '/partials/footer.php'; ?>