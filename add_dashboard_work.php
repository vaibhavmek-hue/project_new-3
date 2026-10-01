<?php
require_once 'common/auth_check.php';
include 'db.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0; // unused for add, kept for consistency
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $page_name    = $conn->real_escape_string(trim($_POST['page_name'] ?? ''));
    $status       = $conn->real_escape_string(trim($_POST['status'] ?? 'Pending'));
    $created_date = $conn->real_escape_string(trim($_POST['created_date'] ?? ''));
    $end_date     = $conn->real_escape_string(trim($_POST['end_date'] ?? ''));
    $project_id   = !empty($_POST['project_id']) ? (int) $_POST['project_id'] : null;

    if ($page_name === '') {
        $error = "Page Name is required.";
    } else {
        $created_date_val = $created_date !== '' ? "'$created_date'" : "CURDATE()";
        $end_date_val     = $end_date !== '' ? "'$end_date'" : "NULL";
        $project_id_val   = $project_id ? $project_id : "NULL";
        $sql = "INSERT INTO dashboard_work (page_name, status, created_date, end_date, project_id)
                VALUES ('$page_name', '$status', $created_date_val, $end_date_val, $project_id_val)";
        if ($conn->query($sql) === TRUE) {
            header("Location: dashboardwork.php");
            exit;
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

// Projects for the dropdown, and an optional pre-selected project (e.g. when
// arriving from a project's detail page via ?project_id=)
$projects_list = $conn->query("SELECT id, project_name FROM projects ORDER BY project_name ASC");
$preselected_project_id = isset($_GET['project_id']) ? (int) $_GET['project_id'] : 0;
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

    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">

            <!-- Add Dashboard Work Form -->
            <div class="card">
                <div class="card-header">
                    <h3 class="text-dark card-header">Add Dashboard Work</h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <div class="row">
                            <!-- Field 1 -->
                            <div class="col-lg-6 mb-3">
                                <div class="form-floating">
                                    <input type="text" name="page_name" class="form-control" id="floatingPageName" placeholder="Page Name" required>
                                    <label for="floatingPageName">Page Name</label>
                                </div>
                            </div>

                            <!-- Field 2 -->
                            <div class="col-lg-6 mb-3">
                                <div class="form-floating">
                                    <select name="status" class="form-control" id="floatingStatus">
                                        <option value="Pending">Pending</option>
                                        <option value="Designed">Designed</option>
                                        <option value="Developed">Developed</option>
                                    </select>
                                    <label for="floatingStatus">Status</label>
                                </div>
                            </div>

                            <!-- Field 3 -->
                            <div class="col-lg-6 mb-3">
                                <div class="form-floating">
                                    <input type="date" name="created_date" class="form-control" id="floatingDate" placeholder="Created Date">
                                    <label for="floatingDate">Created Date</label>
                                </div>
                            </div>

                            <!-- Field 3b -->
                            <div class="col-lg-6 mb-3">
                                <div class="form-floating">
                                    <input type="date" name="end_date" class="form-control" id="floatingEndDate" placeholder="End Date">
                                    <label for="floatingEndDate">End Date</label>
                                </div>
                            </div>

                            <!-- Field 4 -->
                            <div class="col-lg-6 mb-3">
                                <div class="form-floating">
                                    <select name="project_id" class="form-select" id="floatingProjectId">
                                        <option value="">— Not linked to a project —</option>
                                        <?php if ($projects_list): while ($p = $projects_list->fetch_assoc()): ?>
                                            <option value="<?php echo (int) $p['id']; ?>" <?php echo ($preselected_project_id === (int) $p['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($p['project_name']); ?>
                                            </option>
                                        <?php endwhile; endif; ?>
                                    </select>
                                    <label for="floatingProjectId">Project</label>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bx bx-check me-1"></i> Submit
                                </button>
                                <a href="dashboardwork.php" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!--/ Add Dashboard Work Form -->
        </div>
    </div>

          <!-- Content wrapper -->

            <?php include 'common/footer.php'; ?>
        </div>
        <!-- / Layout page -->
      </div>
    </div>
    <!-- / Layout wrapper -->
  </body>
</html>
