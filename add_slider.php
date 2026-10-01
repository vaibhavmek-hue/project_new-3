<?php
require_once 'common/auth_check.php';
include 'db.php';
$error = '';
$success = '';

// Allows "Add Project" links from a client's page (e.g. clientsdetail.php)
// to pre-select that client, so the new project is connected immediately.
$preselected_client_id = isset($_GET['client_id']) ? (int) $_GET['client_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ---- Fields the user actually fills in ----
    $project_name = trim($_POST['project_name'] ?? '');
    $client_id_raw = trim($_POST['client_id'] ?? '');
    $client_id    = ($client_id_raw !== '') ? (int) $client_id_raw : 0;
    // Projects.client_id is a nullable FK to clients.id. 0 is never a valid
    // client row, so it must be stored as NULL, not 0, or the FK constraint
    // (fk_projects_client) rejects the insert.
    $client_id_val = $client_id > 0 ? $client_id : null;
    $client_name  = '';
    // Client name is now derived from the linked client record (single
    // source of truth), not typed by hand, so every page that reads
    // projects.client_name automatically stays in sync with clients.
    if ($client_id > 0) {
        $cstmt = $conn->prepare("SELECT client_name FROM clients WHERE id = ?");
        $cstmt->bind_param('i', $client_id);
        $cstmt->execute();
        $cres = $cstmt->get_result();
        if ($cres && $cres->num_rows > 0) {
            $client_name = $cres->fetch_assoc()['client_name'];
        }
        $cstmt->close();
    }
    $email        = trim($_POST['email'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $num_users    = (int) ($_POST['num_users'] ?? 0);
    $start_date   = trim($_POST['start_date'] ?? '');
    $end_date     = trim($_POST['end_date'] ?? '');
    $description  = trim($_POST['description'] ?? '');

    // ---- Remaining table fields, auto-filled with sensible defaults ----
    // status: every new project starts as "In Progress" (matches the column's own default)
    $status = 'In Progress';
    // progress: brand-new project hasn't started any work yet
    $progress = 0;
    // start_date: if left blank, default it to today instead of storing NULL,
    // so every project has a usable date for the dashboard's monthly/trend charts
    if ($start_date === '') {
        $start_date = date('Y-m-d');
    }
    // end_date: optional - left NULL if not provided (project may still be ongoing)
    $end_date_val = $end_date !== '' ? $end_date : null;

    if ($project_name === '') {
        $error = "Project Name is required.";
    } elseif ($phone !== '' && !preg_match('/^[0-9]{10}$/', $phone)) {
        $error = "Phone Number must be exactly 10 digits, numbers only.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO projects
                (project_name, client_id, client_name, email, phone, num_users, start_date, end_date, description, status, progress)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'sisssissssi',
            $project_name,
            $client_id_val,
            $client_name,
            $email,
            $phone,
            $num_users,
            $start_date,
            $end_date_val,
            $description,
            $status,
            $progress
        );
        if ($stmt->execute()) {
            $new_project_id = $stmt->insert_id;
            $stmt->close();

            // ---- Save any Work rows added inline on this same form ----
            // Each work type is a set of parallel arrays (one entry per row
            // the user added on the page). Blank rows (no name) are skipped.
            $work_types = [
                'app'   => ['table' => 'app_work',       'name_field' => 'screen_name', 'prefix' => 'app_'],
                'dash'  => ['table' => 'dashboard_work',  'name_field' => 'page_name',   'prefix' => 'dash_'],
                'web'   => ['table' => 'website_work',    'name_field' => 'page_name',   'prefix' => 'web_'],
            ];

            foreach ($work_types as $type => $cfg) {
                $names = $_POST[$cfg['prefix'] . 'name'] ?? [];
                $statuses = $_POST[$cfg['prefix'] . 'status'] ?? [];
                $dates = $_POST[$cfg['prefix'] . 'created_date'] ?? [];
                $end_dates = $_POST[$cfg['prefix'] . 'end_date'] ?? [];

                foreach ($names as $i => $work_name) {
                    $work_name = trim($work_name);
                    if ($work_name === '') {
                        continue; // skip empty rows
                    }
                    $work_status = trim($statuses[$i] ?? 'Pending');
                    $work_date   = trim($dates[$i] ?? '');
                    $work_date   = $work_date !== '' ? $work_date : date('Y-m-d');
                    $work_end_date = trim($end_dates[$i] ?? '');
                    $work_end_date = $work_end_date !== '' ? $work_end_date : null;

                    $sql = "INSERT INTO {$cfg['table']} ({$cfg['name_field']}, status, created_date, end_date, project_id) VALUES (?, ?, ?, ?, ?)";
                    $wstmt = $conn->prepare($sql);
                    $wstmt->bind_param('ssssi', $work_name, $work_status, $work_date, $work_end_date, $new_project_id);
                    $wstmt->execute();
                    $wstmt->close();
                }
            }

            // ---- Save selected technologies (multi-select dropdown) ----
            $tech_category_map = [
                'JavaScript' => 'Language', 'PHP' => 'Language', 'Python' => 'Language',
                'Java' => 'Language', 'TypeScript' => 'Language', 'C#' => 'Language',
                'HTML' => 'Frontend', 'CSS' => 'Frontend', 'React' => 'Frontend',
                'Vue.js' => 'Frontend', 'Angular' => 'Frontend', 'Bootstrap' => 'Frontend',
                'Tailwind CSS' => 'Frontend', 'jQuery' => 'Frontend',
                'Node.js' => 'Backend', 'Laravel' => 'Backend', 'Django' => 'Backend',
                'Express.js' => 'Backend', '.NET' => 'Backend', 'Spring Boot' => 'Backend',
                'MySQL' => 'Database', 'PostgreSQL' => 'Database', 'MongoDB' => 'Database',
                'SQLite' => 'Database', 'Firebase' => 'Database',
                'Git' => 'DevOps & Tools', 'Docker' => 'DevOps & Tools', 'AWS' => 'DevOps & Tools',
                'Nginx' => 'DevOps & Tools', 'Linux' => 'DevOps & Tools', 'GitHub Actions' => 'DevOps & Tools',
            ];
            $selected_technologies = $_POST['technologies'] ?? [];
            if (is_array($selected_technologies) && count($selected_technologies) > 0) {
                $tstmt = $conn->prepare("INSERT INTO technologies (tech_type, name, version, project_id) VALUES (?, ?, '', ?)");
                foreach ($selected_technologies as $tech_name) {
                    $tech_name = trim($tech_name);
                    if ($tech_name === '') {
                        continue;
                    }
                    $tech_type = $tech_category_map[$tech_name] ?? 'Other';
                    $tstmt->bind_param('ssi', $tech_type, $tech_name, $new_project_id);
                    $tstmt->execute();
                }
                $tstmt->close();
            }

            header("Location: allprojectdashboard.php?added=1");
            exit;
        } else {
            $error = "Error: " . $stmt->error;
            $stmt->close();
        }
    }
}

// Clients for the dropdown - this is the connection that lets a
// project's client contact info show up automatically wherever the
// project is displayed (project detail page, client's Projects tab, etc.)
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

  <style>
    /* Scrollable area for the Add Project form: keeps the field groups and
       Work Details sections inside a fixed-height, scrollable box so the
       page doesn't grow endlessly as work rows are added. Submit/Delete/
       Back buttons stay outside this box so they're always visible. */
    .add-project-scroll-area {
      max-height: 65vh;
      overflow-y: auto;
      padding-right: 12px;
      margin-bottom: 1rem;
    }
    .add-project-scroll-area::-webkit-scrollbar {
      width: 8px;
    }
    .add-project-scroll-area::-webkit-scrollbar-thumb {
      background-color: rgba(0, 0, 0, 0.2);
      border-radius: 4px;
    }
  </style>

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

            <!-- Add Slider Form -->
            <div class="card">
                <div class="card-header">
                    <h3 class="text-dark card-header">Add New Project</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="" id="addProjectForm">

                        <!-- Scrollable area starts: all fields + work sections -->
                        <div class="add-project-scroll-area">

                        <div class="row">
                            <!-- Field 1 -->
                            <div class="col-lg-6 mb-3">
                                <div class="form-floating">
                                    <input type="text" name="project_name" class="form-control" id="floatingProjectName" placeholder="Project Name" required>
                                    <label for="floatingProjectName">Project Name</label>
                                </div>
                            </div>

                            <!-- Field 2 -->
                            <div class="col-lg-6 mb-3">
                                <div class="form-floating">
                                    <select name="client_id" class="form-select" id="floatingClientId" onchange="handleClientDropdownChange(this)">
                                        <option value="">-- Select a client (optional) --</option>
                                        <?php if ($clients_list): while ($c = $clients_list->fetch_assoc()): ?>
                                            <option value="<?php echo (int) $c['id']; ?>" <?php echo ($preselected_client_id === (int) $c['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($c['client_name']); ?><?php echo $c['organization_name'] ? ' — ' . htmlspecialchars($c['organization_name']) : ''; ?>
                                            </option>
                                        <?php endwhile; endif; ?>
                                        <option value="__add_new__" class="fw-semibold text-primary">+ Add New Client</option>
                                    </select>
                                    <label for="floatingClientId">Client</label>
                                </div>
                            </div>

                            <!-- Field 3 -->
                            <div class="col-lg-6 mb-3">
                                <div class="form-floating">
                                    <input type="email" name="email" class="form-control" id="floatingEmail" placeholder="Email">
                                    <label for="floatingEmail">Email Address</label>
                                </div>
                            </div>

                            <!-- Field 4 -->
                            <div class="col-lg-6 mb-3">
                                <div class="form-floating">
                                    <input type="tel" name="phone" class="form-control" id="floatingPhone" placeholder="Phone" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" title="Enter exactly 10 digits" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)">
                                    <label for="floatingPhone">Phone Number</label>
                                </div>
                            </div>

                            

                            <!-- Field 5 -->
                               <div class="col-lg-6 mb-3">
                                <div class="form-floating">
                                    <input type="date" name="start_date" class="form-control" id="floatingDate" placeholder="Start Date">
                                    <label for="floatingDate">Start Date</label>
                                </div>
                            </div>
                            

                            <!-- Field 6 -->
                          <div class="col-lg-6 mb-3">
                                <div class="form-floating">
                                    <input type="date" name="end_date" class="form-control" id="floatingEndDate" placeholder="End Date">
                                    <label for="floatingEndDate">End Date</label>
                                </div>
                            </div>

                            <!-- Field 7 -->
                            <div class="col-lg-6 mb-3">
                                <div class="form-floating">
                                    <input type="number" name="num_users" class="form-control" id="floatingUsers" placeholder="Number of Users">
                                    <label for="floatingUsers">Number of Users</label>
                                </div>
                            </div>

                            <!-- Field: Technologies (multi-select dropdown) -->
                            <div class="col-lg-6 mb-3">
                                <div class="dropdown" id="techDropdownWrap">
                                    <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start d-flex align-items-center justify-content-between"
                                        type="button" id="techDropdownBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" style="height: calc(3.5rem + 2px);">
                                        <span id="techDropdownLabel" class="text-truncate text-muted">Select technologies</span>
                                    </button>
                                    <div class="dropdown-menu w-100 p-3" style="max-height: 320px; overflow-y: auto;" aria-labelledby="techDropdownBtn">
                                        <?php
                                        $tech_groups = [
                                            'Language' => ['JavaScript', 'PHP', 'Python', 'Java', 'TypeScript', 'C#'],
                                            'Frontend' => ['HTML', 'CSS', 'React', 'Vue.js', 'Angular', 'Bootstrap', 'Tailwind CSS', 'jQuery'],
                                            'Backend'  => ['Node.js', 'Laravel', 'Django', 'Express.js', '.NET', 'Spring Boot'],
                                            'Database' => ['MySQL', 'PostgreSQL', 'MongoDB', 'SQLite', 'Firebase'],
                                            'DevOps & Tools' => ['Git', 'Docker', 'AWS', 'Nginx', 'Linux', 'GitHub Actions'],
                                        ];
                                        foreach ($tech_groups as $group_name => $techs):
                                        ?>
                                        <h6 class="dropdown-header px-0 text-primary"><?php echo htmlspecialchars($group_name); ?></h6>
                                        <div class="row row-cols-2 g-1 mb-2">
                                            <?php foreach ($techs as $tech): $tech_id = 'tech_' . preg_replace('/[^A-Za-z0-9]/', '', $tech); ?>
                                            <div class="col">
                                                <div class="form-check">
                                                    <input class="form-check-input tech-checkbox" type="checkbox" name="technologies[]" value="<?php echo htmlspecialchars($tech); ?>" id="<?php echo $tech_id; ?>">
                                                    <label class="form-check-label" for="<?php echo $tech_id; ?>"><?php echo htmlspecialchars($tech); ?></label>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <div class="form-text">Select all technologies used in this project.</div>
                            </div>

                            <!-- Field 8 -->
                            <div class="col-lg-6 mb-3">
                                <div class="form-floating">
                                    <textarea name="description" class="form-control" id="floatingDescription" placeholder="Description" style="height: 80px;"></textarea>
                                    <label for="floatingDescription">Description</label>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        <!-- Inline Work Sections: add App / Dashboard / Website work
                             rows right here, they'll be saved against this project
                             the moment it's created. -->
                        <h5 class="fw-bold text-dark mb-3">Work Details <small class="text-muted fw-normal">(optional — add now or later from the project page)</small></h5>

                        <?php
                        // One little template renders all three sections since
                        // they're identical in shape (name / status / date).
                        $work_sections = [
                            [
                                'prefix' => 'app',
                                'title'  => 'App Work',
                                'icon'   => 'bi-phone',
                                'label'  => 'Screen Name',
                                'statuses' => ['Pending', 'Designed', 'Developed'],
                            ],
                            [
                                'prefix' => 'dash',
                                'title'  => 'Dashboard Work',
                                'icon'   => 'bi-grid-1x2',
                                'label'  => 'Page Name',
                                'statuses' => ['Pending', 'Designed', 'Developed'],
                            ],
                            [
                                'prefix' => 'web',
                                'title'  => 'Website Work',
                                'icon'   => 'bi-globe',
                                'label'  => 'Page Name',
                                'statuses' => ['Pending', 'Designed', 'Developed'],
                            ],
                        ];
                        foreach ($work_sections as $sec):
                        ?>
                        <div class="card border mb-3 work-section" data-prefix="<?php echo $sec['prefix']; ?>">
                            <div class="card-header d-flex justify-content-between align-items-center bg-light">
                                <span class="fw-semibold"><i class="bi <?php echo $sec['icon']; ?> me-2"></i><?php echo $sec['title']; ?></span>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-add-work-row">
                                    <i class="bi bi-plus-lg me-1"></i> Add Row
                                </button>
                            </div>
                            <div class="card-body project-form-scroll">
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0 work-rows-table">
                                        <thead>
                                            <tr class="text-secondary small">
                                                <th style="min-width: 220px;"><?php echo $sec['label']; ?></th>
                                                <th style="min-width: 160px;">Status</th>
                                                <th style="min-width: 170px;">Created Date</th>
                                                <th style="min-width: 170px;">End Date</th>
                                                <th style="width: 50px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="work-rows-body">
                                            <!-- rows added via JS; starts empty, "Add Row" creates the first one -->
                                        </tbody>
                                    </table>
                                </div>
                                <p class="text-muted small mb-0 no-rows-msg">No <?php echo strtolower($sec['title']); ?> added yet — click "Add Row" if you have some to record.</p>
                            </div>
                        </div>

                        <!-- Row template for this section (cloned by JS, never submitted itself) -->
                        <template id="tpl-row-<?php echo $sec['prefix']; ?>">
                            <tr>
                                <td>
                                    <input type="text" class="form-control" name="<?php echo $sec['prefix']; ?>_name[]" placeholder="<?php echo $sec['label']; ?>">
                                </td>
                                <td>
                                    <select class="form-select" name="<?php echo $sec['prefix']; ?>_status[]">
                                        <?php foreach ($sec['statuses'] as $st): ?>
                                        <option value="<?php echo $st; ?>"><?php echo $st; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="date" class="form-control" name="<?php echo $sec['prefix']; ?>_created_date[]" value="<?php echo date('Y-m-d'); ?>">
                                </td>
                                <td>
                                    <input type="date" class="form-control" name="<?php echo $sec['prefix']; ?>_end_date[]">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-work-row" title="Remove">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                        <?php endforeach; ?>

                        </div>
                        <!-- Scrollable area ends -->

                        <!-- Buttons -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="bx bx-check me-1"></i> Submit
                                </button>
                                <button type="button" class="btn btn-danger me-2" id="btnResetForm" onclick="resetAddProjectForm()">
                                    <i class="bx bx-trash me-1"></i> Delete
                                </button>
                                <a href="allprojectdashboard.php" class="btn btn-secondary">
                                    <i class="bx bx-arrow-back me-1"></i> Back
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <!--/ Add Slider Form -->
        </div>
    </div>

            <?php include 'common/footer.php'; ?>
        </div>
        <!-- / Layout page -->
      </div>
    </div>
    <!-- / Layout wrapper -->

    <?php if ($error): ?>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        Swal.fire({
          title: 'Could not add project',
          text: <?php echo json_encode($error); ?>,
          icon: 'error',
          confirmButtonText: 'OK'
        });
      });
    </script>
    <?php endif; ?>

    <script>
      document.addEventListener('DOMContentLoaded', function () {
        function refreshEmptyState(section) {
          var body = section.querySelector('.work-rows-body');
          var msg = section.querySelector('.no-rows-msg');
          var hasRows = body.querySelectorAll('tr').length > 0;
          msg.style.display = hasRows ? 'none' : '';
          body.closest('.table-responsive').style.display = hasRows ? '' : 'none';
        }

        document.querySelectorAll('.work-section').forEach(function (section) {
          refreshEmptyState(section); // start collapsed since it starts empty

          var prefix = section.getAttribute('data-prefix');
          var template = document.getElementById('tpl-row-' + prefix);
          var body = section.querySelector('.work-rows-body');

          section.querySelector('.btn-add-work-row').addEventListener('click', function () {
            var clone = template.content.cloneNode(true);
            body.appendChild(clone);
            refreshEmptyState(section);
          });

          body.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-remove-work-row');
            if (!btn) return;
            btn.closest('tr').remove();
            refreshEmptyState(section);
          });
        });
      });
    </script>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
        var techLabel = document.getElementById('techDropdownLabel');
        var techCheckboxes = document.querySelectorAll('.tech-checkbox');

        function updateTechLabel() {
          var selected = Array.prototype.filter.call(techCheckboxes, function (cb) { return cb.checked; })
            .map(function (cb) { return cb.value; });

          if (selected.length === 0) {
            techLabel.textContent = 'Select technologies';
            techLabel.classList.add('text-muted');
          } else {
            techLabel.textContent = selected.length + ' selected: ' + selected.join(', ');
            techLabel.classList.remove('text-muted');
          }
        }

        techCheckboxes.forEach(function (cb) {
          cb.addEventListener('change', updateTechLabel);
        });
      });
    </script>

    <!-- Delete / Reset Form logic -->
    <script>
      function resetAddProjectForm() {
        if (!confirm('Clear everything you have entered on this form? This cannot be undone.')) {
          return;
        }

        var form = document.getElementById('addProjectForm');

        // Reset all native inputs/selects/textareas back to their defaults
        form.reset();

        // Remove every dynamically-added Work Details row from all three sections
        document.querySelectorAll('.work-rows-body').forEach(function (body) {
          body.innerHTML = '';
        });
        document.querySelectorAll('.work-section').forEach(function (section) {
          var bodyEl = section.querySelector('.work-rows-body');
          var msg = section.querySelector('.no-rows-msg');
          msg.style.display = '';
          bodyEl.closest('.table-responsive').style.display = 'none';
        });

        // Reset the technologies dropdown label (checkboxes are cleared by form.reset())
        var techLabel = document.getElementById('techDropdownLabel');
        techLabel.textContent = 'Select technologies';
        techLabel.classList.add('text-muted');

        // Reset the client dropdown to the placeholder explicitly
        // (in case a client was pre-selected via ?client_id= in the URL)
        var clientDropdown = document.getElementById('floatingClientId');
        clientDropdown.value = '';
        lastRealClientValue = '';

        // Scroll back to the top of the form
        document.querySelector('.add-project-scroll-area').scrollTop = 0;
      }
    </script>

    <!-- Quick Add Client Modal (triggered from the Client dropdown) -->
    <div class="modal fade" id="quickAddClientModal" tabindex="-1" aria-labelledby="quickAddClientModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="quickAddClientModalLabel">Add New Client</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div id="quickAddClientError" class="alert alert-danger d-none" role="alert"></div>
            <div class="mb-3">
              <label for="qacClientName" class="form-label fw-semibold">Client Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="qacClientName" placeholder="Enter client name">
            </div>
            <div class="mb-3">
              <label for="qacOrganization" class="form-label fw-semibold">Organization</label>
              <input type="text" class="form-control" id="qacOrganization" placeholder="Enter organization">
            </div>
            <div class="mb-3">
              <label for="qacEmail" class="form-label fw-semibold">Email</label>
              <input type="email" class="form-control" id="qacEmail" placeholder="Enter email">
            </div>
            <div class="mb-3">
              <label for="qacMobile" class="form-label fw-semibold">Mobile</label>
              <input type="tel" class="form-control" id="qacMobile" placeholder="Enter mobile" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" title="Enter exactly 10 digits" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="qacSaveBtn" onclick="saveQuickAddClient()">Save Client</button>
          </div>
        </div>
      </div>
    </div>

    <script>
      var clientDropdown = document.getElementById('floatingClientId');
      var lastRealClientValue = clientDropdown ? clientDropdown.value : '';
      var quickAddClientModalEl = document.getElementById('quickAddClientModal');
      var quickAddClientModal = quickAddClientModalEl ? new bootstrap.Modal(quickAddClientModalEl) : null;

      function handleClientDropdownChange(select) {
        if (select.value === '__add_new__') {
          document.getElementById('qacClientName').value = '';
          document.getElementById('qacOrganization').value = '';
          document.getElementById('qacEmail').value = '';
          document.getElementById('qacMobile').value = '';
          document.getElementById('quickAddClientError').classList.add('d-none');
          quickAddClientModal.show();
        } else {
          lastRealClientValue = select.value;
        }
      }

      // If the modal is dismissed without saving, fall back to whatever was selected before
      quickAddClientModalEl.addEventListener('hidden.bs.modal', function () {
        if (clientDropdown.value === '__add_new__') {
          clientDropdown.value = lastRealClientValue;
        }
      });

      function saveQuickAddClient() {
        var name = document.getElementById('qacClientName').value.trim();
        var errorBox = document.getElementById('quickAddClientError');
        errorBox.classList.add('d-none');

        if (name === '') {
          errorBox.textContent = 'Client Name is required.';
          errorBox.classList.remove('d-none');
          return;
        }

        var saveBtn = document.getElementById('qacSaveBtn');
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';

        var formData = new FormData();
        formData.append('client_name', name);
        formData.append('organization_name', document.getElementById('qacOrganization').value.trim());
        formData.append('email', document.getElementById('qacEmail').value.trim());
        formData.append('mobile', document.getElementById('qacMobile').value.trim());

        fetch('ajax_add_client.php', { method: 'POST', body: formData })
          .then(function (res) { return res.json(); })
          .then(function (data) {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Client';

            if (!data.success) {
              errorBox.textContent = data.message || 'Could not save the client.';
              errorBox.classList.remove('d-none');
              return;
            }

            // Insert the new client into the dropdown, right before "+ Add New Client", and select it
            var newOption = document.createElement('option');
            newOption.value = data.id;
            newOption.textContent = data.label;
            var addNewOption = clientDropdown.querySelector('option[value="__add_new__"]');
            clientDropdown.insertBefore(newOption, addNewOption);
            clientDropdown.value = data.id;
            lastRealClientValue = data.id;

            quickAddClientModal.hide();
          })
          .catch(function () {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Client';
            errorBox.textContent = 'Something went wrong. Please try again.';
            errorBox.classList.remove('d-none');
          });
      }
    </script>
  </body>
</html>