<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Welcome</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<div class="container mt-5 text-center">
  <h1>Welcome to the User Management System</h1>
    <?php if (isset($_SESSION['user_id'])): ?>
      <p>You are logged in as <?php echo htmlspecialchars($_SESSION['user_email']); ?>.</p>
      <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
    <?php else: ?>
      <p>Please log in or register.</p>
      <a href="login.php" class="btn btn-primary">Login</a>
      <a href="register.php" class="btn btn-secondary">Register</a>
    <?php endif; ?>
</div>
</body>
</html>
