<?php
require_once 'common/auth_check.php';
include 'db.php';

$error = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_POST['id'] ?? 0);

// ===============================================================
// LOAD REPORT + PROJECT + CLIENT
// (mirrors the lookups in download_report.php so the defaults we
// show here match exactly what the PDF currently prints)
// ===============================================================

$report = null;
if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM project_reports WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $report = $res->fetch_assoc();
    }
    $stmt->close();
}

if (!$report) {
    header("Location: allprojectdashboard.php");
    exit;
}

$project_id = (int)$report['project_id'];

$project = null;
$stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
    $project = $res->fetch_assoc();
}
$stmt->close();

if (!$project) {
    header("Location: allprojectdashboard.php");
    exit;
}

$client = null;
if (!empty($project['client_id'])) {
    $stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
    $stmt->bind_param("i", $project['client_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $client = $res->fetch_assoc();
    }
    $stmt->close();
} elseif (!empty($project['client_name'])) {
    $stmt = $conn->prepare("SELECT * FROM clients WHERE client_name = ?");
    $stmt->bind_param("s", $project['client_name']);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $client = $res->fetch_assoc();
    }
    $stmt->close();
}

$clientOrg = $client['organization_name'] ?? ($project['client_name'] ?? 'your organization');

// Work out this report's period the same way download_report.php does,
// so the default covering-note text matches what actually gets printed.
$allReports = [];
$stmt = $conn->prepare("SELECT * FROM project_reports WHERE project_id = ? ORDER BY report_date ASC, created_at ASC");
$stmt->bind_param("i", $project_id);
$stmt->execute();
$res = $stmt->get_result();
while ($res && ($row = $res->fetch_assoc())) {
    $allReports[] = $row;
}
$stmt->close();

$periodEnd = $report['report_date'] ?? date('Y-m-d');
$periodStart = $project['start_date'] ?? null;
foreach ($allReports as $row) {
    if ((int)$row['id'] === (int)$report['id']) {
        break;
    }
    $periodStart = $row['report_date'] ?: $periodStart;
}

function fmt_month_year_local($date): string
{
    $timestamp = empty($date) ? time() : strtotime($date);
    if ($timestamp === false) {
        $timestamp = time();
    }
    return date('F Y', $timestamp);
}

$defaultSubject = ($project['project_name'] ?? 'Project') . ' - Project Work Report';

$defaultIntro = 'This report covers the work carried out on the ' .
    ($project['project_name'] ?? 'project') . ' for ' . $clientOrg .
    ' from ' . fmt_month_year_local($periodStart) . ' to ' .
    fmt_month_year_local($periodEnd) . '. We appreciate your continued ' .
    'trust and partnership, and are glad to share a summary of ' .
    'the work completed in this period below.';

$defaultClosing = 'Once again, we extend our heartfelt gratitude for your trust and ' .
    'continued collaboration. We look forward to your prompt attention ' .
    "to this matter and to serving you in the future.\n\n" .
    'Thank you for choosing ' . (defined('COMPANY_NAME') ? COMPANY_NAME : 'us') . '.';

$subjectVal = ($report['custom_subject'] ?? '') !== '' ? $report['custom_subject'] : $defaultSubject;
$introVal = ($report['custom_intro'] ?? '') !== '' ? $report['custom_intro'] : $defaultIntro;
$closingVal = ($report['custom_closing'] ?? '') !== '' ? $report['custom_closing'] : $defaultClosing;

