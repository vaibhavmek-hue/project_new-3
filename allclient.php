<?php require_once 'common/auth_check.php'; include 'db.php'; include 'common/pagination.php'; ?>
<?php
// Pagination: 10 clients per page
$per_page = 10;
$page     = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$offset   = ($page - 1) * $per_page;

// Handle Add / Update Client (now a popup modal on this page instead of a
// separate addclient.php page — see the "Add / Edit Client" modal below).
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['add_client']) || isset($_POST['update_client']))) {
    $client_name       = trim($_POST['client_name'] ?? '');
    $organization_name = trim($_POST['organization_name'] ?? '');
    $contact_person     = trim($_POST['contact_person'] ?? '');
    $designation        = trim($_POST['designation'] ?? '');
    $email              = trim($_POST['email'] ?? '');
    $mobile             = trim($_POST['mobile'] ?? '');
    $address            = trim($_POST['address'] ?? '');
    $state              = trim($_POST['state'] ?? '');
    $pin_code           = trim($_POST['pin_code'] ?? '');
    $status             = trim($_POST['status'] ?? 'Active');

    if ($client_name === '') {
        $client_form_error = "Client Name is required.";
    } elseif ($mobile !== '' && !preg_match('/^[0-9]{10}$/', $mobile)) {
        $client_form_error = "Mobile Number must be exactly 10 digits, numbers only.";
    } elseif (isset($_POST['update_client'])) {
        $post_id = (int) $_POST['client_id'];
        $stmt = $conn->prepare("UPDATE clients SET client_name=?, organization_name=?, contact_person=?, designation=?, email=?, mobile=?, address=?, state=?, pin_code=?, status=? WHERE id=?");
        $stmt->bind_param("ssssssssssi", $client_name, $organization_name, $contact_person, $designation, $email, $mobile, $address, $state, $pin_code, $status, $post_id);
        $stmt->execute();
        $stmt->close();
        header("Location: allclient.php?updated=1");
        exit;
    } else {
        $stmt = $conn->prepare("INSERT INTO clients (client_name, organization_name, contact_person, designation, email, mobile, address, state, pin_code, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssssssss", $client_name, $organization_name, $contact_person, $designation, $email, $mobile, $address, $state, $pin_code, $status);
        $stmt->execute();
        $stmt->close();
        header("Location: allclient.php?added=1");
        exit;
    }
}

// Handle Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_client'])) {
    $id = (int) $_POST['client_id'];
    $stmt = $conn->prepare("DELETE FROM clients WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: allclient.php?deleted=1");
    exit;
}

// Handle Search
$search = trim($_GET['q'] ?? '');

