<?php
// One-time helper: creates (or resets) ONE shared admin login for everyone
// who uses this project — email: admin@123 / password: Admin@123
//
// Visit this file once in your browser after importing database.sql.
// It's safe to run more than once (it just resets the password each time).
// Delete this file afterwards if you don't want it reachable anymore.

include 'db.php';

$name     = 'Admin';
$email    = 'admin@123';
$password = 'Admin@123';
$hash     = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO users (name, email, password, status)
     VALUES (?, ?, ?, 'Active')
     ON DUPLICATE KEY UPDATE password = VALUES(password), status = 'Active'"
);
$stmt->bind_param('sss', $name, $email, $hash);

if ($stmt->execute()) {
    $message = "Admin account is ready. Login with email: $email and password: $password";
    $done = true;
} else {
    $message = "Error: " . $conn->error;
    $done = false;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Set Shared Admin Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 bg-light">
  <div class="card p-4 shadow-sm" style="max-width:460px;width:100%;">
    <h4 class="mb-3">Shared Admin Login</h4>
    <div class="alert <?php echo $done ? 'alert-success' : 'alert-danger'; ?>">
      <?php echo htmlspecialchars($message); ?>
    </div>
    <?php if ($done): ?>
      <p class="text-muted small mb-3">Give these credentials to anyone who needs access — they'll all log in with the same account:</p>
      <ul class="small text-muted mb-3">
        <li>Email: <strong>admin@123</strong></li>
        <li>Password: <strong>Admin@123</strong></li>
      </ul>
      <a href="loginpage.php" class="btn btn-primary w-100">Go to Login</a>
    <?php endif; ?>
  </div>
</body>
</html>
