<?php require_once 'common/auth_check.php'; include 'db.php'; include 'common/pagination.php'; ?>
<?php
// Pagination: 10 technologies per page
$per_page = 10;
$page     = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset   = ($page - 1) * $per_page;

// Handle Add / Update Technology form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_technology']) || isset($_POST['update_technology']))) {
    $tech_type  = trim($_POST['tech_type']);
    $name       = trim($_POST['name']);
    $version    = trim($_POST['version']);
    $project_id = !empty($_POST['project_id']) ? (int) $_POST['project_id'] : null;

    if (isset($_POST['update_technology'])) {
        $id = (int) $_POST['tech_id'];
        $stmt = $conn->prepare("UPDATE technologies SET tech_type = ?, name = ?, version = ?, project_id = ? WHERE id = ?");
        $stmt->bind_param("sssii", $tech_type, $name, $version, $project_id, $id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO technologies (tech_type, name, version, project_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $tech_type, $name, $version, $project_id);
        $stmt->execute();
        $stmt->close();
    }

    // Redirect to avoid form re-submission on refresh
    header("Location: " . $_SERVER['PHP_SELF'] . "?" . (isset($_POST['update_technology']) ? "updated=1" : "added=1"));
    exit;
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_technology'])) {
    $id = (int) $_POST['tech_id'];
    $stmt = $conn->prepare("DELETE FROM technologies WHERE id = ?");
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
          <div class="container">
    
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="fw-bold text-dark mt-5">Technology</h2>
      <!-- Add Technology Button -->
      <button type="button" class="btn btn-primary mt-5" data-bs-toggle="modal" data-bs-target="#addTechnologyModal">
        <i class="bx bx-plus me-1"></i> Add Technology
      </button>
    </div>

    <!-- Table Container -->
    <div class="card border rounded-3 shadow-sm ">
      <div class="table-responsive">
        <!-- Added 'table-hover' class to highlight rows on mouseover -->
        <table class="table table-bordered align-middle m-0">
          <thead class="table-light text-center">
            <tr class="fw-bold">
              <th scope="col" class="py-3 px-4 text-dark">Sr No</th>
              <th scope="col" class="text-start py-3 px-4 text-dark" >Technology Type</th>
              <th scope="col" class="text-start py-3 px-4 text-dark">Name</th>
              <th scope="col" class="py-3 px-4 text-dark">Version</th>
              <th scope="col" class="py-3 px-4 text-dark">Project</th>
              <th scope="col" class="py-3 px-4 text-dark">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $result = $conn->query(
                "SELECT t.*, p.project_name FROM technologies t
                 LEFT JOIN projects p ON p.id = t.project_id
                 ORDER BY t.id ASC
                 LIMIT $per_page OFFSET $offset"
            );
            $count_result = $conn->query("SELECT COUNT(*) AS total FROM technologies");
            $total_rows   = $count_result ? (int) $count_result->fetch_assoc()['total'] : 0;
            $total_pages  = max(1, (int) ceil($total_rows / $per_page));
            $sr_no = $offset + 1;
            if ($result && $result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
            ?>
            <tr>
              <td class="text-center py-3 px-4 text-dark"><?php echo $sr_no++; ?></td>
              <td class="text-start py-3 px-4 fw-medium text-dark"><?php echo htmlspecialchars($row['tech_type']); ?></td>
              <td class="text-start py-3 px-4 fw-semibold text-dark"><?php echo htmlspecialchars($row['name']); ?></td>
              <td class="text-center py-3 px-4 text-dark"><?php echo htmlspecialchars($row['version']); ?></td>
              <td class="text-center py-3 px-4 text-dark">
                <?php if (!empty($row['project_name'])): ?>
                  <a href="projectdetail.php?id=<?php echo (int) $row['project_id']; ?>"><?php echo htmlspecialchars($row['project_name']); ?></a>
                <?php else: ?>
                  <span class="text-muted">— Unlinked —</span>
                <?php endif; ?>
              </td>
              <td class="text-center py-3 px-4">
                <!-- Hover added -->
                <button type="button" class="btn btn-dark btn-sm px-3 py-1 border shadow-none text-white bg-primary btn-edit-tech"
                  data-id="<?php echo htmlspecialchars($row['id']); ?>"
                  data-tech_type="<?php echo htmlspecialchars($row['tech_type']); ?>"
                  data-name="<?php echo htmlspecialchars($row['name']); ?>"
                  data-version="<?php echo htmlspecialchars($row['version']); ?>"
                  data-project_id="<?php echo htmlspecialchars($row['project_id']); ?>"
                ><i class="fa-solid fa-pen-to-square"></i></button>
                <button type="button" class="btn btn-dark btn-sm px-3 p-1 border shadow-none text-white bg-primary btn-delete-tech"
                  data-id="<?php echo htmlspecialchars($row['id']); ?>"
                  data-name="<?php echo htmlspecialchars($row['name']); ?>"
                ><i class="bx bx-trash me-1"></i></button>
              </td>
            </tr>
            <?php
                endwhile;
            else:
            ?>
            <tr>
              <td colspan="5" class="text-center py-4 text-muted">No technologies found.</td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php render_pagination($page, $total_pages); ?>

    </div>

  </div>

  <!-- Add / Edit Technology Modal -->
  <div class="modal fade" id="addTechnologyModal" tabindex="-1" aria-labelledby="addTechnologyModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form method="POST" action="" id="techForm">
          <input type="hidden" name="tech_id" id="tech_id" value="">
          <div class="modal-header">
            <h5 class="modal-title" id="addTechnologyModalLabel">Add Technology</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="tech_type" class="form-label">Technology Type</label>
              <input type="text" class="form-control" id="tech_type" name="tech_type" required>
            </div>
            <div class="mb-3">
              <label for="name" class="form-label">Name</label>
              <input type="text" class="form-control" id="name" name="name" required>
            </div>
            <div class="mb-3">
              <label for="version" class="form-label">Version</label>
              <input type="text" class="form-control" id="version" name="version" required>
            </div>
            <div class="mb-3">
              <label for="tech_project_id" class="form-label">Project</label>
              <select class="form-select" id="tech_project_id" name="project_id">
                <option value="">— Not linked to a project —</option>
                <?php if ($projects_list): $projects_list->data_seek(0); while ($p = $projects_list->fetch_assoc()): ?>
                  <option value="<?php echo (int) $p['id']; ?>" <?php echo ($preselected_project_id === (int) $p['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($p['project_name']); ?>
                  </option>
                <?php endwhile; endif; ?>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="add_technology" id="techSubmitBtn" class="btn btn-primary">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Hidden delete form, submitted via JS on confirm -->
  <form method="POST" action="" id="deleteTechForm" style="display:none;">
    <input type="hidden" name="tech_id" id="delete_tech_id" value="">
    <input type="hidden" name="delete_technology" value="1">
  </form>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var modalEl = document.getElementById('addTechnologyModal');
      var modalTitle = document.getElementById('addTechnologyModalLabel');
      var techForm = document.getElementById('techForm');
      var techIdInput = document.getElementById('tech_id');
      var techTypeInput = document.getElementById('tech_type');
      var nameInput = document.getElementById('name');
      var versionInput = document.getElementById('version');
      var projectIdInput = document.getElementById('tech_project_id');
      var submitBtn = document.getElementById('techSubmitBtn');

      function resetToAddMode() {
        modalTitle.textContent = 'Add Technology';
        submitBtn.textContent = 'Save';
        submitBtn.name = 'add_technology';
        techIdInput.value = '';
        techForm.reset();
        projectIdInput.value = '<?php echo $preselected_project_id ?: ""; ?>';
      }

      // "Add Technology" header button should always start fresh
      document.querySelector('[data-bs-target="#addTechnologyModal"]').addEventListener('click', resetToAddMode);

      // Edit buttons: populate the modal with this row's data
      document.querySelectorAll('.btn-edit-tech').forEach(function (btn) {
        btn.addEventListener('click', function () {
          modalTitle.textContent = 'Edit Technology';
          submitBtn.textContent = 'Update';
          submitBtn.name = 'update_technology';

          techIdInput.value = btn.getAttribute('data-id');
          techTypeInput.value = btn.getAttribute('data-tech_type');
          nameInput.value = btn.getAttribute('data-name');
          versionInput.value = btn.getAttribute('data-version');
          projectIdInput.value = btn.getAttribute('data-project_id') || '';

          var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
          modal.show();
        });
      });

      // Delete buttons: confirm then submit hidden delete form
      var deleteForm = document.getElementById('deleteTechForm');
      var deleteTechIdInput = document.getElementById('delete_tech_id');
      document.querySelectorAll('.btn-delete-tech').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var name = btn.getAttribute('data-name');
          Swal.fire({
            title: 'Delete technology "' + name + '"?',
            text: 'This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d33'
          }).then(function (result) {
            if (result.isConfirmed) {
              deleteTechIdInput.value = btn.getAttribute('data-id');
              deleteForm.submit();
            }
          });
        });
      });

      // Success alerts after redirect (add / update / delete)
      var params = new URLSearchParams(window.location.search);
      var alertMap = {
        added: { title: 'Added!', text: 'Technology added successfully.' },
        updated: { title: 'Updated!', text: 'Technology updated successfully.' },
        deleted: { title: 'Deleted!', text: 'Technology deleted successfully.' }
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


          <!-- Content wrapper -->

            <?php include 'common/footer.php'; ?>
        </div>
        <!-- / Layout page -->
      </div>
    </div>
    <!-- / Layout wrapper -->
  </body>
</html>
