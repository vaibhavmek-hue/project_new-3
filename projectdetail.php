<?php
require_once 'common/auth_check.php';
include 'db.php';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$project = [
    'project_name' => 'Project', 'client_name' => '', 'status' => 'In Progress',
    'email' => '', 'phone' => '', 'num_users' => 0,
    'start_date' => '', 'end_date' => '', 'progress' => 0, 'description' => ''
];
if ($id > 0) {
    $res = $conn->query("SELECT * FROM projects WHERE id = $id");
    if ($res && $res->num_rows > 0) {
        $project = $res->fetch_assoc();
    }
}
$statusBadge = ($project['status'] === 'Completed') ? 'bg-success-subtle text-success' : (($project['status'] === 'On Hold') ? 'bg-warning-subtle text-warning' : 'bg-primary-subtle text-primary');

// Client contact info - linked via client_id when available (real FK
// relation), falling back to the old client_name text match for any
// project that hasn't been connected to a client record yet.
$client = null;
if (!empty($project['client_id'])) {
    $cid = (int) $project['client_id'];
    $cres = $conn->query("SELECT * FROM clients WHERE id = $cid LIMIT 1");
    if ($cres && $cres->num_rows > 0) {
        $client = $cres->fetch_assoc();
    }
} elseif (!empty($project['client_name'])) {
    $cname = $conn->real_escape_string($project['client_name']);
    $cres = $conn->query("SELECT * FROM clients WHERE client_name = '$cname' LIMIT 1");
    if ($cres && $cres->num_rows > 0) {
        $client = $cres->fetch_assoc();
    }
}

// Technology stack for THIS project only (connected via technologies.project_id)
$tech_result = null;
if ($id > 0) {
    $tech_result = $conn->query("SELECT * FROM technologies WHERE project_id = $id ORDER BY id ASC");
}

// App screens / dashboard pages / website pages linked to this project
$app_work_result = $dashboard_work_result = $website_work_result = null;
if ($id > 0) {
    $app_work_result       = $conn->query("SELECT * FROM app_work WHERE project_id = $id ORDER BY id ASC");
    $dashboard_work_result = $conn->query("SELECT * FROM dashboard_work WHERE project_id = $id ORDER BY id ASC");
    $website_work_result   = $conn->query("SELECT * FROM website_work WHERE project_id = $id ORDER BY id ASC");
}

