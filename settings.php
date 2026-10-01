<?php
session_start();
include 'db.php';

// Require login
if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];
$success = '';
$errors  = [];

// Load current user
$stmt = $conn->prepare("SELECT id, name, email, status FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header("Location: loginpage.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ---------- Profile (name + email) ----------
    if (isset($_POST['update_profile'])) {
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($name === '') {
            $errors[] = "Name is required.";
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "A valid email is required.";
        }

        // Make sure the email isn't already used by another account
        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $errors[] = "That email is already in use by another account.";
            }
            $stmt->close();
        }

        if (empty($errors)) {
            $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
            $stmt->bind_param("ssi", $name, $email, $user_id);
            $stmt->execute();
            $stmt->close();

            $_SESSION['user_name'] = $name;
            $user['name']  = $name;
            $user['email'] = $email;
            $success = "Profile updated successfully.";
        }
    }

    // ---------- Password ----------
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password      = $_POST['new_password'] ?? '';
        $confirm_password  = $_POST['confirm_password'] ?? '';

        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || !password_verify($current_password, $row['password'])) {
            $errors[] = "Current password is incorrect.";
        } elseif (strlen($new_password) < 8) {
            $errors[] = "New password must be at least 8 characters.";
        } elseif ($new_password !== $confirm_password) {
            $errors[] = "New password and confirmation do not match.";
        }

        if (empty($errors)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed, $user_id);
            $stmt->execute();
            $stmt->close();
            $success = "Password changed successfully.";
        }
    }
}
?>
<!DOCTYPE html>

<!-- =========================================================
* Sneat - Bootstrap 5 HTML Admin Template - Pro | v1.0.0
==============================================================
* Product Page: https://themeselection.com/products/sneat-bootstrap-html-admin-template/
* Created by: ThemeSelection
=========================================================
 -->
<!-- beautify ignore:start -->
<html
  lang="en"
  class="light-style layout-menu-fixed"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="./assets/"
  data-template="vertical-menu-template-free"
>
  <?php include 'common/header.php'; ?>

  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <!-- Menu -->

         <?php include 'common/sidebar.php'; ?>

        <!-- / Menu -->

        <!-- Layout container -->
        <div class="layout-page">
          <!-- Navbar -->

        <?php include 'common/navbar.php'; ?>

          <!-- / Navbar -->

          <!-- Content wrapper -->
          <div class="content-wrapper">
            <div class="container-xxl flex-grow-1 container-p-y">

              <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold m-0 text-dark">Account Settings</h2>
              </div>

              <?php if ($success): ?>
                <div class="alert alert-success py-2"><?php echo htmlspecialchars($success); ?></div>
              <?php endif; ?>
              <?php if (!empty($errors)): ?>
                <div class="alert alert-danger py-2">
                  <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $err): ?>
                      <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>

              <div class="row">
                <!-- Profile Info -->
                <div class="col-12 col-lg-6 mb-4">
                  <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-3 p-md-4">
                      <h5 class="fw-semibold mb-1">Profile Information</h5>
                      <p class="text-muted small mb-4">Update your name and email address.</p>

                      <form method="POST" action="">
                        <div class="mb-3">
                          <label for="name" class="form-label fw-semibold">Full Name</label>
                          <input type="text" class="form-control" id="name" name="name"
                                 value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>

                        <div class="mb-3">
                          <label for="email" class="form-label fw-semibold">Email</label>
                          <input type="email" class="form-control" id="email" name="email"
                                 value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>

                        <div class="mb-4">
                          <label class="form-label fw-semibold">Account Status</label>
                          <div>
                            <span class="badge <?php echo $user['status'] === 'Active' ? 'bg-success' : 'bg-secondary'; ?>">
                              <?php echo htmlspecialchars($user['status']); ?>
                            </span>
                          </div>
                        </div>

                        <div class="d-flex justify-content-end">
                          <button type="submit" name="update_profile" class="btn btn-primary px-4 py-2">
                            <i class="bi bi-check-lg me-1"></i> Save Changes
                          </button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

                <!-- Change Password -->
                <div class="col-12 col-lg-6 mb-4">
                  <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-3 p-md-4">
                      <h5 class="fw-semibold mb-1">Change Password</h5>
                      <p class="text-muted small mb-4">Choose a strong password you don't use elsewhere.</p>

                      <form method="POST" action="">
                        <div class="mb-3">
                          <label for="current_password" class="form-label fw-semibold">Current Password</label>
                          <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>

                        <div class="mb-3">
                          <label for="new_password" class="form-label fw-semibold">New Password</label>
                          <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" required>
                          <div class="form-text">At least 8 characters.</div>
                        </div>

                        <div class="mb-4">
                          <label for="confirm_password" class="form-label fw-semibold">Confirm New Password</label>
                          <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
                        </div>

                        <div class="d-flex justify-content-end">
                          <button type="submit" name="update_password" class="btn btn-primary px-4 py-2">
                            <i class="bi bi-shield-lock me-1"></i> Update Password
                          </button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
          <!-- / Content wrapper -->

            <?php include 'common/footer.php'; ?>
        </div>
        <!-- / Layout page -->
      </div>
    </div>
    <!-- / Layout wrapper -->
  </body>
</html>
