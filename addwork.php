<?php require_once 'common/auth_check.php'; include 'db.php'; ?>
<?php
// Handle Add Screen form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_screen'])) {
    $screen_name  = trim($_POST['screen_name']);
    $status       = trim($_POST['status']);
    $created_date = trim($_POST['created_date']);

    $stmt = $conn->prepare("INSERT INTO app_work (screen_name, status, created_date) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $screen_name, $status, $created_date);
    $stmt->execute();
    $stmt->close();

    // Redirect back to the App Work listing page after saving
    header("Location: appwork.php");
    exit;
}
?>
<!DOCTYPE html>

<!-- =========================================================
* Sneat - Bootstrap 5 HTML Admin Template - Pro | v1.0.0
==============================================================

* Product Page: https://themeselection.com/products/sneat-bootstrap-html-admin-template/
* Created by: ThemeSelection
* License: You must have a valid license purchased in order to legally use the theme for your project.
* Copyright ThemeSelection (https://themeselection.com)

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
          <main>
            <div>
              <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 m-4">
                <h1 class="h3 fw-bold text-dark m-0">Add Screen</h1>
                <a href="appwork.php" class="btn btn-outline-secondary px-3 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2">
                  <i class="fa-solid fa-arrow-left"></i> Back to App Work
                </a>
              </div>

              <!-- Full Screen Add Form -->
              <div class="card border rounded-3 overflow-hidden m-4">
                <div class="card-body p-4 p-sm-5">
                  <form method="POST" action="">
                    <div class="row g-4">
                      <div class="col-12 col-md-6">
                        <label for="screen_name" class="form-label fw-semibold">Screen Name</label>
                        <input type="text" class="form-control" id="screen_name" name="screen_name" placeholder="Enter screen name" required>
                      </div>

                      <div class="col-12 col-md-6">
                        <label for="status" class="form-label fw-semibold">Status</label>
                        <select class="form-select" id="status" name="status" required>
                          <option value="" disabled selected>Select status</option>
                          <option value="Pending">Pending</option>
                          <option value="Designed">Designed</option>
                          <option value="Developed">Developed</option>
                        </select>
                      </div>

                      <div class="col-12 col-md-6">
                        <label for="created_date" class="form-label fw-semibold">Created Date</label>
                        <input type="date" class="form-control" id="created_date" name="created_date" required>
                      </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-5">
                      <a href="appwork.php" class="btn btn-outline-secondary px-4 py-2">Cancel</a>
                      <button type="submit" name="add_screen" class="btn btn-primary px-4 py-2">
                        <i class="fa-solid fa-check me-1"></i> Save Screen
                      </button>
                    </div>
                  </form>
                </div>
              </div>

            </div>
          </main>
          <!-- Content wrapper -->

          <?php include 'common/footer.php'; ?>
        </div>
      </div>
    </div>
  </body>
</html>