// Reports for this project
$reports_result = null;
if ($id > 0) {
   $reports_result = $conn->query("SELECT * FROM project_reports WHERE project_id = $id ORDER BY id DESC");
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
   <div class="container py-4">
    
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="fw-bold mb-0 text-dark">Project Details</h2>
            <p class="small mb-0 fs-5 text-primary">View project information</p>
        </div>
        <a href="allprojectdashboard.php" class="btn btn-white bg-white border text-dark fw-medium shadow-sm px-3 py-2 rounded-3">
            <i class="bi bi-arrow-left me-1"></i> Back </a>
    </div>

    <!-- Main Info Card -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start mb-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; font-size: 1.5rem;">
                        <i class="bi bi-folder-fill"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars($project['project_name']); ?></h4>
                        <div class="text-primary small">
                            Client: <span class="fw-semibold text-dark"><?php echo htmlspecialchars($project['client_name']); ?></span>
                        </div>
                    </div>
                </div>
                <span class="badge <?php echo $statusBadge; ?> px-3 py-2 rounded-pill fw-medium d-flex align-items-center gap-1">
                    <i class="bi bi-circle-fill"></i> <?php echo htmlspecialchars($project['status']); ?>
                </span>
            </div>

            <div class="row pt-3 border-top g-3">
                <div class="col-md-4 border-end">
                    <div class="text-primary small mb-1">Start Date</div>
                    <div class="fw-bold text-dark fs-5"><?php echo $project['start_date'] ? date('M d, Y', strtotime($project['start_date'])) : '-'; ?></div>
                </div>
                <div class="col-md-4 border-end">
                    <div class="text-primary small mb-1">End Date</div>
                    <div class="fw-bold text-dark fs-5"><?php echo $project['end_date'] ? date('M d, Y', strtotime($project['end_date'])) : '-'; ?></div>
                </div>
                <div class="col-md-4">
                    <div class="text-primary small mb-1">Team Size  </div>
                    <div class="fw-bold text-dark fs-5"><?php echo isset($project['num_users']) ? (int)$project['num_users'] : 0; ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="bg-white rounded-3 shadow-sm p-1 mb-4">
        <ul class="nav nav-tabs border-0 custom-tabs" id="projectTab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active border-0 fw-medium px-4 py-2" data-bs-toggle="tab" data-bs-target="#overview"><span class="text-primary">Overview</span></button>
            </li>
            <li class="nav-item">
                <button class="nav-link border-0 text-muted fw-medium px-4 py-2" data-bs-toggle="tab" data-bs-target="#team"><span class="text-primary">Team</span></button>
            </li>
            <li class="nav-item">
                <button class="nav-link border-0 text-muted fw-medium px-4 py-2" data-bs-toggle="tab" data-bs-target="#work"><span class="text-primary">Work Details</span></button>
            </li>
            <li class="nav-item">
                <button class="nav-link border-0 text-muted fw-medium px-4 py-2" data-bs-toggle="tab" data-bs-target="#tech"><span class="text-primary">Technology</span></button>
            </li>
            <li class="nav-item">
                <button class="nav-link border-0 text-muted fw-medium px-4 py-2" data-bs-toggle="tab" data-bs-target="#reports"><span class="text-primary">Reports</span></button>
            </li>
        </ul>
    </div>
                                                                
    <!-- Tab Contents -->
    <div class="tab-content" id="projectTabContent">
        <div class="tab-pane fade show active" id="overview">
            <div class="row g-4">
                <!-- Project Overview -->
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 p-4 h-100">
                        <h5 class="fw-bold text-dark mb-3">Project Overview</h5>
                        <h6 class="text-primary fw-semibold mb-2">Project Description</h6>
                        <p class="text-muted mb-0"><?php echo $project['description'] ? nl2br(htmlspecialchars($project['description'])) : 'No description provided.'; ?></p>
                    </div>
                </div>

                <!-- Project Summary -->
                 <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 p-4 h-100">
                        <h5 class="fw-bold text-dark mb-3">Project Summary</h5>
                        <!-- Hover added: Converted simple rows into interactive list-group items for modern row highlighters -->
                        <div class="list-group list-group-flush rounded-3">
                            <div class="list-group-item list-group-item-action d-flex justify-content-between py-2 px-2 border-bottom border-0">
                                <span class="text-primary">Client</span>
                                <span class="fw-bold text-dark"><?php echo htmlspecialchars($project['client_name'] ?: '-'); ?></span>
                            </div>
                            <div class="list-group-item list-group-item-action d-flex justify-content-between py-2 px-2 border-bottom border-0">
                                <span class="text-primary">Email</span>
                                <span class="fw-bold text-dark"><?php echo htmlspecialchars($project['email'] ?: '-'); ?></span>
                            </div>
                            <div class="list-group-item list-group-item-action d-flex justify-content-between py-2 px-2 border-bottom border-0">
                                <span class="text-primary">Phone</span>
                                <span class="fw-bold text-dark"><?php echo htmlspecialchars($project['phone'] ?: '-'); ?></span>
                            </div>
                            <div class="list-group-item list-group-item-action d-flex justify-content-between py-2 px-2 border-0">
                                <span class="text-primary">End Date</span>
                                <span class="fw-bold text-dark"><?php echo $project['end_date'] ? date('M d, Y', strtotime($project['end_date'])) : '-'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Team Tab -->
        <div class="tab-pane fade" id="team">
            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 p-4 h-100">
                        <h5 class="fw-bold text-dark mb-3">Client Contact</h5>
                        <?php if ($client): ?>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; font-size: 1.2rem;">
                                <i class="bi bi-person-fill"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($client['contact_person'] ?: '-'); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($client['designation'] ?: ''); ?></small>
                            </div>
                        </div>
                        <div class="list-group list-group-flush rounded-3">
                            <div class="list-group-item d-flex justify-content-between py-2 px-2 border-bottom border-0">
                                <span class="text-primary">Organization</span>
                                <span class="fw-bold text-dark"><?php echo htmlspecialchars($client['organization_name'] ?: '-'); ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between py-2 px-2 border-bottom border-0">
                                <span class="text-primary">Email</span>
                                <span class="fw-bold text-dark"><?php echo htmlspecialchars($client['email'] ?: '-'); ?></span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between py-2 px-2 border-0">
                                <span class="text-primary">Mobile</span>
                                <span class="fw-bold text-dark"><?php echo htmlspecialchars($client['mobile'] ?: '-'); ?></span>
                            </div>
                        </div>
                        <?php else: ?>
                        <p class="text-muted mb-0">No matching client record found for "<?php echo htmlspecialchars($project['client_name']); ?>".</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3 p-4 h-100">
                        <h5 class="fw-bold text-dark mb-3">Team Size</h5>
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; font-size: 1.5rem;">
                                <i class="bi bi-people-fill"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0 text-dark"><?php echo (int)$project['num_users']; ?></h3>
                                <small class="text-muted">Number of Users</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Work Details Tab -->
        <div class="tab-pane fade" id="work">
            <div class="card border-0 shadow-sm rounded-3 p-4">
                <h5 class="fw-bold text-dark mb-3">Work Details</h5>
                <div class="list-group list-group-flush rounded-3">
                    <div class="list-group-item d-flex justify-content-between py-2 px-2 border-bottom border-0">
                        <span class="text-primary">Status</span>
                        <span class="badge <?php echo $statusBadge; ?> px-3 py-1 rounded-pill fw-medium"><?php echo htmlspecialchars($project['status']); ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between py-2 px-2 border-bottom border-0">
                        <span class="text-primary">Start Date</span>
                        <span class="fw-bold text-dark"><?php echo $project['start_date'] ? date('M d, Y', strtotime($project['start_date'])) : '-'; ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between py-2 px-2 border-0">
                        <span class="text-primary">End Date</span>
                        <span class="fw-bold text-dark"><?php echo $project['end_date'] ? date('M d, Y', strtotime($project['end_date'])) : '-'; ?></span>
                    </div>
                </div>
            </div>

            <!-- App Screens linked to this project -->
            <div class="card border-0 shadow-sm rounded-3 p-4 mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark m-0">App Screens</h5>
                    <a href="appwork.php?project_id=<?php echo (int) $id; ?>" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg"></i> Add / View in App Work
                    </a>
                </div>
                <?php if ($app_work_result && $app_work_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Sr No</th><th>Screen Name</th><th>Status</th><th>Created</th><th>End Date</th></tr></thead>
                        <tbody>
                        <?php $aw_sr_no = 1; while ($aw = $app_work_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $aw_sr_no++; ?></td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($aw['screen_name']); ?></td>
                                <td><span class="badge bg-success-subtle text-success"><?php echo htmlspecialchars($aw['status']); ?></span></td>
                                <td class="text-muted"><?php echo $aw['created_date'] ? date('M d, Y', strtotime($aw['created_date'])) : '-'; ?></td>
                                <td class="text-muted"><?php echo !empty($aw['end_date']) ? date('M d, Y', strtotime($aw['end_date'])) : '-'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No app screens linked to this project yet.</p>
                <?php endif; ?>
            </div>

            <!-- Dashboard Pages linked to this project -->
            <div class="card border-0 shadow-sm rounded-3 p-4 mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark m-0">Dashboard Pages</h5>
                    <a href="dashboardwork.php?project_id=<?php echo (int) $id; ?>" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg"></i> Add Dashboard Page
                    </a>
                </div>
                <?php if ($dashboard_work_result && $dashboard_work_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Sr No</th><th>Page Name</th><th>Status</th><th>Created</th><th>End Date</th></tr></thead>
                        <tbody>
                        <?php $dw_sr_no = 1; while ($dw = $dashboard_work_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $dw_sr_no++; ?></td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($dw['page_name']); ?></td>
                                <td><span class="badge bg-success-subtle text-success"><?php echo htmlspecialchars($dw['status']); ?></span></td>
                                <td class="text-muted"><?php echo $dw['created_date'] ? date('M d, Y', strtotime($dw['created_date'])) : '-'; ?></td>
                                <td class="text-muted"><?php echo !empty($dw['end_date']) ? date('M d, Y', strtotime($dw['end_date'])) : '-'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No dashboard pages linked to this project yet.</p>
                <?php endif; ?>
            </div>

            <!-- Website Pages linked to this project -->
            <div class="card border-0 shadow-sm rounded-3 p-4 mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark m-0">Website Pages</h5>
                    <a href="websitework.php?project_id=<?php echo (int) $id; ?>" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg"></i> Add / View in Website Work
                    </a>
                </div>
                <?php if ($website_work_result && $website_work_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Sr No</th><th>Page Name</th><th>Status</th><th>Created</th><th>End Date</th></tr></thead>
                        <tbody>
                        <?php $ww_sr_no = 1; while ($ww = $website_work_result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $ww_sr_no++; ?></td>
                                <td class="fw-semibold"><?php echo htmlspecialchars($ww['page_name']); ?></td>
                                <td><span class="badge bg-success-subtle text-success"><?php echo htmlspecialchars($ww['status']); ?></span></td>
                                <td class="text-muted"><?php echo $ww['created_date'] ? date('M d, Y', strtotime($ww['created_date'])) : '-'; ?></td>
                                <td class="text-muted"><?php echo !empty($ww['end_date']) ? date('M d, Y', strtotime($ww['end_date'])) : '-'; ?></td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No website pages linked to this project yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Technology Tab -->
        <div class="tab-pane fade" id="tech">
            <div class="card border-0 shadow-sm rounded-3 p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark m-0">Technology Stack</h5>
                    <a href="tech.php?project_id=<?php echo (int) $id; ?>" class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-lg"></i> Add / View in Technology
                    </a>
                </div>
                <div class="row g-3">
                    <?php if ($tech_result && $tech_result->num_rows > 0): ?>
                        <?php while ($t = $tech_result->fetch_assoc()): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="border rounded-3 p-3 h-100">
                                <small class="text-primary d-block mb-1"><?php echo htmlspecialchars($t['tech_type']); ?></small>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($t['name']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($t['version']); ?></small>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0">No technologies linked to this project yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Reports Tab -->
        <div class="tab-pane fade" id="reports">
            <div class="card border-0 shadow-sm rounded-3 p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold text-dark m-0">Project Reports</h5>
                    <div class="d-flex gap-2">
                        <a href="reportsections.php?project_id=<?php echo (int)$id; ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-list-check"></i> Manage Report Details
                        </a>
                        <a href="download_report.php?project_id=<?php echo (int)$id; ?>&view=1" class="btn btn-outline-secondary btn-sm" target="_blank">
                            <i class="bi bi-eye"></i> View
                        </a>
                        <a href="download_report.php?project_id=<?php echo (int)$id; ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-file-earmark-pdf"></i> Download Full Project Report
                        </a>
                       
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
    <thead>
        <tr>
            <th>Sr No</th>
            <th>Report Name</th>
            <th>Type</th>
            <th>Date</th>
            <th class="text-end">Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($reports_result && $reports_result->num_rows > 0): ?>
            <?php $r_sr_no = 1; while ($r = $reports_result->fetch_assoc()): ?>
            <tr>
                <td><?php echo $r_sr_no++; ?></td>
                <td class="fw-semibold"><?php echo htmlspecialchars($r['report_name']); ?></td>
                <td class="text-muted"><?php echo htmlspecialchars($r['report_type']); ?></td>
                <td class="text-muted"><?php echo $r['report_date'] ? date('M d, Y', strtotime($r['report_date'])) : '-'; ?></td>
                <td class="text-end">
    <a href="download_report.php?report_id=<?php echo (int)$r['id']; ?>&view=1"
       class="btn btn-sm btn-outline-secondary me-1" title="View"
       onclick="event.stopPropagation(); window.open(this.href, '_blank'); return false;">
        <i class="fa-solid fa-eye"></i>
    </a>
    <a href="edit_report.php?id=<?php echo (int)$r['id']; ?>"
       class="btn btn-sm btn-outline-secondary me-1" title="Edit"
       onclick="event.stopPropagation(); window.location.href = this.href; return false;">
        <i class="fa-solid fa-pencil"></i>
    </a>
    <a href="download_report.php?report_id=<?php echo (int)$r['id']; ?>"
       class="btn btn-sm btn-outline-secondary" title="Download as PDF"
       onclick="event.stopPropagation(); window.location.href = this.href; return false;">
        <i class="bi bi-file-earmark-pdf"></i> Download
    </a>
</td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="4" class="text-center py-4 text-muted">No reports yet for this project.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
                </div>
            </div>
        </div>
</div>
          <!-- Content wrapper -->
</div>  


          
            <script>
              // Isolate each report action button so a single click can only
              // ever trigger the one action the user actually clicked (View,
              // Edit, or Download) — never more than one navigation at once.
              document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('.report-action-btn').forEach(function (btn) {
                  btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    e.stopImmediatePropagation();

                    var action = btn.getAttribute('data-action');
                    var href = btn.getAttribute('href');

                    e.preventDefault();

                    if (action === 'view') {
                      window.open(href, '_blank', 'noopener');
                    } else {
                      window.location.href = href;
                    }
                  }, true);
                });
              });
            </script>

            <?php include 'common/footer.php'; ?>
        </div>
        <!-- / Layout page -->
      </div>
    </div>
    <!-- / Layout wrapper -->
  </body>
</html>
