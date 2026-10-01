<?php require_once 'common/auth_check.php'; include 'db.php'; include 'common/pagination.php'; ?>
<?php
// Pagination: 10 screens per page
$per_page = 10;
$page     = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset   = ($page - 1) * $per_page;

// Handle Add / Update Screen form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_screen']) || isset($_POST['update_screen']))) {
    $screen_name  = trim($_POST['screen_name']);
    $status       = trim($_POST['status']);
    $created_date = trim($_POST['created_date']);
    $end_date_raw = trim($_POST['end_date'] ?? '');
    $end_date     = $end_date_raw !== '' ? $end_date_raw : null;
    $project_id   = !empty($_POST['project_id']) ? (int) $_POST['project_id'] : null;

    if (isset($_POST['update_screen'])) {
        // UPDATE existing screen
        $id = (int) $_POST['screen_id'];
        $stmt = $conn->prepare("UPDATE app_work SET screen_name = ?, status = ?, created_date = ?, end_date = ?, project_id = ?, last_api_key_id = NULL, last_api_touched_at = NULL WHERE id = ?");
        $stmt->bind_param("ssssii", $screen_name, $status, $created_date, $end_date, $project_id, $id);
        $stmt->execute();
        $stmt->close();
    } else {
        // INSERT new screen
        $stmt = $conn->prepare("INSERT INTO app_work (screen_name, status, created_date, end_date, project_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $screen_name, $status, $created_date, $end_date, $project_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?" . (isset($_POST['update_screen']) ? "updated=1" : "added=1"));
    exit;
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_screen'])) {
    $id = (int) $_POST['screen_id'];
    $stmt = $conn->prepare("DELETE FROM app_work WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF'] . "?deleted=1");
    exit;
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
          <main>
            <div>

              <!-- ============ LIST VIEW ============ -->
              <div id="listView" class="p-3 p-md-4">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                  <div class="card-header bg-white border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 px-4 py-3">
                    <h1 class="h4 fw-bold text-dark m-0"><i class="bx bx-mobile-alt me-2 text-primary"></i>App Work</h1>
                    <button type="button" id="showAddFormBtn" class="btn btn-primary px-3 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 align-self-sm-auto" data-bs-toggle="modal" data-bs-target="#screenModal">
                      <i class="bx bx-plus fs-5"></i> Add Screen
                    </button>
                  </div>

                  <div class="card-body p-0">
                    <div class="table-responsive">
                      <table class="table align-middle mb-0">
                        <thead class="table-light">
                          <tr class="text-secondary small">
                            <th scope="col" class="ps-4 py-3 fw-bold text-dark">Sr No</th>
                            <th scope="col" class="py-3 fw-bold text-dark">Screen Name</th>
                            <th scope="col" class="py-3 fw-bold text-dark">Status</th>
                            <th scope="col" class="py-3 fw-bold text-dark">Project</th>
                            <th scope="col" class="py-3 fw-bold text-dark">Created Date</th>
                            <th scope="col" class="py-3 fw-bold text-dark">End Date</th>
                            <!-- <th scope="col" class="py-3 fw-bold text-dark">API Key</th> -->
                            <th scope="col" class="py-3 fw-bold text-dark text-center pe-4">Actions</th>
                          </tr> 
                        </thead>
                        <tbody>
                          <?php
                         $result = $conn->query(
    "SELECT aw.*, p.project_name
     FROM app_work aw
     LEFT JOIN projects p ON p.id = aw.project_id
     ORDER BY aw.id ASC
     LIMIT $per_page OFFSET $offset"
);
                          $count_result = $conn->query("SELECT COUNT(*) AS total FROM app_work");
                          $total_rows   = $count_result ? (int) $count_result->fetch_assoc()['total'] : 0;
                          $total_pages  = max(1, (int) ceil($total_rows / $per_page));
                          $sr_no = $offset + 1;
                          if ($result && $result->num_rows > 0):
                              while ($row = $result->fetch_assoc()):
                                  $statusBadge = ($row['status'] === 'Developed')
                                      ? 'bg-success-subtle text-success border border-success-subtle'
                                      : (($row['status'] === 'Designed')
                                          ? 'bg-primary-subtle text-primary border border-primary-subtle'
                                          : 'bg-warning-subtle text-warning border border-warning-subtle');
                          ?>
                          <tr>
                            <td class="ps-4 text-secondary small"><?php echo $sr_no++; ?></td>
                            <td class="fw-medium text-dark"><?php echo htmlspecialchars($row['screen_name']); ?></td>
                            <td>
                              <span class="badge <?php echo $statusBadge; ?> rounded-pill px-3 py-2 fw-semibold">
                                <?php echo htmlspecialchars($row['status']); ?>
                              </span>
                            </td>
                            <td class="text-secondary small">
                              <?php if (!empty($row['project_name'])): ?>
                                <a href="projectdetail.php?id=<?php echo (int) $row['project_id']; ?>" class="text-decoration-none"><?php echo htmlspecialchars($row['project_name']); ?></a>
                              <?php else: ?>
                                <span class="text-muted">— Unlinked —</span>
                              <?php endif; ?>
                            </td>
                            <td class="text-secondary small"><?php echo $row['created_date'] ? date('M d, Y', strtotime($row['created_date'])) : ''; ?></td>
                            <td class="text-secondary small"><?php echo !empty($row['end_date']) ? date('M d, Y', strtotime($row['end_date'])) : '—'; ?></td>
                            <!-- <td class="text-secondary small">
                              <?php if (!empty($row['api_key_label'])): ?>
                                <span class="badge bg-info-subtle text-info border-0" title="Last touched via API on <?php echo htmlspecialchars($row['last_api_touched_at']); ?>">
                                  <i class="bi bi-key me-1"></i><?php echo htmlspecialchars($row['api_key_label']); ?>
                                </span>
                              <?php else: ?>
                                <span class="text-muted">— Created in app —</span>
                              <?php endif; ?>
                            </td> -->
                            <td class="text-center pe-4">
                              <div class="d-flex justify-content-center gap-2">
                                <button
                                  type="button"
                                  class="btn btn-primary btn-sm rounded-3 btn-edit"
                                  title="Edit"
                                  data-id="<?php echo htmlspecialchars($row['id']); ?>"
                                  data-screen_name="<?php echo htmlspecialchars($row['screen_name']); ?>"
                                  data-status="<?php echo htmlspecialchars($row['status']); ?>"
                                  data-created_date="<?php echo htmlspecialchars($row['created_date']); ?>"
                                  data-end_date="<?php echo htmlspecialchars($row['end_date']); ?>"
                                  data-project_id="<?php echo htmlspecialchars($row['project_id']); ?>"
                                >
                                  <i class="fa-solid fa-pen-clip"></i>
                                </button>
                                <button
                                  type="button"
                                  class="btn btn-danger btn-sm rounded-3 btn-delete"
                                  title="Delete"
                                  data-id="<?php echo htmlspecialchars($row['id']); ?>"
                                  data-screen_name="<?php echo htmlspecialchars($row['screen_name']); ?>"
                                >
                                  <i class="fa-regular fa-trash-can"></i>
                                </button>
                              </div>
                            </td>
                          </tr>
                          <?php
                              endwhile;
                          else:
                          ?>
                          <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No app screens found.</td>
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
              <!-- ============ / LIST VIEW ============ -->

              <!-- ============ ADD / EDIT SCREEN MODAL (popup) ============ -->
              <div class="modal fade" id="screenModal" tabindex="-1" aria-labelledby="screenModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                  <div class="modal-content">
                    <form method="POST" action="" id="screenForm">
                      <input type="hidden" name="screen_id" id="screen_id" value="">
                      <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="formTitle">Add Screen</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                      </div>
                      <div class="modal-body">
                        <div class="row g-4">
                          <div class="col-12 col-md-6">
                            <label for="screen_name" class="form-label fw-semibold">Screen Name</label>
                            <input type="text" class="form-control" id="screen_name" name="screen_name" placeholder="Enter screen name" required>
                          </div>

                          <div class="col-12 col-md-6">
                            <label for="status" class="form-label fw-semibold">Status</label>
                            <select class="form-select" id="status" name="status" required>
                              <option value="" disabled>Select status</option>
                              <option value="Pending">Pending</option>
                              <option value="Designed">Designed</option>
                              <option value="Developed">Developed</option>
                            </select>
                          </div>

                          <div class="col-12 col-md-6">
                            <label for="created_date" class="form-label fw-semibold">Created Date</label>
                            <input type="date" class="form-control" id="created_date" name="created_date" required>
                          </div>

                          <div class="col-12 col-md-6">
                            <label for="end_date" class="form-label fw-semibold">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date">
                          </div>

                          <div class="col-12 col-md-6">
                            <label for="project_id" class="form-label fw-semibold">Project</label>
                            <select class="form-select" id="project_id" name="project_id">
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
                        <button type="button" id="cancelAddBtn" class="btn btn-outline-secondary px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_screen" id="submitBtn" class="btn btn-primary px-4 py-2">
                          <i class="fa-solid fa-check me-1"></i> Save Screen
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
              <!-- ============ / ADD / EDIT SCREEN MODAL ============ -->

            </div>
          </main>
          <!-- Content wrapper -->

          <?php include 'common/footer.php'; ?>
        </div>
      </div>
    </div>

    <!-- Hidden delete form, submitted via JS on confirm -->
    <form method="POST" action="" id="deleteForm" style="display:none;">
      <input type="hidden" name="screen_id" id="delete_screen_id" value="">
      <input type="hidden" name="delete_screen" value="1">
    </form>

    <script>
      document.addEventListener('DOMContentLoaded', function () {
        var screenModalEl = document.getElementById('screenModal');
        var screenModal = bootstrap.Modal.getOrCreateInstance(screenModalEl);
        var showAddFormBtn = document.getElementById('showAddFormBtn');

        var screenForm = document.getElementById('screenForm');
        var formTitle = document.getElementById('formTitle');
        var submitBtn = document.getElementById('submitBtn');
        var screenIdInput = document.getElementById('screen_id');
        var screenNameInput = document.getElementById('screen_name');
        var statusInput = document.getElementById('status');
        var createdDateInput = document.getElementById('created_date');
        var endDateInput = document.getElementById('end_date');
        var projectIdInput = document.getElementById('project_id');

        function resetFormToAddMode() {
          formTitle.textContent = 'Add Screen';
          submitBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Save Screen';
          submitBtn.name = 'add_screen';
          screenIdInput.value = '';
          screenForm.reset();
          projectIdInput.value = '<?php echo $preselected_project_id ?: ""; ?>';
        }

        // Add Screen button: the modal opens itself via data-bs-toggle; just reset the form fresh.
        showAddFormBtn.addEventListener('click', function () {
          resetFormToAddMode();
        });

        // Whenever the modal is closed (Cancel, X, backdrop, Esc), reset it back to add mode.
        screenModalEl.addEventListener('hidden.bs.modal', function () {
          resetFormToAddMode();
        });

        // Edit buttons: populate form with row data, switch to edit mode, and open the modal
        document.querySelectorAll('.btn-edit').forEach(function (btn) {
          btn.addEventListener('click', function () {
            formTitle.textContent = 'Edit Screen';
            submitBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Update Screen';
            submitBtn.name = 'update_screen';

            screenIdInput.value = btn.getAttribute('data-id');
            screenNameInput.value = btn.getAttribute('data-screen_name');
            statusInput.value = btn.getAttribute('data-status');
            createdDateInput.value = btn.getAttribute('data-created_date');
            endDateInput.value = btn.getAttribute('data-end_date') || '';
            projectIdInput.value = btn.getAttribute('data-project_id') || '';

            screenModal.show();
          });
        });

        // Delete buttons: confirm then submit hidden delete form
        var deleteForm = document.getElementById('deleteForm');
        var deleteScreenIdInput = document.getElementById('delete_screen_id');

        document.querySelectorAll('.btn-delete').forEach(function (btn) {
          btn.addEventListener('click', function () {
            var name = btn.getAttribute('data-screen_name');
            Swal.fire({
              title: 'Delete screen "' + name + '"?',
              text: 'This cannot be undone.',
              icon: 'warning',
              showCancelButton: true,
              confirmButtonText: 'Yes, delete',
              cancelButtonText: 'Cancel',
              confirmButtonColor: '#d33'
            }).then(function (result) {
              if (result.isConfirmed) {
                deleteScreenIdInput.value = btn.getAttribute('data-id');
                deleteForm.submit();
              }
            });
          });
        });

        // Success alerts after redirect (add / update / delete)
        var params = new URLSearchParams(window.location.search);
        var alertMap = {
          added: { title: 'Added!', text: 'Screen added successfully.' },
          updated: { title: 'Updated!', text: 'Screen updated successfully.' },
          deleted: { title: 'Deleted!', text: 'Screen deleted successfully.' }
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