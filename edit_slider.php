<?php
require_once 'common/auth_check.php';
include 'db.php';
$error = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id           = (int)($_POST['id'] ?? 0);
    $project_name = $conn->real_escape_string(trim($_POST['project_name'] ?? ''));
    $client_id    = (int) ($_POST['client_id'] ?? 0);
    $client_name  = '';
    // Client name always follows the linked client record, so the
    // connection stays live everywhere client_name is displayed.
    if ($client_id > 0) {
        $cres = $conn->query("SELECT client_name FROM clients WHERE id = " . $client_id);
        if ($cres && $cres->num_rows > 0) {
            $client_name = $conn->real_escape_string($cres->fetch_assoc()['client_name']);
        }
    }
    $email        = $conn->real_escape_string(trim($_POST['email'] ?? ''));
    $phone        = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
    $num_users    = (int)($_POST['num_users'] ?? 0);
    $start_date   = $conn->real_escape_string(trim($_POST['start_date'] ?? ''));
    $end_date     = $conn->real_escape_string(trim($_POST['end_date'] ?? ''));
    $status       = $conn->real_escape_string(trim($_POST['status'] ?? 'In Progress'));
    $progress     = (int)($_POST['progress'] ?? 0);
    $description  = $conn->real_escape_string(trim($_POST['description'] ?? ''));

    if ($project_name === '' || $id <= 0) {
        $error = "Project Name is required.";
    } elseif ($phone !== '' && !preg_match('/^[0-9]{10}$/', $phone)) {
        $error = "Phone Number must be exactly 10 digits, numbers only.";
    } else {
        $start_date_val = $start_date !== '' ? "'$start_date'" : "NULL";
        $end_date_val = $end_date !== '' ? "'$end_date'" : "NULL";
        $client_id_val = $client_id > 0 ? $client_id : "NULL";
        $sql = "UPDATE projects SET
                    project_name = '$project_name',
                    client_id    = $client_id_val,
                    client_name  = '$client_name',
                    email        = '$email',
                    phone        = '$phone',
                    num_users    = $num_users,
                    start_date   = $start_date_val,
                    end_date     = $end_date_val,
                    status       = '$status',
                    progress     = $progress,
                    description  = '$description'
                WHERE id = $id";
        if ($conn->query($sql) === TRUE) {
            header("Location: allprojectdashboard.php?updated=1");
            exit;
        } else {
            $error = "Error: " . $conn->error;
        }
    }
}

// Load existing project data
$project = [
    'id' => $id, 'project_name' => '', 'client_id' => null, 'client_name' => '', 'email' => '',
    'phone' => '', 'num_users' => '',
    'start_date' => '', 'end_date' => '', 'status' => 'In Progress', 'progress' => 0, 'description' => ''
];
if ($id > 0) {
    $res = $conn->query("SELECT * FROM projects WHERE id = $id");
    if ($res && $res->num_rows > 0) {
        $project = $res->fetch_assoc();
    }
}

