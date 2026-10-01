<?php require_once 'common/auth_check.php'; include 'db.php'; ?>
<?php
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;

$client = [
    'client_name' => '', 'organization_name' => '', 'contact_person' => '',
    'email' => '', 'mobile' => '', 'state' => '',
    'pin_code' => '', 'address' => '', 'designation' => '', 'status' => 'Active'
];

if ($isEdit) {
    $res = $conn->query("SELECT * FROM clients WHERE id = $id");
    if ($res && $res->num_rows > 0) {
        $client = $res->fetch_assoc();
    }
}

// Handle form submission (Add or Update)
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_name       = trim($_POST['client_name']);
    $organization_name = trim($_POST['organization_name']);
    $contact_person     = trim($_POST['contact_person']);
    $designation        = trim($_POST['designation']);
    $email              = trim($_POST['email']);
    $mobile             = trim($_POST['mobile']);
    $address            = trim($_POST['address']);
    $state              = trim($_POST['state']);
    $pin_code           = trim($_POST['pin_code']);
    $status             = trim($_POST['status']);

    // Keep the form's own state in sync so a failed submission re-shows what was typed
    $client = array_merge($client, compact(
        'client_name', 'organization_name', 'contact_person', 'designation',
        'email', 'mobile', 'address', 'state', 'pin_code', 'status'
    ));
    if (isset($_POST['client_id'])) {
        $client['id'] = $_POST['client_id'];
    }

    if ($mobile !== '' && !preg_match('/^[0-9]{10}$/', $mobile)) {
        $error = "Mobile Number must be exactly 10 digits, numbers only.";
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
            <div class="container-xxl flex-grow-1 container-p-y">

              <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold m-0 text-dark"><?php echo $isEdit ? 'Edit Client' : 'Add Client'; ?></h2>
                
              </div>

              <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-3 p-md-4">
                  <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error); ?></div>
                  <?php endif; ?>
                  <form method="POST" action="">
                    <?php if ($isEdit || isset($client['id'])): ?>
                      <input type="hidden" name="client_id" value="<?php echo htmlspecialchars($client['id']); ?>">
                    <?php endif; ?>

                    <div class="row g-4">

                      <div class="col-12 col-md-6">
                        <label for="client_name" class="form-label fw-semibold">Client Name</label>
                        <input type="text" class="form-control" id="client_name" name="client_name" value="<?php echo htmlspecialchars($client['client_name']);  ?>" placeholder="Enter client name" required>
                        
                      </div>

                      <div class="col-12 col-md-6">
                        <label for="organization_name" class="form-label fw-semibold">Organization</label>
                        <input type="text" class="form-control" id="organization_name" name="organization_name" value="<?php echo htmlspecialchars($client['organization_name']); ?>"placeholder="Enter Organization">
                      </div>

                      

                      

                      <div class="col-12 col-md-6">
                        <label for="email" class="form-label fw-semibold">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($client['email']); ?>"placeholder="Enter email">
                      </div>

                      <div class="col-12 col-md-6">
                        <label for="mobile" class="form-label fw-semibold">Mobile</label>
                        <input type="tel" class="form-control" id="mobile" name="mobile" value="<?php echo htmlspecialchars($client['mobile']); ?>" placeholder="Enter mobile" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" title="Enter exactly 10 digits" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)">
                      </div>
                      
                      <div class="col-12 col-md-6">
                        <label for="status" class="form-label fw-semibold">Status</label>
                        <select class="form-select" id="status" name="status" required>
                          <option value="Active" <?php echo ($client['status'] === 'Active') ? 'selected' : ''; ?>>Active</option>
                          <option value="Inactive" <?php echo ($client['status'] === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                      </div>

                      <div class="col-12">
                        <label for="address" class="form-label fw-semibold">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="2" placeholder="Enter address"><?php echo htmlspecialchars($client['address']); ?></textarea>
                      </div>

                      


                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-5">
                      <a href="allclient.php" class="btn btn-outline-secondary px-4 py-2">Cancel</a>
                      <?php if ($isEdit): ?>
                        <button type="submit" name="update_client" class="btn btn-primary px-4 py-2">
                          <i class="bi bi-check-lg me-1"></i> Update Client
                        </button>
                      <?php else: ?>
                        <button type="submit" name="add_client" class="btn btn-primary px-4 py-2">
                          <i class="bi bi-check-lg me-1"></i> Save Client
                        </button>
                      <?php endif; ?>
                    </div>
                  </form>
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
  </body>
</html>
