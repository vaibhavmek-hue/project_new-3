<?php
require_once 'common/auth_check.php';
include 'db.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$preselected_client_id = $id; // pre-select this client in the Add Project modal below
require_once 'common/add_project_handler.php';
$client = [
    'client_name' => 'Client', 'organization_name' => '', 'contact_person' => '',
    'email' => '', 'mobile' => '', 'status' => 'Active',
    'address' => '', 'designation' => '', 'state' => '', 'pin_code' => ''
];
if ($id > 0) {
    $res = $conn->query("SELECT * FROM clients WHERE id = $id");
    if ($res && $res->num_rows > 0) {
        $client = $res->fetch_assoc();
    }
}

// Reports for all of this client's projects. Joined through
// projects.client_id (real FK) now, with a fallback to the legacy
// client_name text match for any project not yet linked.
$cname_esc = $conn->real_escape_string($client['client_name']);
$reports_result = $conn->query(
    "SELECT pr.*, p.project_name FROM project_reports pr
     INNER JOIN projects p ON p.id = pr.project_id
     WHERE p.client_id = $id OR (p.client_id IS NULL AND p.client_name = '$cname_esc')
     ORDER BY pr.created_at DESC"
);
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

          
  <!-- Content wrapper --><!-- Content wrapper --><div class="container-fluid px-3 px-md-4 py-4">


    <!-- Page Heading -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <h2 class="fw-bold mb-0">
            Client Details
        </h2>

        <a href="allclient.php" class="btn btn-outline-secondary">

            <i class="bi bi-arrow-left me-1"></i>

            Back

        </a>

    </div>



    <!-- ================= CLIENT CARD ================= -->

    <div class="card border-0 shadow-sm rounded-4 mb-4">

        <div class="card-body p-3 p-md-4">


            <!-- Company + Status -->

            <div class="d-flex justify-content-between align-items-start flex-nowrap">


                <!-- Company -->

                <div class="d-flex align-items-center">

                    <div
                        class="bg-primary bg-opacity-10 rounded-3 d-flex align-items-center justify-content-center me-3 flex-shrink-0"
                        style="width:55px;height:55px;">

                        <i class="bi bi-folder-fill text-primary fs-3"></i>

                    </div>


                    <div>

                        <h4 class="fw-bold mb-1">
                            <?php echo htmlspecialchars($client['organization_name'] ?: $client['client_name']); ?>
                        </h4>

                        <p class="text-secondary mb-0 small">

                            Contact: <?php echo htmlspecialchars($client['contact_person']); ?>

                            <span class="mx-2">|</span>

                            <?php echo htmlspecialchars($client['email']); ?>

                        </p>

                    </div>

                </div>


                <!-- Status -->

                <?php if ($client['status'] === 'Active'): ?>
                <span
                    class="badge rounded-pill bg-success bg-opacity-10 text-success text-white
                     px-3 py-2 flex-shrink-0">

                    <i class="bi bi-circle-fill small me-1"></i>

                    Active

                </span>
                <?php else: ?>
                <span
                    class="badge rounded-pill bg-danger bg-opacity-10 text-white px-3 py-2 flex-shrink-0">

                    <i class="bi bi-circle-fill small me-1"></i>

                    Inactive

                </span>
                <?php endif; ?>

            </div>



            <hr class="my-4">



            <!-- Client Details (all fields) -->

            <div class="row g-4">

                <div class="col-6 col-md-3">
                    <small class="text-secondary">Client Name</small>
                    <div class="fw-semibold"><?php echo htmlspecialchars($client['client_name']); ?></div>
                </div>

                <div class="col-6 col-md-3">
                    <small class="text-secondary">Organization</small>
                    <div class="fw-semibold"><?php echo $client['organization_name'] ? htmlspecialchars($client['organization_name']) : '&mdash;'; ?></div>
                </div>

                

             

                <div class="col-6 col-md-3">
                    <small class="text-secondary">Email</small>
                    <div class="fw-semibold"><?php echo $client['email'] ? htmlspecialchars($client['email']) : '&mdash;'; ?></div>
                </div>

                <div class="col-6 col-md-3">
                    <small class="text-secondary">Mobile</small>
                    <div class="fw-semibold"><?php echo $client['mobile'] ? htmlspecialchars($client['mobile']) : '&mdash;'; ?></div>
                </div>

                

                <div class="col-12 col-md-6">
                    <small class="text-secondary">Address</small>
                    <div class="fw-semibold"><?php echo $client['address'] ? htmlspecialchars($client['address']) : '&mdash;'; ?></div>
                </div>

            </div>

        </div>

    </div>



    <!-- ================= BOOTSTRAP TABS ================= -->

    <ul class="nav nav-tabs mb-4" id="clientTabs" role="tablist">


        <!-- Projects -->

        <li class="nav-item" role="presentation">

            <button
                class="nav-link active fw-semibold"
                id="projects-tab"
                data-bs-toggle="tab"
                data-bs-target="#projects"
                type="button"
                role="tab"
                aria-controls="projects"
                aria-selected="true">

                Projects

            </button>

        </li>


       


        <!-- Information -->

        <li class="nav-item" role="presentation">

            <button
                class="nav-link fw-semibold"
                id="information-tab"
                data-bs-toggle="tab"
                data-bs-target="#information"
                type="button"
                role="tab"
                aria-controls="information"
                aria-selected="false">

                Information

            </button>

        </li>

    </ul>



    <!-- ================= TAB CONTENT ================= -->

    <div class="tab-content" id="clientTabsContent">


        <!-- ================================================= -->
        <!-- PROJECTS -->
        <!-- ================================================= -->

        <div
            class="tab-pane fade show active"
            id="projects"
            role="tabpanel"
            aria-labelledby="projects-tab"
            tabindex="0">

            <div class="card border-0 shadow-sm rounded-4">

                <div class="card-body p-3 p-md-4">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold m-0">
                            Client Projects
                        </h5>
                        <a href="#" data-bs-toggle="modal" data-bs-target="#addProjectModal" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg"></i> Add Project for this Client
                        </a>
                    </div>


                    <div class="table-responsive">

                        <table class="table align-middle">

                            <thead>

                                <tr>

                                    <th>
                                        Sr No
                                    </th>

                                    <th>
                                        Project Name
                                    </th>

                                    <th>
                                        Status
                                    </th>

                                    <th>
                                        Progress
                                    </th>

                                    <th class="text-center">
                                        Action
                                    </th>

                                </tr>

                            </thead>


                            <tbody>

                                <?php
                                // Linked via client_id (real FK) with a fallback to the old
                                // name-text match for any project not yet connected.
                                $proj_result = $conn->query(
                                    "SELECT * FROM projects
                                     WHERE client_id = $id OR (client_id IS NULL AND client_name = '" . $conn->real_escape_string($client['client_name']) . "')
                                     ORDER BY created_at DESC"
                                );
                                $proj_sr_no = 1;
                                if ($proj_result && $proj_result->num_rows > 0):
                                    while ($p = $proj_result->fetch_assoc()):
                                        $pstatus = $p['status'];
                                        $pbadge = ($pstatus === 'Completed') ? 'bg-success text-white' : (($pstatus === 'On Hold') ? 'bg-warning' : 'bg-primary');
                                        $pbar = ($pstatus === 'Completed') ? 'bg-success' : 'bg-primary';
                                        $pprogress = (int)$p['progress'];
                                ?>
                                <tr>

                                    <td>
                                        <?php echo $proj_sr_no++; ?>
                                    </td>

                                    <td class="fw-semibold">
                                        <?php echo htmlspecialchars($p['project_name']); ?>
                                    </td>


                                    <td>

                                        <span
                                            class="badge  <?php echo $pbadge; ?> bg-opacity-10 text-<?php echo str_replace('bg-','',$pbadge); ?> rounded-pill px-3 py-2">

                                            <i class="bi bi-circle-fill small me-1 "></i>

                                            <?php echo htmlspecialchars($pstatus); ?>

                                        </span>

                                    </td>


                                    <td>

                                        <div class="d-flex align-items-center gap-2">

                                            <div
                                                class="progress"
                                                style="width:100px;height:8px;">

                                                <div
                                                    class="progress-bar <?php echo $pbar; ?>"
                                                    style="width:<?php echo $pprogress; ?>%;">
                                                </div>

                                            </div>

                                            <small>
                                                <?php echo $pprogress; ?>%
                                            </small>

                                        </div>

                                    </td>


                                    <td class="text-center">

                                        <a href="projectdetail.php?id=<?php echo $p['id']; ?>"
                                            class="btn btn-light border rounded-3 p-2 bg-primary">

                                            <i class="fa-solid fa-eye "></i>

                                        </a>

                                    </td>

                                </tr>
                                <?php
                                    endwhile;
                                else:
                                ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">No projects found for this client.</td>
                                </tr>
                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>



        <!-- ================================================= -->
        <!-- REPORTS -->
        <!-- ================================================= -->

        <div
            class="tab-pane fade"
            id="reports"
            role="tabpanel"
            aria-labelledby="reports-tab"
            tabindex="0">

            <div class="card border-0 shadow-sm rounded-4">

                <div class="card-body p-3 p-md-4">

                    <h5 class="fw-bold mb-4">
                        Client Reports
                    </h5>


                    <div class="table-responsive">

                        <table class="table align-middle">

                            <thead>

                                <tr>

                                    <th>
                                        Report Name
                                    </th>

                                    <th>
                                        Type
                                    </th>

                                    <th>
                                        Date
                                    </th>

                                  

                                </tr>

                            </thead>


                            <tbody>


                                <tr>

                                    <td class="fw-semibold ">
                                        Monthly Sales Report
                                    </td>

                                    <td>

                                        <span
                                            class="badge bg-info bg-opacity-10 text-info rounded-pill px-3 py-2 text-white">

                                            <i class="bi bi-circle-fill small me-1"></i>

                                            Financial

                                        </span>

                                    </td>

                                    <td>
                                        01 Aug 2026
                                    </td>

                                    

                                </tr>



                                <tr>

                                    <td class="fw-semibold">
                                        Project Progress Summary
                                    </td>

                                    <td>

                                        <span
                                            class="badge bg-primary bg-opacity-10 text-white rounded-pill px-3 py-2">

                                            <i class="bi bi-circle-fill small me-1"></i>

                                            Operational

                                        </span>

                                    </td>

                                    <td>
                                        05 Aug 2026
                                    </td>


                                </tr>



                                <tr>

                                    <td class="fw-semibold">
                                        Client Feedback Report
                                    </td>

                                    <td>

                                        <span
                                            class="badge bg-success bg-opacity-10 text-success text-white
                                             rounded-pill px-3 py-2">

                                            <i class="bi bi-circle-fill small me-1"></i>

                                            Feedback

                                        </span>

                                    </td>

                                    <td>
                                        10 Aug 2026
                                    </td>

                                   
                                </tr>


                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>



        <!-- ================================================= -->
        <!-- INFORMATION -->
        <!-- ================================================= -->

        <div
            class="tab-pane fade"
            id="information"
            role="tabpanel"
            aria-labelledby="information-tab"
            tabindex="0">

            <div class="card border-0 shadow-sm rounded-4">

                <div class="card-body p-3 p-md-4">

                    <h5 class="fw-bold mb-4">
                        Additional Information
                    </h5>


                    <div class="table-responsive">

                        <table class="table align-middle">

                            <tbody>

                                <tr>
                                    <td class="fw-semibold" style="width:220px;">Client Name</td>
                                    <td><?php echo htmlspecialchars($client['client_name']); ?></td>
                                </tr>

                                <tr>
                                    <td class="fw-semibold">Organization</td>
                                    <td><?php echo $client['organization_name'] ? htmlspecialchars($client['organization_name']) : '&mdash;'; ?></td>
                                </tr>

                              

                                <tr>
                                    <td class="fw-semibold">Email</td>
                                    <td><?php echo $client['email'] ? htmlspecialchars($client['email']) : '&mdash;'; ?></td>
                                </tr>

                                <tr>
                                    <td class="fw-semibold">Mobile</td>
                                    <td><?php echo $client['mobile'] ? htmlspecialchars($client['mobile']) : '&mdash;'; ?></td>
                                </tr>

                                <tr>
                                    <td class="fw-semibold">Address</td>
                                    <td><?php echo $client['address'] ? htmlspecialchars($client['address']) : '&mdash;'; ?></td>
                                </tr>

                                
                                <tr>
                                    <td class="fw-semibold">Status</td>
                                    <td><?php echo htmlspecialchars($client['status']); ?></td>
                                </tr>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>


    </div>

</div>   <!-- Content wrapper -->  <!-- Content wrapper -->

          


          
            <?php include 'common/footer.php'; ?>

  <?php include 'common/add_project_modal.php'; ?>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      var params = new URLSearchParams(window.location.search);
      if (params.get('added') === '1') {
        Swal.fire({
          title: 'Added!',
          text: 'Project added successfully.',
          icon: 'success',
          timer: 2000,
          showConfirmButton: false
        });
        params.delete('added');
        var newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.history.replaceState({}, document.title, newUrl);
      }
    });
  </script>
        </div>
        <!-- / Layout page -->
      </div>
    </div>
    <!-- / Layout wrapper -->
  </body>
</html>
