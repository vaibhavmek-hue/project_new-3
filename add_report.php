<?php
require_once 'common/auth_check.php';
include 'db.php';
$error = '';

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : (int)($_POST['project_id'] ?? 0);

// Load the project so we can show its name and validate the id
$project = null;
if ($project_id > 0) {
    $res = $conn->query("SELECT id, project_name FROM projects WHERE id = $project_id");
    if ($res && $res->num_rows > 0) {
        $project = $res->fetch_assoc();
    }
}

if (!$project) {
    header("Location: allprojectdashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_name = trim($_POST['report_name'] ?? '');
    $report_type = $_POST['report_type'] ?? 'Progress';
    $report_date = trim($_POST['report_date'] ?? '');

    $allowed_types = ['Weekly', 'Monthly', 'Progress', 'Final'];
    if (!in_array($report_type, $allowed_types, true)) {
        $report_type = 'Progress';
    }

    if ($report_name === '') {
        $error = "Report Name is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO project_reports (project_id, report_name, report_type, report_date) VALUES (?, ?, ?, ?)");
        $date_val = $report_date !== '' ? $report_date : null;
        $stmt->bind_param("isss", $project_id, $report_name, $report_type, $date_val);
        if ($stmt->execute()) {
            $stmt->close();
            header("Location: projectdetail.php?id=" . $project_id);
            exit;
        } else {
            $error = "Error: " . $stmt->error;
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
    <div class="layout-wrapper layout-content-navbar " >
      <div class="layout-container">
        <!-- Menu -->

         <?php include 'common/sidebar.php'; ?>

        <!-- / Menu -->
        <!-- Layout container -->

        <!-- Layout container -->
        <div class="layout-page">
          <!-- Navbar -->

        <?php include 'common/navbar.php'; ?>

          <!-- / Navbar -->

          <!-- Content wrapper -->
          <div class="content-wrapper">
            <div class="container-xxl flex-grow-1 container-p-y">

              <div class="card">
                <div class="card-header">
                  <h3 class="text-dark card-header m-0">
                    Add Report <span class="text-muted fs-6">for <?php echo htmlspecialchars($project['project_name']); ?></span>
                  </h3>
                </div>
                <div class="card-body">
                  <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                  <?php endif; ?>

                  <form method="POST" action="">
                    <input type="hidden" name="project_id" value="<?php echo (int)$project_id; ?>">

                    <div class="row">
                      <div class="col-lg-6 mb-3">
                        <div class="form-floating">
                          <input type="text" name="report_name" class="form-control" id="floatingReportName" placeholder="Report Name" required>
                          <label for="floatingReportName">Report Name</label>
                        </div>
                      </div>

                      <div class="col-lg-6 mb-3">
                        <div class="form-floating">
                          <select name="report_type" class="form-select" id="floatingReportType">
                            <option value="Progress">Progress</option>
                            <option value="Weekly">Weekly</option>
                            <option value="Monthly">Monthly</option>
                            <option value="Final">Final</option>
                          </select>
                          <label for="floatingReportType">Report Type</label>
                        </div>
                      </div>

                      <div class="col-lg-6 mb-3">
                        <div class="form-floating">
                          <input type="date" name="report_date" class="form-control" id="floatingReportDate">
                          <label for="floatingReportDate">Report Date</label>
                        </div>
                      </div>
                    </div>

                    <div class="row mt-4">
                      <div class="col-12">
                        <button type="submit" class="btn btn-primary me-2">
                          <i class="bx bx-check me-1"></i> Submit
                        </button>
                        <a href="projectdetail.php?id=<?php echo (int)$project_id; ?>" class="btn btn-secondary">
                          <i class="bx bx-arrow-back me-1"></i> Back
                        </a>
                      </div>
                    </div>
                  </form>
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
