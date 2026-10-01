<?php require_once 'common/auth_check.php'; include 'db.php'; include 'common/pagination.php'; ?>
<?php
// Pagination: 10 dashboard pages per page
$per_page = 10;
$page     = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset   = ($page - 1) * $per_page;

// Handle Add / Update Dashboard Page (now a popup modal on this page,
// see the "Add / Edit Dashboard Page" modal further down).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_dash_page']) || isset($_POST['update_dash_page']))) {
    $page_name    = trim($_POST['page_name'] ?? '');
    $status       = trim($_POST['status'] ?? 'Pending');
    $created_date = trim($_POST['created_date'] ?? '');
    $end_date_raw = trim($_POST['end_date'] ?? '');
    $end_date     = $end_date_raw !== '' ? $end_date_raw : null;
    $project_id   = !empty($_POST['project_id']) ? (int) $_POST['project_id'] : null;

    if ($page_name === '') {
        $dash_form_error = "Page Name is required.";
    } else {
        if (isset($_POST['update_dash_page'])) {
            $dash_id = (int) $_POST['dash_id'];
            $created_date_val = $created_date !== '' ? $created_date : null;
            // Editing here in the app means this is now the most recent touch,
            // so clear any "last touched via API key" badge shown in the list.
            $stmt = $conn->prepare("UPDATE dashboard_work SET page_name = ?, status = ?, created_date = ?, end_date = ?, project_id = ?, last_api_key_id = NULL, last_api_touched_at = NULL WHERE id = ?");
            $stmt->bind_param("ssssii", $page_name, $status, $created_date_val, $end_date, $project_id, $dash_id);
            $stmt->execute();
            $stmt->close();
            header("Location: dashboardwork.php?updated=1");
            exit;
        } else {
            $created_date_val = $created_date !== '' ? $created_date : date('Y-m-d');
            $stmt = $conn->prepare("INSERT INTO dashboard_work (page_name, status, created_date, end_date, project_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $page_name, $status, $created_date_val, $end_date, $project_id);
            $stmt->execute();
            $stmt->close();
            header("Location: dashboardwork.php?added=1");
            exit;
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

          
  <!-- Content wrapper -->
         <div class="p-3 p-md-4">

        <!-- Main Card Block -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 px-4 py-3">
                <h1 class="h4 fw-bold text-dark m-0"><i class="bx bx-grid-alt me-2 text-primary"></i>Dashboard Work</h1>
                <button type="button" id="showAddDashBtn" class="btn btn-primary px-3 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2" data-bs-toggle="modal" data-bs-target="#addDashModal">
                    <i class="bx bx-plus fs-5"></i> Add Page
                </button>
            </div>

            <!-- Tab Contents -->
            <div class="card-body p-0">
                <div class="tab-content" id="myTabContent">
                    <!-- Designed Pages Tab -->
                    <div class="tab-pane fade show active" id="designed" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-light">
                                    <tr class="text-secondary small">
                                        <th scope="col" class="ps-4 py-3 fw-bold text-dark">Sr No</th>
                                        <th scope="col" class="py-3 fw-bold text-dark">Page Name</th>
                                        <th scope="col" class="py-3 fw-bold text-dark">Status</th>
                                        <th scope="col" class="py-3 fw-bold text-dark">Project</th>
                                        <th scope="col" class="py-3 fw-bold text-dark">Created Date</th>
                                        <th scope="col" class="py-3 fw-bold text-dark">End Date</th>
                                        <!-- <th scope="col" class="py-3 fw-bold text-dark">API Key</th> -->
                                        <th scope="col" class="text-center py-3 fw-bold text-dark pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                  $result = $conn->query(
    "SELECT dw.*, p.project_name
     FROM dashboard_work dw
     LEFT JOIN projects p ON p.id = dw.project_id
     ORDER BY dw.id ASC
     LIMIT $per_page OFFSET $offset"
);
                                    $count_result = $conn->query("SELECT COUNT(*) AS total FROM dashboard_work");
                                    $total_rows   = $count_result ? (int) $count_result->fetch_assoc()['total'] : 0;
                                    $total_pages  = max(1, (int) ceil($total_rows / $per_page));
                                    $sr_no = $offset + 1;
                                    if ($result && $result->num_rows > 0):
                                        while ($row = $result->fetch_assoc()):
                                            $badgeClass = ($row['status'] === 'Developed')
                                                ? 'bg-success-subtle text-success border border-success-subtle'
                                                : (($row['status'] === 'Designed')
                                                    ? 'bg-primary-subtle text-primary border border-primary-subtle'
                                                    : 'bg-warning-subtle text-warning border border-warning-subtle');
                                    ?>
                                    <tr>
                                        <td class="ps-4 py-3 text-secondary small"><?php echo $sr_no++; ?></td>
                                        <td class="fw-medium text-dark py-3"><?php echo htmlspecialchars($row['page_name']); ?></td>
                                        <td class="py-3">
                                            <span class="badge <?php echo $badgeClass; ?> rounded-pill px-3 py-2 fw-semibold"><?php echo htmlspecialchars($row['status']); ?></span>
                                        </td>
                                        <td class="py-3 text-dark">
                                            <?php if (!empty($row['project_name'])): ?>
                                                <a href="projectdetail.php?id=<?php echo (int) $row['project_id']; ?>" class="text-decoration-none"><?php echo htmlspecialchars($row['project_name']); ?></a>
                                            <?php else: ?>
                                                <span class="text-muted">— Unlinked —</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3 text-dark"><?php echo $row['created_date'] ? date('M d, Y', strtotime($row['created_date'])) : ''; ?></td>
                                        <td class="py-3 text-dark"><?php echo !empty($row['end_date']) ? date('M d, Y', strtotime($row['end_date'])) : '—'; ?></td>
                                        <!-- <td class="py-3 text-dark">
                                            <?php if (!empty($row['api_key_label'])): ?>
                                                <span class="badge bg-info-subtle text-info border-0" title="Last touched via API on <?php echo htmlspecialchars($row['last_api_touched_at']); ?>">
                                                    <i class="bi bi-key me-1"></i><?php echo htmlspecialchars($row['api_key_label']); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">— Created in app —</span>
                                            <?php endif; ?>
                                        </td> -->
                                        <td class="text-center pe-4 py-3">
    <div class="d-flex justify-content-center gap-2">
    <button type="button"
       class="btn btn-primary btn-sm rounded-3 btn-edit-dash"
       data-bs-toggle="modal" data-bs-target="#addDashModal"
       data-id="<?php echo (int) $row['id']; ?>"
       data-page_name="<?php echo htmlspecialchars($row['page_name']); ?>"
       data-status="<?php echo htmlspecialchars($row['status']); ?>"
       data-created_date="<?php echo htmlspecialchars($row['created_date']); ?>"
       data-end_date="<?php echo htmlspecialchars($row['end_date']); ?>"
       data-project_id="<?php echo htmlspecialchars($row['project_id']); ?>"
    ><i class="fa-solid fa-pen-clip"></i></button>
    <a href="delete_dashboard_work.php?id=<?php echo (int)$row['id']; ?>"
       class="btn btn-danger btn-sm rounded-3 btn-delete-page"
       data-name="<?php echo htmlspecialchars($row['page_name']); ?>"><i class="fa-regular fa-trash-can"></i></a>
    </div>
</td>
                                    </tr>
                                    <?php
                                        endwhile;
                                    else:
                                    ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No dashboard pages found.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="px-4 pb-3 pt-2">
                          <?php render_pagination($page, $total_pages); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
 <!-- Content wrapper -->



          
            <?php include 'common/footer.php'; ?>
        </div>
        <!-- / Layout page -->
      </div>
    </div>
    <!-- / Layout wrapper -->

    <!-- Add / Edit Dashboard Page Modal -->
    <div class="modal fade" id="addDashModal" tabindex="-1" aria-labelledby="addDashModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
          <form method="POST" action="" id="dashForm">
            <input type="hidden" name="dash_id" id="dash_id" value="">
            <div class="modal-header">
              <h5 class="modal-title fw-bold" id="addDashModalLabel">Add Dashboard Page</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div id="dashFormError" class="alert alert-danger py-2" style="display:none;"></div>
              <div class="row g-4">
                <div class="col-12 col-md-6">
                  <label for="dash_page_name" class="form-label fw-semibold">Page Name</label>
                  <input type="text" class="form-control" id="dash_page_name" name="page_name" placeholder="Enter page name" required>
                </div>
                <div class="col-12 col-md-6">
                  <label for="dash_status" class="form-label fw-semibold">Status</label>
                  <select class="form-select" id="dash_status" name="status">
                    <option value="Pending">Pending</option>
                    <option value="Designed">Designed</option>
                    <option value="Developed">Developed</option>
                  </select>
                </div>
                <div class="col-12 col-md-6">
                  <label for="dash_created_date" class="form-label fw-semibold">Created Date</label>
                  <input type="date" class="form-control" id="dash_created_date" name="created_date">
                </div>
                <div class="col-12 col-md-6">
                  <label for="dash_end_date" class="form-label fw-semibold">End Date</label>
                  <input type="date" class="form-control" id="dash_end_date" name="end_date">
                </div>
                <div class="col-12 col-md-6">
                  <label for="dash_project_id" class="form-label fw-semibold">Project</label>
                  <select class="form-select" id="dash_project_id" name="project_id">
                    <option value="">— Not linked to a project —</option>
                    <?php if ($projects_list): $projects_list->data_seek(0); while ($p = $projects_list->fetch_assoc()): ?>
                      <option value="<?php echo (int) $p['id']; ?>" <?php echo ($preselected_project_id === (int) $p['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($p['project_name']); ?>
                      </option>
                    <?php endwhile; endif; ?>
                  </select>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary px-4 py-2" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" name="add_dash_page" id="dashSubmitBtn" class="btn btn-primary px-4 py-2">
                <i class="fa-solid fa-check me-1"></i> Save Page
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script>
      document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.btn-delete-page').forEach(function (link) {
          link.addEventListener('click', function (e) {
            e.preventDefault();
            var name = link.getAttribute('data-name');
            var href = link.getAttribute('href');
            Swal.fire({
              title: 'Delete page "' + name + '"?',
              text: 'This cannot be undone.',
              icon: 'warning',
              showCancelButton: true,
              confirmButtonText: 'Yes, delete',
              cancelButtonText: 'Cancel',
              confirmButtonColor: '#d33'
            }).then(function (result) {
              if (result.isConfirmed) {
                window.location.href = href;
              }
            });
          });
        });

        // ---- Add / Edit Dashboard Page modal ----
        var dashModalEl = document.getElementById('addDashModal');
        var dashModalTitle = document.getElementById('addDashModalLabel');
        var dashForm = document.getElementById('dashForm');
        var dashIdInput = document.getElementById('dash_id');
        var dashSubmitBtn = document.getElementById('dashSubmitBtn');

        function resetDashFormToAddMode() {
          dashModalTitle.textContent = 'Add Dashboard Page';
          dashSubmitBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Save Page';
          dashSubmitBtn.name = 'add_dash_page';
          dashIdInput.value = '';
          dashForm.reset();
          document.getElementById('dashFormError').style.display = 'none';
          document.getElementById('dash_project_id').value = '<?php echo $preselected_project_id ?: ""; ?>';
        }

        document.getElementById('showAddDashBtn').addEventListener('click', resetDashFormToAddMode);

        document.querySelectorAll('.btn-edit-dash').forEach(function (btn) {
          btn.addEventListener('click', function () {
            dashModalTitle.textContent = 'Edit Dashboard Page';
            dashSubmitBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Update Page';
            dashSubmitBtn.name = 'update_dash_page';
            document.getElementById('dashFormError').style.display = 'none';

            dashIdInput.value = btn.getAttribute('data-id');
            document.getElementById('dash_page_name').value = btn.getAttribute('data-page_name');
            document.getElementById('dash_status').value = btn.getAttribute('data-status');
            document.getElementById('dash_created_date').value = btn.getAttribute('data-created_date');
            document.getElementById('dash_end_date').value = btn.getAttribute('data-end_date') || '';
            document.getElementById('dash_project_id').value = btn.getAttribute('data-project_id') || '';
          });
        });

        <?php if (!empty($dash_form_error)): ?>
        document.getElementById('dashFormError').textContent = <?php echo json_encode($dash_form_error); ?>;
        document.getElementById('dashFormError').style.display = 'block';
        var reopenModal = bootstrap.Modal.getOrCreateInstance(dashModalEl);
        reopenModal.show();
        <?php endif; ?>

        // Success alerts after redirect (add / update / delete)
        var params = new URLSearchParams(window.location.search);
        var alertMap = {
          added: { title: 'Added!', text: 'Page added successfully.' },
          updated: { title: 'Updated!', text: 'Page updated successfully.' },
          deleted: { title: 'Deleted!', text: 'Page deleted successfully.' }
        };
        Object.keys(alertMap).forEach(function (key) {
          if (params.get(key) === '1') {
            Swal.fire({
              title: alertMap[key].title,
              text: alertMap[key].text,
              icon: 'success',
              timer: 2000,
              showConfirmButton: false
            });
            params.delete(key);
            var newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.history.replaceState({}, document.title, newUrl);
          }
        });
      });
    </script>
  </body>
</html>