// Clients for the dropdown
$clients_list = $conn->query("SELECT id, client_name, organization_name FROM clients ORDER BY client_name ASC");
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

    <div class="content-wrapper">
        <div class="container-xxl flex-grow-1 container-p-y">

            <!-- Edit Project Form -->
            <div class="card">
                <div class="card-header">
                    <h3 class="text-dark card-header">Edit Project</h3>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <input type="hidden" name="id" value="<?php echo (int)$project['id']; ?>">
                        <div class="row">
                            <!-- Field 1 -->
                            <div class="col-lg-6 mb-3">
                                <div class="form-floating">
                                    <input type="text" name="project_name" class="form-control" id="floatingProjectName" placeholder="Project Name" value="<?php echo htmlspecialchars($project['project_name']); ?>" required>
                                    <label for="floatingProjectName">Project Name</label>
                                </div>
                            </div>

                            <!-- Field 2 -->
                            <div class="col-lg-6 mb-3">
                                <div class="form-floating">
                                    <select name="client_id" class="form-select" id="floatingClientId">
                                        <option value="">-- Select a client (optional) --</option>
                                        <?php if ($clients_list): while ($c = $clients_list->fetch_assoc()): ?>
                                            <option value="<?php echo (int) $c['id']; ?>" <?php echo ((int)$project['client_id'] === (int)$c['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($c['client_name']); ?><?php echo $c['organization_name'] ? ' — ' . htmlspecialchars($c['organization_name']) : ''; ?>
                                            </option>
                                        <?php endwhile; endif; ?>
                                    </select>
                                    <label for="floatingClientId">Client</label>
                                </div>
                                <?php if (empty($project['client_id']) && !empty($project['client_name'])): ?>
                                <div class="form-text text-warning">
                                    Currently unlinked - stored client name: "<?php echo htmlspecialchars($project['client_name']); ?>". Pick the matching client above to connect it.
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- Field 3 -->
                            <div class="col-lg-6 mb-3">
                                <div class="form-floating">
                                    <input type="email" name="email" class="form-control" id="floatingEmail" placeholder="Email" value="<?php echo htmlspecialchars($project['email']); ?>">
                                    <label for="floatingEmail">Email Address</label>
                                </div>
                            </div>

                            <!-- Field 4 -->
                            <div class="col-lg-6 mb-3">
                                <div class="form-floating">
                                    <input type="tel" name="phone" class="form-control" id="floatingPhone" placeholder="Phone" value="<?php echo htmlspecialchars($project['phone']); ?>" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" title="Enter exactly 10 digits" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)">
                                    <label for="floatingPhone">Phone Number</label>
                                </div>
                            </div>

                            <!-- Field 5 -->
                            <div class="col-lg-6 mb-3">
                                <div class="form-floating">
                                    <input type="number" name="num_users" class="form-control" id="floatingUsers" placeholder="Number of Users" value="<?php echo htmlspecialchars($project['num_users']); ?>">
                                    <label for="floatingUsers">Number of Users</label>
                                </div>
                            </div>

                            <!-- Field 6 -->
                            <div class="col-lg-6 mb-3">
                                <div class="form-floating">
                                    <input type="date" name="start_date" class="form-control" id="floatingDate" placeholder="Start Date" value="<?php echo htmlspecialchars($project['start_date']); ?>">
                                    <label for="floatingDate">Start Date</label>
                                </div>
                            </div>

                            <!-- Field 7 -->
                            <div class="col-lg-6 mb-3">
                                <div class="form-floating">
                                    <input type="date" name="end_date" class="form-control" id="floatingEndDate" placeholder="End Date" value="<?php echo htmlspecialchars($project['end_date']); ?>">
                                    <label for="floatingEndDate">End Date</label>
                                </div>
                            </div>

                            <!-- Field 8 -->
                            <div class="col-lg-6 mb-3">
                                <div class="form-floating">
                                    <select name="status" class="form-control" id="floatingStatus">
                                        <?php foreach (['In Progress','Completed','On Hold'] as $opt): ?>
                                            <option value="<?php echo $opt; ?>" <?php echo ($project['status'] === $opt) ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label for="floatingStatus">Status</label>
                                </div>
                            </div>

                            <!-- Field 9 -->
                            
                            <!-- Field 10 -->
                            <div class="col-lg-12 mb-3">
                                <div class="form-floating">
                                    <textarea name="description" class="form-control" id="floatingDescription" placeholder="Description" style="height: 80px;"><?php echo htmlspecialchars($project['description']); ?></textarea>
                                    <label for="floatingDescription">Description</label>
                                </div>
                            </div>
                        </div>

                        <!-- Buttons -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bx bx-check me-1"></i> Update
                                </button>
                                <a href="allprojectdashboard.php" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!--/ Edit Project Form -->
        </div>
    </div>

          
            <?php include 'common/footer.php'; ?>
        </div>
        <!-- / Layout page -->
      </div>
    </div>
    <!-- / Layout wrapper -->
  </body>
</html>
