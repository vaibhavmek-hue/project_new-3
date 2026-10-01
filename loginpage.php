<?php
session_start();
include 'db.php';
$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Server-side mandatory check (the HTML `required` attribute alone can
    // be bypassed, e.g. by submitting the form via curl/JS with it removed)
    if ($email === '' || $password === '') {
        $login_error = "Email and password are both required.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND status = 'Active' LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows === 1) {
            $user = $res->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                session_regenerate_id(true); // fresh session id on every login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                header("Location: index.php");
                exit;
            } else {
                $login_error = "Invalid email or password.";
            }
        } else {
            $login_error = "Invalid email or password.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - DashBoard</title>
  
  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <!-- External Custom CSS -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 p-3 p-md-4">

  <!-- Main Container -->
  <div class="container main-card rounded-5 p-4 p-lg-5">
    <div class="row g-4 align-items-center">
      
      <!-- Left Column: Branding & Dashboard Mockup -->
      <div class="col-lg-6 d-flex flex-column justify-content-between pe-lg-4">
        <div>
          <!-- Brand Logo -->
          <div class="d-flex align-items-center gap-2 mb-4">
            <div class="brand-icon rounded-3 d-flex align-items-center justify-content-center">
              <i class="bi bi-grid-fill text-white fs-6"></i>
            </div>
            <span class="fw-bold fs-4 text-white">DashBoard</span>
          </div>

          <!-- Hero Text -->
          <h1 class="display-4 fw-bold text-white mb-2">
            Welcome <span class="text-gradient">Back!</span>
          </h1>
          <p class="text-secondary fs-6 mb-3">
            Sign in to continue to your dashboard and manage everything in one place.
          </p>
          <div class="title-underline mb-4"></div>

          <!-- Interactive Graphic / Mockup Card -->
          <div class="my-4 position-relative">
            <!-- Background Orbs / Glows -->
            <div class="orb orb-1"></div>
            <div class="orb orb-2"></div>

            <!-- Dashboard Visual Card -->
            <div class="mockup-card rounded-4 p-4 text-start">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="d-flex align-items-center gap-2">
                  <i class="bi bi-house-door-fill text-primary"></i>
                  <span class="text-secondary small fw-semibold">Dashboard</span>
                </div>
              </div>
              
              <!-- Mockup Stat Boxes -->
              <div class="row g-2 mb-3">
                <div class="col-4">
                  <div class="stat-box p-2 rounded text-center">
                    <span class="text-secondary extra-small d-block">Total Users</span>
                    <strong class="text-white">8,542</strong>
                  </div>
                </div>
                <div class="col-4">
                  <div class="stat-box p-2 rounded text-center">
                    <span class="text-secondary extra-small d-block">Total Orders</span>
                    <strong class="text-white">2,156</strong>
                  </div>
                </div>
                <div class="col-4">
                  <div class="stat-box p-2 rounded text-center">
                    <span class="text-secondary extra-small d-block">Revenue</span>
                    <strong class="text-success">$12,450</strong>
                  </div>
                </div>
              </div>

              <!-- Mockup Chart Section -->
              <div class="stat-box p-3 rounded">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <span class="text-secondary extra-small">Sales Overview</span>
                  <span class="badge bg-secondary bg-opacity-20 text-secondary extra-small">This Month</span>
                </div>
                <!-- SVG Wave Line -->
                <svg class="w-100" height="45" viewBox="0 0 300 45">
                  <path d="M0,35 Q 30,10 60,30 T 120,25 T 180,10 T 240,30 T 300,15" fill="none" stroke="#8b5cf6" stroke-width="3"/>
                </svg>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer Quote -->
        <div class="mt-3">
          <i class="bi bi-quote quote-icon d-block mb-1"></i>
          <p class="text-secondary mb-0 small">The best way to predict the future is to create it.</p>
          <small class="text-purple fw-semibold">Peter Drucker</small>
        </div>
      </div>

      <!-- Right Column: Login Card -->
      <div class="col-lg-6">
        <div class="login-card p-4 p-md-5 rounded-5">
          
          <!-- Header Lock Icon -->
          <div class="text-center mb-4">
            <div class="lock-avatar rounded-circle d-inline-flex align-items-center justify-content-center mb-3">
              <i class="bi bi-lock-fill text-white fs-3"></i>
            </div>
            <h2 class="fw-bold text-white mb-1">Login</h2>
            <p class="text-secondary small">Welcome back! Please login to continue.</p>
          </div>

          <!-- Form Fields -->
          <?php if ($login_error): ?>
            <div class="alert alert-danger py-2 small"><?php echo htmlspecialchars($login_error); ?></div>
          <?php endif; ?>
          <form method="POST" action="">
            <!-- Email -->
            <div class="mb-3">
              <label for="emailInput" class="form-label text-secondary small">Email Address</label>
              <div class="input-group">
                <span class="input-group-text custom-input-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control custom-input" id="emailInput" placeholder="Enter your email" required>
              </div>
            </div>

            <!-- Password -->
            <div class="mb-3">
              <label for="passwordInput" class="form-label text-secondary small" required>Password</label>
              <div class="input-group">
                <span class="input-group-text custom-input-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control custom-input border-end-0" id="passwordInput" placeholder="Enter your password" required>
                <span class="input-group-text custom-input-text border-start-0 cursor-pointer" id="togglePassword">
                  <i class="bi bi-eye" id="togglePasswordIcon"></i>
                </span>
              </div>
            </div>

            <!-- Remember & Forgot Password -->
            <div class="d-flex justify-content-between align-items-center mb-4">
              <div class="form-check">
                <input class="form-check-input custom-checkbox" type="checkbox" id="rememberMe" checked>
                <label class="form-check-label text-secondary small" for="rememberMe">
                  Remember me
                </label>
              </div>
              <a href="#" class="text-purple text-decoration-none small">Forgot password?</a>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-gradient w-100 py-2.5 fw-semibold mb-4">
              Sign In
</button>

            <!-- Social Divider -->
            <div class="d-flex align-items-center text-center mb-4">
              <hr class="flex-grow-1 custom-hr">
              <span class="px-3 text-secondary extra-small">or continue with</span>
              <hr class="flex-grow-1 custom-hr">
            </div>

            <!-- OAuth Buttons -->
            <div class="row g-2 mb-4">
              <div class="col-6">
                <a href="https://accounts.google.com/lifecycle/steps/signup/name?dsh=S2047408425:1786641907967517&flowEntry=SignUp&flowName=GlifWebSignIn&TL=AE6Fvybu5XBeqnKiH2TcMA8p5NEalMEPYyrRcoZjvU85cHh3wXD6fHi4Xbmpyhkp&continue=https://accounts.google.com/ManageAccount?nc%3D1" type="button" class="btn social-btn w-100 d-flex align-items-center justify-content-center gap-2 py-2">
                  <i class="bi bi-google text-danger"></i>
                  <span class="small">Google</span>
</a>
              </div>
              <div class="col-6">
                <button type="button" class="btn social-btn w-100 d-flex align-items-center justify-content-center gap-2 py-2">
                  <i class="bi bi-microsoft text-warning"></i>
                  <span class="small">Microsoft</span>
                </button>
              </div>
            </div>

            <!-- Footer Sign Up Link -->
            <div class="text-center">
              <span class="text-secondary small">Don't have an account? </span>
              <a href="setup_admin.php" class="text-purple text-decoration-none small fw-semibold">Create New Account</a>
            </div>
          </form>

        </div>
      </div>

    </div>
  </div>

  <!-- Bootstrap 5 JS Bundle -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var toggleBtn = document.getElementById('togglePassword');
      var toggleIcon = document.getElementById('togglePasswordIcon');
      var passwordInput = document.getElementById('passwordInput');

      if (toggleBtn && passwordInput) {
        toggleBtn.addEventListener('click', function () {
          var isHidden = passwordInput.type === 'password';
          passwordInput.type = isHidden ? 'text' : 'password';
          toggleIcon.classList.toggle('bi-eye', !isHidden);
          toggleIcon.classList.toggle('bi-eye-slash', isHidden);
        });
      }
    });
  </script>
</body>
</html>