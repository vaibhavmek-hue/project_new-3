<?php require_once 'common/auth_check.php'; include 'db.php'; include 'common/pagination.php'; ?>
<?php
// Pagination: 10 pages per page
$per_page = 10;
$page     = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset   = ($page - 1) * $per_page;

// Handle Add / Update Page form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_page']) || isset($_POST['update_page']))) {
    $page_name    = trim($_POST['page_name']);
    $status       = trim($_POST['status']);
    $created_date = trim($_POST['created_date']);
    $end_date_raw = trim($_POST['end_date'] ?? '');
    $end_date     = $end_date_raw !== '' ? $end_date_raw : null;
    $project_id   = !empty($_POST['project_id']) ? (int) $_POST['project_id'] : null;

    if (isset($_POST['update_page'])) {
        // UPDATE existing page
        $id = (int) $_POST['page_id'];
       $stmt = $conn->prepare(
    "UPDATE website_work
     SET page_name = ?,
         status = ?,
         created_date = ?,
         end_date = ?,
         project_id = ?
     WHERE id = ?"
); $stmt->bind_param("ssssii", $page_name, $status, $created_date, $end_date, $project_id, $id);
        $stmt->execute();
        $stmt->close();
    } else {
        // INSERT new page
        $stmt = $conn->prepare("INSERT INTO website_work (page_name, status, created_date, end_date, project_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssi", $page_name, $status, $created_date, $end_date, $project_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?" . (isset($_POST['update_page']) ? "updated=1" : "added=1"));
    exit;
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_page'])) {
    $id = (int) $_POST['page_id'];
    $stmt = $conn->prepare("DELETE FROM website_work WHERE id = ?");
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

          <!-- ============ LIST VIEW ============ -->
          <div id="listView" class="p-3 p-md-4">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
              <div class="card-header bg-white border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 px-4 py-3">
                <h1 class="h4 fw-bold m-0 text-dark"><i class="bx bx-globe me-2 text-primary"></i>Website Work</h1>
                <button type="button" id="showAddFormBtn" class="btn btn-primary px-3 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 align-self-sm-auto" data-bs-toggle="modal" data-bs-target="#pageModal">
                  <i class="bx bx-plus fs-5"></i>Add Page
                </button>
              </div>

              <div class="card-body p-0">
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
                      <th scope="col" class="py-3 fw-bold text-dark text-center pe-4">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $result = $conn->query(
    "SELECT ww.*, p.project_name
     FROM website_work ww
     LEFT JOIN projects p ON p.id = ww.project_id
     ORDER BY ww.id ASC
     LIMIT $per_page OFFSET $offset"
);
                    $count_result = $conn->query("SELECT COUNT(*) AS total FROM website_work");
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
                      <td class="ps-4 text-secondary small"><?php echo $sr_no++; ?></td>
                      <td class="fw-medium text-dark"><?php echo htmlspecialchars($row['page_name']); ?></td>
                      <td><span class="badge <?php echo $badgeClass; ?> rounded-pill px-3 py-2 fw-semibold"><?php echo htmlspecialchars($row['status']); ?></span></td>
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
                            data-page_name="<?php echo htmlspecialchars($row['page_name']); ?>"
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
                            data-page_name="<?php echo htmlspecialchars($row['page_name']); ?>"
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
                      <td colspan="8" class="text-center py-4 text-muted">No website pages found.</td>
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

 <!-- ============ ADD / EDIT PAGE MODAL ============ -->
<div class="modal fade" id="pageModal" tabindex="-1"
     aria-labelledby="pageModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <form method="POST" action="" id="pageForm">

                <!-- Hidden Page ID -->
                <input type="hidden" name="page_id" id="page_id" value="">

                <!-- Modal Header -->
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="formTitle">
                        Add Page
                    </h5>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close">
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body">

                    <div class="row g-4">

                        <!-- Page Name -->
                        <div class="col-12 col-md-6">
                            <label for="page_name"
                                   class="form-label fw-semibold">
                                Page Name
                            </label>

                            <input type="text"
                                   class="form-control"
                                   id="page_name"
                                   name="page_name"
                                   placeholder="Enter page name"
                                   required>
                        </div>

                        <!-- Status -->
                        <div class="col-12 col-md-6">
                            <label for="status"
                                   class="form-label fw-semibold">
                                Status
                            </label>

                            <select class="form-select"
                                    id="status"
                                    name="status"
                                    required>

                                <option value="" disabled>
                                    Select status
                                </option>

                                <option value="Designed">
                                    Designed
                                </option>

                                <option value="Developed">
                                    Developed
                                </option>

                            </select>
                        </div>

                        <!-- Created Date -->
                        <div class="col-12 col-md-6">
                            <label for="created_date"
                                   class="form-label fw-semibold">
                                Created Date
                            </label>

                            <input type="date"
                                   class="form-control"
                                   id="created_date"
                                   name="created_date"
                                   required>
                        </div>

                        <!-- End Date -->
                        <div class="col-12 col-md-6">
                            <label for="end_date"
                                   class="form-label fw-semibold">
                                End Date
                            </label>

                            <input type="date"
                                   class="form-control"
                                   id="end_date"
                                   name="end_date">
                        </div>

                        <!-- Project -->
                        <div class="col-12 col-md-6">
                            <label for="project_id"
                                   class="form-label fw-semibold">
                                Project
                            </label>

                            <select class="form-select"
                                    id="project_id"
                                    name="project_id">

                                <option value="">
                                    — Not linked to a project —
                                </option>

                                <?php
                                if ($projects_list):
                                    $projects_list->data_seek(0);

                                    while ($p = $projects_list->fetch_assoc()):
                                ?>

                                    <option
                                        value="<?php echo (int) $p['id']; ?>"
                                        <?php
                                        echo ($preselected_project_id === (int) $p['id'])
                                            ? 'selected'
                                            : '';
                                        ?>
                                    >
                                        <?php
                                        echo htmlspecialchars(
                                            $p['project_name']
                                        );
                                        ?>
                                    </option>

                                <?php
                                    endwhile;
                                endif;
                                ?>

                            </select>
                        </div>

                    </div>

                </div>

                <!-- Modal Footer -->
                <div class="modal-footer">

                    <!-- Back / Cancel Button -->
                    <button type="button"
                            id="cancelAddBtn"
                            class="btn btn-outline-secondary px-4 py-2"
                            data-bs-dismiss="modal">

                        <i class="fa-solid fa-arrow-left me-1"></i>
                        Back
                    </button>

                    <!-- Submit Button -->
                    <button type="submit"
                            name="add_page"
                            id="submitBtn"
                            class="btn btn-primary px-4 py-2">

                        <i class="fa-solid fa-check me-1"></i>
                        Submit
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>
<!-- ============ / ADD / EDIT PAGE MODAL ============ -->



<script>
/* ==========================================
   BACK TO PREVIOUS PAGE
========================================== */
function goBackToPages() {

    // If browser has previous page
    if (document.referrer) {
        window.history.back();
    } else {
        // Change this to your actual page-list URL
        window.location.href = "pages.php";
    }
}


/* ==========================================
   FORM SUBMIT
========================================== */
document.getElementById("pageForm").addEventListener("submit", function (e) {

    const submitBtn = document.getElementById("submitBtn");

    // Prevent double submission
    submitBtn.disabled = true;

    submitBtn.innerHTML =
        '<i class="fa-solid fa-spinner fa-spin me-1"></i> Saving...';

});
</script>

          <!-- Content wrapper -->

            <?php include 'common/footer.php'; ?>

<!-- Hidden delete form, submitted via JS on confirm -->
<form method="POST" action="" id="deleteForm" style="display:none;">
  <input type="hidden" name="page_id" id="delete_page_id" value="">
  <input type="hidden" name="delete_page" value="1">
</form>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    var pageModalEl = document.getElementById('pageModal');
    var pageModal = bootstrap.Modal.getOrCreateInstance(pageModalEl);
    var showAddFormBtn = document.getElementById('showAddFormBtn');

    var pageForm = document.getElementById('pageForm');
    var formTitle = document.getElementById('formTitle');
    var submitBtn = document.getElementById('submitBtn');
    var pageIdInput = document.getElementById('page_id');
    var pageNameInput = document.getElementById('page_name');
    var statusInput = document.getElementById('status');
    var createdDateInput = document.getElementById('created_date');
    var endDateInput = document.getElementById('end_date');
    var projectIdInput = document.getElementById('project_id');

    function resetFormToAddMode() {
      formTitle.textContent = 'Add Page';
      submitBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Save Page';
      submitBtn.name = 'add_page';
      pageIdInput.value = '';
      pageForm.reset();
      projectIdInput.value = '<?php echo $preselected_project_id ?: ""; ?>';
    }

    // Add Page button: the modal opens itself via data-bs-toggle; just reset the form fresh.
    showAddFormBtn.addEventListener('click', function () {
      resetFormToAddMode();
    });

    // Whenever the modal is closed (Cancel, X, backdrop, Esc), reset it back to add mode.
    pageModalEl.addEventListener('hidden.bs.modal', function () {
      resetFormToAddMode();
    });

    // Edit buttons: populate form with row data, switch to edit mode, and open the modal
    document.querySelectorAll('.btn-edit').forEach(function (btn) {
      btn.addEventListener('click', function () {
        formTitle.textContent = 'Edit Page';
        submitBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Update Page';
        submitBtn.name = 'update_page';

        pageIdInput.value = btn.getAttribute('data-id');
        pageNameInput.value = btn.getAttribute('data-page_name');
        statusInput.value = btn.getAttribute('data-status');
        createdDateInput.value = btn.getAttribute('data-created_date');
        endDateInput.value = btn.getAttribute('data-end_date') || '';
        projectIdInput.value = btn.getAttribute('data-project_id') || '';

        pageModal.show();
      });
    });

    // Delete buttons: confirm then submit hidden delete form
    var deleteForm = document.getElementById('deleteForm');
    var deletePageIdInput = document.getElementById('delete_page_id');

    document.querySelectorAll('.btn-delete').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var name = btn.getAttribute('data-page_name');
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
            deletePageIdInput.value = btn.getAttribute('data-id');
            deleteForm.submit();
          }
        });
      });
    });

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
        </div>
        <!-- / Layout page -->
      </div>
    </div>
    <!-- / Layout wrapper -->
  </body>
</html>