if ($search !== '') {
    $like = '%' . $search . '%';

    $count_stmt = $conn->prepare(
        "SELECT COUNT(*) AS total FROM clients
         WHERE client_name LIKE ?
            OR organization_name LIKE ?
            OR contact_person LIKE ?
            OR mobile LIKE ?
            OR email LIKE ?"
    );
    $count_stmt->bind_param("sssss", $like, $like, $like, $like, $like);
    $count_stmt->execute();
    $total_rows = (int) $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();

    $stmt = $conn->prepare(
        "SELECT * FROM clients
         WHERE client_name LIKE ?
            OR organization_name LIKE ?
            OR contact_person LIKE ?
            OR mobile LIKE ?
            OR email LIKE ?
         ORDER BY created_at DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->bind_param("sssssii", $like, $like, $like, $like, $like, $per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $count_result = $conn->query("SELECT COUNT(*) AS total FROM clients");
    $total_rows   = $count_result ? (int) $count_result->fetch_assoc()['total'] : 0;

    $result = $conn->query("SELECT * FROM clients ORDER BY created_at DESC LIMIT $per_page OFFSET $offset");
}
$total_pages = max(1, (int) ceil($total_rows / $per_page));
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
          <div class="content-wrapper">
            <!-- Content -->

            <div class="container-xxl flex-grow-1 container-p-y">

              <!-- Basic Bootstrap Table -->
              <div class="card border-0 shadow-sm rounded-4 overflow-hidden">

                <div class="card-header bg-white border-bottom d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 px-4 py-3">
                  <h1 class="h4 fw-bold m-0 text-dark"><i class="bx bx-user-circle me-2 text-primary"></i>All Clients</h1>
                  <button type="button" id="showAddClientBtn" class="btn btn-primary px-3 py-2 fw-semibold d-flex align-items-center justify-content-center gap-2 align-self-sm-auto" data-bs-toggle="modal" data-bs-target="#addClientModal">
                    <i class="bx bx-plus fs-5"></i>Add Client
                  </button>
                </div>

                <div class="card-body p-0">
                  <div class="px-4 pt-4">
                      <!-- Search & Filter Bar -->
                      <form method="GET" action="" class="row g-3 mb-3">
                        <div class="col-lg-4">
                          <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted ps-3">
                              <button type="submit" class="btn btn-link p-0 border-0 text-muted">
                                <i class="bi bi-search"></i>
                              </button>
                            </span>
                            <input type="text" name="q" class="form-control border-start-0 ps-0 shadow-none py-2"
                                   placeholder="Search clients..." value="<?php echo htmlspecialchars($search); ?>">
                          </div>
                        </div>
                        <?php if ($search !== ''): ?>
                        <div class="col-auto">
                          <a href="allclient.php" class="btn btn-outline-secondary bg-white text-dark d-flex align-items-center gap-2 py-2 px-3 fw-medium">
                            <i class="bi bi-x-lg"></i> Clear
                          </a>
                        </div>
                        <?php endif; ?>
                      </form>

                      <?php if ($search !== ''): ?>
                      <div class="text-muted small mb-2">
                        <?php echo $result ? $result->num_rows : 0; ?> result(s) for "<?php echo htmlspecialchars($search); ?>"
                      </div>
                      <?php endif; ?>
                  </div>

                      <!-- Clients Table -->
                      <div class="table-responsive">
                        <table class="table align-middle mb-0">
                          <thead class="table-light">
                            <tr class="text-secondary small">
                              <th class="ps-2 py-3 fw-bold text-dark">Sr No</th>
                              <th class="py-3 fw-bold text-dark">Client Name</th>
                              <th class="py-3 fw-bold text-dark">Organization</th>
                              <th class="py-3 fw-bold text-dark">Mobile</th>
                              <th class="py-3 fw-bold text-dark">Status</th>
                              <th class="py-3 fw-bold text-dark text-center pe-4">Actions</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                            $sr_no = $offset + 1;
                            if ($result && $result->num_rows > 0):
                                while ($row = $result->fetch_assoc()):
                            ?>
                            <tr>
                              <td class="ps-4 text-secondary small"><?php echo $sr_no++; ?></td>
                              <td class="fw-medium text-dark"><?php echo htmlspecialchars($row['client_name']); ?></td>
                              <td class="text-secondary small"><?php echo htmlspecialchars($row['organization_name']); ?></td>
                              <td class="text-secondary small"><?php echo htmlspecialchars($row['mobile']); ?></td>
                              <td>
                                <?php if ($row['status'] === 'Active'): ?>
                                  <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-2 fw-semibold">Active</span>
                                <?php else: ?>
                                  <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-2 fw-semibold">Inactive</span>
                                <?php endif; ?>
                              </td>
                              <td class="text-center pe-4">
                                <div class="d-flex justify-content-center gap-2">
                                  <a href="clientsdetail.php?id=<?php echo $row['id']; ?>" class="btn btn-outline-secondary btn-sm rounded-3" title="View">
                                    <i class="fa-solid fa-eye"></i>
                                  </a>
                                  <button
                                    type="button"
                                    class="btn btn-primary btn-sm rounded-3 btn-edit-client"
                                    title="Edit"
                                    data-bs-toggle="modal"
                                    data-bs-target="#addClientModal"
                                    data-id="<?php echo (int) $row['id']; ?>"
                                    data-client_name="<?php echo htmlspecialchars($row['client_name']); ?>"
                                    data-organization_name="<?php echo htmlspecialchars($row['organization_name']); ?>"
                                    data-contact_person="<?php echo htmlspecialchars($row['contact_person']); ?>"
                                    data-designation="<?php echo htmlspecialchars($row['designation']); ?>"
                                    data-email="<?php echo htmlspecialchars($row['email']); ?>"
                                    data-mobile="<?php echo htmlspecialchars($row['mobile']); ?>"
                                    data-address="<?php echo htmlspecialchars($row['address']); ?>"
                                    data-state="<?php echo htmlspecialchars($row['state']); ?>"
                                    data-pin_code="<?php echo htmlspecialchars($row['pin_code']); ?>"
                                    data-status="<?php echo htmlspecialchars($row['status']); ?>"
                                  >
                                    <i class="fa-solid fa-pen-clip"></i>
                                  </button>
                                  <button
                                    type="button"
                                    class="btn btn-danger btn-sm rounded-3 btn-delete-client"
                                    title="Delete"
                                    data-id="<?php echo $row['id']; ?>"
                                    data-name="<?php echo htmlspecialchars($row['client_name']); ?>"
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
                              <td colspan="6" class="text-center py-4 text-muted">
                                <?php if ($search !== ''): ?>
                                  No clients matched "<?php echo htmlspecialchars($search); ?>". <a href="allclient.php">Clear search</a>
                                <?php else: ?>
                                  No clients found. <a href="#" data-bs-toggle="modal" data-bs-target="#addClientModal">Add a client</a>
                                <?php endif; ?>
                              </td>
                            </tr>
                            <?php endif; ?>

                          </tbody>
                        </table>
                      </div>

                      <div class="px-4 pb-3 pt-2">
                        <?php render_pagination($page, $total_pages, ['q' => $search]); ?>
                      </div>

                </div>
              </div>
              <!--/ Basic Bootstrap Table -->

         

          </div>
          <!-- Content wrapper -->


          
            <?php include 'common/footer.php'; ?>

  <!-- Add / Edit Client Modal -->
  <div class="modal fade" id="addClientModal" tabindex="-1" aria-labelledby="addClientModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="POST" action="" id="clientForm">
          <input type="hidden" name="client_id" id="client_id" value="">
          <div class="modal-header">
            <h5 class="modal-title" id="addClientModalLabel">Add Client</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div id="clientFormError" class="alert alert-danger py-2" style="display:none;"></div>
            <div class="row g-4">
              <div class="col-12 col-md-6">
                <label for="client_name" class="form-label fw-semibold">Client Name</label>
                <input type="text" class="form-control" id="client_name" name="client_name" placeholder="Enter client name" required>
              </div>
              <div class="col-12 col-md-6">
                <label for="organization_name" class="form-label fw-semibold">Organization</label>
                <input type="text" class="form-control" id="organization_name" name="organization_name" placeholder="Enter Organization">
              </div>
                                  
              <div class="col-12 col-md-6">
                <label for="email" class="form-label fw-semibold">Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter email">
              </div>
              <div class="col-12 col-md-6">
                <label for="mobile" class="form-label fw-semibold">Mobile</label>
                <input type="tel" class="form-control" id="mobile" name="mobile" placeholder="Enter mobile" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" title="Enter exactly 10 digits" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)">
              </div>
              <div class="col-12 col-md-6">
                <label for="state" class="form-label fw-semibold">State</label>
                <input type="text" class="form-control" id="state" name="state" placeholder="Enter state">
              </div>
              <div class="col-12 col-md-6">
                <label for="pin_code" class="form-label fw-semibold">Pin Code</label>
                <input type="text" class="form-control" id="pin_code" name="pin_code" placeholder="Enter pin code">
              </div>
              <div class="col-12 col-md-6">
                <label for="status" class="form-label fw-semibold">Status</label>
                <select class="form-select" id="status" name="status" required>
                  <option value="Active">Active</option>
                  <option value="Inactive">Inactive</option>
                </select>
              </div>
              <div class="col-12">
                <label for="address" class="form-label fw-semibold">Address</label>
                <textarea class="form-control" id="address" name="address" rows="2" placeholder="Enter address"></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" name="add_client" id="clientSubmitBtn" class="btn btn-primary px-4">
              <i class="bi bi-check-lg me-1"></i> Save Client
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Hidden delete form -->
  <form method="POST" action="" id="deleteClientForm" style="display:none;">
    <input type="hidden" name="client_id" id="delete_client_id" value="">
    <input type="hidden" name="delete_client" value="1">
  </form>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var deleteForm = document.getElementById('deleteClientForm');
      var deleteClientIdInput = document.getElementById('delete_client_id');

      document.querySelectorAll('.btn-delete-client').forEach(function (btn) {
        btn.addEventListener('click', function () {
          var name = btn.getAttribute('data-name');
          Swal.fire({
            title: 'Delete client "' + name + '"?',
            text: 'This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#d33'
          }).then(function (result) {
            if (result.isConfirmed) {
              deleteClientIdInput.value = btn.getAttribute('data-id');
              deleteForm.submit();
            }
          });
        });
      });

      // ---- Add / Edit Client modal ----
      var clientModalEl = document.getElementById('addClientModal');
      var clientModalTitle = document.getElementById('addClientModalLabel');
      var clientForm = document.getElementById('clientForm');
      var clientIdInput = document.getElementById('client_id');
      var clientSubmitBtn = document.getElementById('clientSubmitBtn');
      var clientFields = ['client_name', 'organization_name', 'contact_person', 'designation', 'email', 'mobile', 'address', 'state', 'pin_code'];

      function resetClientFormToAddMode() {
        clientModalTitle.textContent = 'Add Client';
        clientSubmitBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Save Client';
        clientSubmitBtn.name = 'add_client';
        clientIdInput.value = '';
        clientForm.reset();
        document.getElementById('clientFormError').style.display = 'none';
      }

      document.getElementById('showAddClientBtn').addEventListener('click', resetClientFormToAddMode);

      document.querySelectorAll('.btn-edit-client').forEach(function (btn) {
        btn.addEventListener('click', function () {
          clientModalTitle.textContent = 'Edit Client';
          clientSubmitBtn.innerHTML = '<i class="bi bi-check-lg me-1"></i> Update Client';
          clientSubmitBtn.name = 'update_client';
          document.getElementById('clientFormError').style.display = 'none';

          clientIdInput.value = btn.getAttribute('data-id');
          clientFields.forEach(function (field) {
            var el = document.getElementById(field);
            if (el) { el.value = btn.getAttribute('data-' + field) || ''; }
          });
          document.getElementById('status').value = btn.getAttribute('data-status') || 'Active';
        });
      });

      <?php if (!empty($client_form_error)): ?>
      // A validation error happened server-side (e.g. invalid mobile number) —
      // re-open the modal with what was typed so nothing is lost.
      (function () {
        document.getElementById('clientFormError').textContent = <?php echo json_encode($client_form_error); ?>;
        document.getElementById('clientFormError').style.display = 'block';
        clientIdInput.value = <?php echo json_encode($_POST['client_id'] ?? ''); ?>;
        clientSubmitBtn.name = <?php echo json_encode(isset($_POST['update_client']) ? 'update_client' : 'add_client'); ?>;
        clientModalTitle.textContent = <?php echo json_encode(isset($_POST['update_client']) ? 'Edit Client' : 'Add Client'); ?>;
        clientFields.forEach(function (field) {
          var el = document.getElementById(field);
          if (el) { el.value = <?php echo json_encode($_POST); ?>[field] || ''; }
        });
        document.getElementById('status').value = <?php echo json_encode($_POST['status'] ?? 'Active'); ?>;
        var modal = bootstrap.Modal.getOrCreateInstance(clientModalEl);
        modal.show();
      })();
      <?php endif; ?>

      // Success alerts after redirect (add / update / delete)
      var params = new URLSearchParams(window.location.search);
      var alertMap = {
        added: { title: 'Added!', text: 'Client added successfully.' },
        updated: { title: 'Updated!', text: 'Client updated successfully.' },
        deleted: { title: 'Deleted!', text: 'Client deleted successfully.' }
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