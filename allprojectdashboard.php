<?php require_once 'common/auth_check.php'; include 'db.php'; include 'common/pagination.php'; ?>
<?php
// Pagination: 10 projects per page
$per_page = 10;
$page     = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset   = ($page - 1) * $per_page;
?>
<?php
// Handles the "Add Project" popup modal's submission (see
// common/add_project_modal.php, included further down this page).
require_once 'common/add_project_handler.php';
?>
<?php
// Handle Delete (the Delete button used to do nothing - now wired up like allclient.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_project'])) {
    $del_id = (int) $_POST['project_id'];
    $stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    $stmt->execute();
    $stmt->close();

    header("Location: allprojectdashboard.php?deleted=1");
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
         <div class="p-3 p-md-4">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
          <div class="card-header bg-white border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 px-4 py-3">
            <h1 class="h4 fw-bold text-dark m-0"><i class="bx bx-briefcase-alt-2 me-2 text-primary"></i>All Projects</h1>
            <button type="button" class="btn btn-primary px-3 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2" data-bs-toggle="modal" data-bs-target="#addProjectModal"><i class="bx bx-plus fs-5"></i> Add Project</button>
          </div>

          <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table align-middle mb-0">
            <thead class="table-light">
              <tr class="text-secondary small">
                <th scope="col" class="ps-4 py-3 fw-bold text-dark">Sr No</th>
                <th scope="col" class="py-3 fw-bold text-dark">Project Name</th>
                <th scope="col" class="py-3 fw-bold text-dark">Client</th>
                <th scope="col" class="py-3 fw-bold text-dark">Email</th>
                <th scope="col" class="py-3 fw-bold text-dark">Phone</th>
                <th scope="col" class="py-3 fw-bold text-dark">Start Date</th>
                <th scope="col" class="py-3 fw-bold text-dark">Status</th>
                <th scope="col" class="py-3 fw-bold text-dark text-center pe-4">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              // LEFT JOIN clients so this list always reflects the live client
              // record (name/org) for any project that is linked via client_id.
              $result = $conn->query(
                  "SELECT p.*, c.id AS linked_client_id, c.client_name AS linked_client_name
                   FROM projects p
                   LEFT JOIN clients c ON c.id = p.client_id
                   ORDER BY p.created_at DESC
                   LIMIT $per_page OFFSET $offset"
              );
              $count_result = $conn->query("SELECT COUNT(*) AS total FROM projects");
              $total_rows   = $count_result ? (int) $count_result->fetch_assoc()['total'] : 0;
              $total_pages  = max(1, (int) ceil($total_rows / $per_page));
              $sr_no = $offset + 1;
              if ($result && $result->num_rows > 0):
                  while ($row = $result->fetch_assoc()):
                      $status = $row['status'];
                      if ($status === 'Completed') { $badge = 'bg-success-subtle text-success border border-success-subtle'; }
                      elseif ($status === 'On Hold') { $badge = 'bg-warning-subtle text-warning border border-warning-subtle'; }
                      else { $badge = 'bg-primary-subtle text-primary border border-primary-subtle'; }
                      $progress = (int)$row['progress'];
                      $start_date = !empty($row['start_date']) ? date('d M Y', strtotime($row['start_date'])) : '—';
              ?>
              <tr>
                <td class="ps-4 text-secondary small"><?php echo $sr_no++; ?></td>
                <td class="fw-medium"><a href="projectdetail.php?id=<?php echo $row['id']; ?>" class="text-dark text-decoration-none"><?php echo htmlspecialchars($row['project_name']); ?></a></td>
                <td class="text-secondary small">
                  <?php if (!empty($row['linked_client_id'])): ?>
                    <a href="clientsdetail.php?id=<?php echo (int) $row['linked_client_id']; ?>" class="text-decoration-none"><?php echo htmlspecialchars($row['linked_client_name']); ?></a>
                  <?php else: ?>
                    <?php echo htmlspecialchars($row['client_name']); ?>
                  <?php endif; ?>
                </td>
                <td class="text-secondary small"><?php echo htmlspecialchars($row['email']); ?></td>
                <td class="text-secondary small"><?php echo htmlspecialchars($row['phone']); ?></td>
                <td class="text-secondary small"><?php echo $start_date; ?></td>
                <td>
                  <span class="badge <?php echo $badge; ?> rounded-pill px-3 py-2 fw-semibold">
                    <?php echo htmlspecialchars($status); ?>
                  </span>
                </td>

                <td class="text-center pe-4">
                  <div class="d-flex justify-content-center gap-2">
                    <a href="projectdetail.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-secondary btn-sm rounded-3" title="View">
                      <i class="fa-solid fa-eye"></i>
                    </a>
                    <a href="edit_slider.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm rounded-3" title="Edit"><i class="fa-solid fa-pen-clip"></i></a>
                    <button
                      type="button"
                      class="btn btn-danger btn-sm rounded-3 btn-delete-project"
                      title="Delete"
                      data-id="<?php echo $row['id']; ?>"
                      data-name="<?php echo htmlspecialchars($row['project_name']); ?>"
                    ><i class="fa-regular fa-trash-can"></i></button>
                  </div>
                </td>
              </tr>
              <?php
                  endwhile;
              else:
              ?>
              <tr>
                <td colspan="8" class="text-center py-4 text-muted">No projects found. <a href="#" data-bs-toggle="modal" data-bs-target="#addProjectModal">Add a project</a></td>
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
          <!-- Content wrapper -->



          
            <?php include 'common/footer.php'; ?>
        </div>
        <!-- / Layout page -->
      </div>
    </div>
    <!-- / Layout wrapper -->

  <!-- Hidden delete form -->
  <form method="POST" action="" id="deleteProjectForm" style="display:none;">
    <input type="hidden" name="project_id" id="delete_project_id" value="">
    <input type="hidden" name="delete_project" value="1">
  </form>

  <?php include 'common/add_project_modal.php'; ?>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var deleteForm = document.getElementById('deleteProjectForm');
      var deleteProjectIdInput = document.getElementById('delete_project_id');

      document.querySelectorAll('.btn-delete-project').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var name = btn.getAttribute('data-name');
          Swal.fire({
            title: 'Delete project "' + name + '"?',
            text: 'This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d33'
          }).then(function (result) {
            if (result.isConfirmed) {
              deleteProjectIdInput.value = btn.getAttribute('data-id');
              deleteForm.submit();
            }
          });
        });
      });

      // Success alerts after redirect (add / update / delete)
      var params = new URLSearchParams(window.location.search);
      var alertMap = {
        added: { title: 'Added!', text: 'Project added successfully.' },
        updated: { title: 'Updated!', text: 'Project updated successfully.' },
        deleted: { title: 'Deleted!', text: 'Project deleted successfully.' }
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
