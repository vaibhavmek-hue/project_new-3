<?php
require_once 'common/auth_check.php';
include 'db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$page = ['page_name' => '', 'status' => 'Designed', 'created_date' => '', 'end_date' => '', 'project_id' => null];

if ($id > 0) {
    $stmt = $conn->prepare("SELECT * FROM dashboard_work WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $page = $res->fetch_assoc();
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $page_name = $conn->real_escape_string($_POST['page_name']);
    $status = $conn->real_escape_string($_POST['status']);
    $created_date = $conn->real_escape_string($_POST['created_date']);
    $end_date_raw = trim($_POST['end_date'] ?? '');
    $end_date = $end_date_raw !== '' ? $end_date_raw : null;
    $project_id = !empty($_POST['project_id']) ? (int) $_POST['project_id'] : null;

    $stmt = $conn->prepare("UPDATE dashboard_work SET page_name = ?, status = ?, created_date = ?, end_date = ?, project_id = ?, last_api_key_id = NULL, last_api_touched_at = NULL WHERE id = ?");
    $stmt->bind_param("ssssii", $page_name, $status, $created_date, $end_date, $project_id, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: dashboardwork.php?updated=1");
    exit;
}

// Projects for the dropdown
$projects_list = $conn->query("SELECT id, project_name FROM projects ORDER BY project_name ASC");
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
      <div class="card border rounded-3 p-4 m-4">
        <h3 class="mb-4">Edit Dashboard Page</h3>
        <form method="POST">
          <div class="mb-3">
            <label class="form-label">Page Name</label>
            <input type="text" name="page_name" class="form-control"
                   value="<?php echo htmlspecialchars($page['page_name']); ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <?php foreach (['Designed', 'Developed', 'Pending'] as $opt): ?>
                <option value="<?php echo $opt; ?>" <?php echo ($page['status'] === $opt) ? 'selected' : ''; ?>>
                  <?php echo $opt; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Created Date</label>
            <input type="date" name="created_date" class="form-control"
                   value="<?php echo htmlspecialchars($page['created_date']); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">End Date</label>
            <input type="date" name="end_date" class="form-control"
                   value="<?php echo htmlspecialchars($page['end_date'] ?? ''); ?>">
          </div>
          <div class="mb-3">
            <label class="form-label">Project</label>
            <select name="project_id" class="form-select">
              <option value="">— Not linked to a project —</option>
              <?php if ($projects_list): while ($p = $projects_list->fetch_assoc()): ?>
                <option value="<?php echo (int) $p['id']; ?>" <?php echo ((int)$page['project_id'] === (int)$p['id']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($p['project_name']); ?>
                </option>
              <?php endwhile; endif; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">Save Changes</button>
          <a href="dashboardwork.php" class="btn btn-secondary">Cancel</a>
        </form>
      </div>
      <?php include 'common/footer.php'; ?>
    </div>
  </div>
</div>
</body>
</html>