// ===============================================================
// HANDLE SAVE
// ===============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_name = trim($_POST['report_name'] ?? '');
    $report_type = $_POST['report_type'] ?? 'Progress';
    $report_date = trim($_POST['report_date'] ?? '');
    $subjectVal = trim($_POST['custom_subject'] ?? '');
    $introVal = trim($_POST['custom_intro'] ?? '');
    $closingVal = trim($_POST['custom_closing'] ?? '');

    $allowed_types = ['Weekly', 'Monthly', 'Progress', 'Final'];
    if (!in_array($report_type, $allowed_types, true)) {
        $report_type = 'Progress';
    }

    if ($report_name === '') {
        $error = "Report Name is required.";
    } else {
        $date_val = $report_date !== '' ? $report_date : null;

        // Store null (falls back to the auto-generated wording) if the
        // text still exactly matches the default the user was shown.
        $subjectToSave = ($subjectVal === $defaultSubject || $subjectVal === '') ? null : $subjectVal;
        $introToSave = ($introVal === $defaultIntro || $introVal === '') ? null : $introVal;
        $closingToSave = ($closingVal === $defaultClosing || $closingVal === '') ? null : $closingVal;

        $stmt = $conn->prepare(
            "UPDATE project_reports
             SET report_name = ?, report_type = ?, report_date = ?,
                 custom_subject = ?, custom_intro = ?, custom_closing = ?
             WHERE id = ?"
        );
        $stmt->bind_param(
            "ssssssi",
            $report_name,
            $report_type,
            $date_val,
            $subjectToSave,
            $introToSave,
            $closingToSave,
            $id
        );

        if ($stmt->execute()) {
            $stmt->close();
            header("Location: projectdetail.php?id=" . $project_id . "#reports");
            exit;
        } else {
            $error = "Error: " . $stmt->error;
        }
    }

    $report['report_name'] = $report_name;
    $report['report_type'] = $report_type;
    $report['report_date'] = $report_date;
}
?>
<!DOCTYPE html>
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
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <?php include 'common/sidebar.php'; ?>

        <div class="layout-page">
          <?php include 'common/navbar.php'; ?>

          <div class="content-wrapper">
            <div class="container-xxl flex-grow-1 container-p-y">

              <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="text-dark m-0">
                  Edit Report <span class="text-muted fs-6">for <?php echo htmlspecialchars($project['project_name'] ?? ''); ?></span>
                </h3>
                <a href="download_report.php?report_id=<?php echo (int)$id; ?>&view=1" class="btn btn-outline-secondary btn-sm" target="_blank">
                  <i class="bi bi-eye"></i> Preview PDF
                </a>
              </div>

              <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
              <?php endif; ?>

              <form method="POST" action="">
                <input type="hidden" name="id" value="<?php echo (int)$id; ?>">

                <!-- Report meta -->
                <div class="card mb-3">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-lg-5 mb-3 mb-lg-0">
                        <label class="form-label">Report Name</label>
                        <input type="text" name="report_name" class="form-control"
                               value="<?php echo htmlspecialchars($report['report_name']); ?>" required>
                      </div>
                      <div class="col-lg-3 mb-3 mb-lg-0">
                        <label class="form-label">Type</label>
                        <select name="report_type" class="form-select">
                          <?php foreach (['Progress', 'Weekly', 'Monthly', 'Final'] as $opt): ?>
                            <option value="<?php echo $opt; ?>" <?php echo ($report['report_type'] === $opt) ? 'selected' : ''; ?>>
                              <?php echo $opt; ?>
                            </option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="col-lg-4">
                        <label class="form-label">Date</label>
                        <input type="date" name="report_date" class="form-control"
                               value="<?php echo htmlspecialchars($report['report_date'] ?? ''); ?>">
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Document-style editor: this is the actual text that -->
                <!-- prints into the PDF letter, shown as it currently   -->
                <!-- reads so it can be edited in place.                 -->
                <div class="card">
                  <div class="card-header bg-light">
                    <i class="bi bi-file-earmark-pdf me-1"></i> Report Letter Content
                    <span class="text-muted fs-7 d-block mt-1" style="font-weight: normal;">
                      This is the exact wording that prints in the PDF. Edit it directly below.
                    </span>
                  </div>
                  <div class="card-body" style="background:#fafafa;">
                    <div class="p-4 mx-auto bg-white shadow-sm" style="max-width: 700px; font-family: Georgia, 'Times New Roman', serif;">

                      <label class="form-label fw-bold text-muted small text-uppercase">Subject</label>
                      <input type="text" name="custom_subject" class="form-control mb-4 fw-bold"
                             style="font-family: inherit; font-size: 1.05rem; border: none; border-bottom: 1px solid #ddd; border-radius: 0;"
                             value="<?php echo htmlspecialchars($subjectVal); ?>">

                      <p class="mb-2" style="font-family: inherit;">Respected Sir/Madam,</p>

                      <label class="form-label fw-bold text-muted small text-uppercase">Opening / Covering Note</label>
                      <textarea name="custom_intro" class="form-control mb-4" rows="5"
                                style="font-family: inherit; font-size: 1rem; border: none; border-bottom: 1px solid #ddd; border-radius: 0; resize: vertical;"
                      ><?php echo htmlspecialchars($introVal); ?></textarea>

                      <p class="text-muted small mb-4">&hellip; work counters and tables print here automatically, based on live project data &hellip;</p>

                      <label class="form-label fw-bold text-muted small text-uppercase">Closing Note</label>
                      <textarea name="custom_closing" class="form-control mb-2" rows="5"
                                style="font-family: inherit; font-size: 1rem; border: none; border-bottom: 1px solid #ddd; border-radius: 0; resize: vertical;"
                      ><?php echo htmlspecialchars($closingVal); ?></textarea>

                    </div>
                  </div>
                </div>

                <div class="mt-3">
                  <button type="submit" class="btn btn-primary me-2">
                    <i class="bx bx-check me-1"></i> Save Changes
                  </button>
                  <a href="projectdetail.php?id=<?php echo (int)$project_id; ?>#reports" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Cancel
                  </a>
                </div>
              </form>

            </div>
          </div>

          <?php include 'common/footer.php'; ?>
        </div>
      </div>
    </div>
  </body>
</html>
