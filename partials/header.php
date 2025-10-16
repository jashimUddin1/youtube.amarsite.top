<?php require_once __DIR__ . '/../config.php'; ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>YouTube Clone</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="/assets/css/custom.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
<div class="container">
<a class="navbar-brand" href="index.php">YT‑Clone</a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Toggle navigation">
<span class="navbar-toggler-icon"></span>
</button>
<div class="collapse navbar-collapse" id="nav">
<form class="d-flex ms-auto" role="search" action="search.php">
<input class="form-control me-2" type="search" name="q" placeholder="Search" aria-label="Search">
<button class="btn btn-outline-light" type="submit">Search</button>
</form>
<ul class="navbar-nav ms-3">
<?php if (is_logged_in()): ?>
<li class="nav-item"><a class="nav-link" href="upload.php">Upload</a></li>
<li class="nav-item"><span class="nav-link">👋 <?php echo htmlspecialchars($_SESSION['user']['name']); ?></span></li>
<li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
<?php else: ?>
<li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
<li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
<?php endif; ?>
</ul>
</div>
</div>
</nav>
<div class="container py-4">