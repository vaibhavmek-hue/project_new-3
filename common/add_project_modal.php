<?php
/**
 * Shared "Add Project" modal (popup, no page navigation).
 *
 * Include this once, anywhere in the page body (e.g. right before the
 * closing </body> content or alongside other hidden forms/modals), on
 * any page that also includes common/add_project_handler.php near the
 * top to process the submission.
 *
 * Optional variable a host page can set BEFORE including this file:
 *   $preselected_client_id  (int) pre-select a client in the dropdown,
 *                            e.g. when opened from that client's own page.
 * If not set, falls back to ?client_id= in the URL.
 */

if (!isset($preselected_client_id)) {
    $preselected_client_id = isset($_GET['client_id']) ? (int) $_GET['client_id'] : 0;
}

// Clients for the dropdown.
$clients_list = $conn->query("SELECT id, client_name, organization_name FROM clients ORDER BY client_name ASC");

$tech_groups = [
    'Language' => ['JavaScript', 'PHP', 'Python', 'Java', 'TypeScript', 'C#'],
    'Frontend' => ['HTML', 'CSS', 'React', 'Vue.js', 'Angular', 'Bootstrap', 'Tailwind CSS', 'jQuery'],
    'Backend'  => ['Node.js', 'Laravel', 'Django', 'Express.js', '.NET', 'Spring Boot'],
    'Database' => ['MySQL', 'PostgreSQL', 'MongoDB', 'SQLite', 'Firebase'],
    'DevOps & Tools' => ['Git', 'Docker', 'AWS', 'Nginx', 'Linux', 'GitHub Actions'],
];

$work_sections = [
    ['prefix' => 'app',  'title' => 'App Work',       'icon' => 'bi-phone',       'label' => 'Screen Name', 'statuses' => ['Pending', 'Designed', 'Developed']],
    ['prefix' => 'dash', 'title' => 'Dashboard Work', 'icon' => 'bi-grid-1x2',    'label' => 'Page Name',   'statuses' => ['Pending', 'Designed', 'Developed']],
    ['prefix' => 'web',  'title' => 'Website Work',   'icon' => 'bi-globe',       'label' => 'Page Name',   'statuses' => ['Pending', 'Designed', 'Developed']],
];
?>
<!-- Add Project Modal -->
<div class="modal fade" id="addProjectModal" tabindex="-1" aria-labelledby="addProjectModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <form method="POST" action="" id="addProjectForm">
        <input type="hidden" name="add_project" value="1">
        <div class="modal-header">
          <h5 class="modal-title" id="addProjectModalLabel">Add New Project</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-lg-6 mb-3">
              <div class="form-floating">
                <input type="text" name="project_name" class="form-control" id="floatingProjectName" placeholder="Project Name" required>
                <label for="floatingProjectName">Project Name</label>
              </div>
            </div>

            <div class="col-lg-6 mb-3">
              <div class="form-floating">
                <select name="client_id" class="form-select" id="floatingClientId" onchange="handleClientDropdownChange(this)">
                  <option value="">-- Select a client (optional) --</option>
                  <?php if ($clients_list): $clients_list->data_seek(0); while ($c = $clients_list->fetch_assoc()): ?>
                    <option value="<?php echo (int) $c['id']; ?>" <?php echo ($preselected_client_id === (int) $c['id']) ? 'selected' : ''; ?>>
                      <?php echo htmlspecialchars($c['client_name']); ?><?php echo $c['organization_name'] ? ' — ' . htmlspecialchars($c['organization_name']) : ''; ?>
                    </option>
                  <?php endwhile; endif; ?>
                  <option value="__add_new__" class="fw-semibold text-primary">+ Add New Client</option>
                </select>
                <label for="floatingClientId">Client</label>
              </div>
            </div>

            <div class="col-lg-6 mb-3">
              <div class="form-floating">
                <input type="email" name="email" class="form-control" id="floatingEmail" placeholder="Email">
                <label for="floatingEmail">Email Address</label>
              </div>
            </div>

            <div class="col-lg-6 mb-3">
              <div class="form-floating">
                <input type="tel" name="phone" class="form-control" id="floatingPhone" placeholder="Phone" maxlength="10" inputmode="numeric" pattern="[0-9]{10}" title="Enter exactly 10 digits" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)">
                <label for="floatingPhone">Phone Number</label>
              </div>
            </div>

            <div class="col-lg-6 mb-3">
              <div class="form-floating">
                <input type="date" name="start_date" class="form-control" id="floatingDate" placeholder="Start Date">
                <label for="floatingDate">Start Date</label>
              </div>
            </div>

            <div class="col-lg-6 mb-3">
              <div class="form-floating">
                <input type="date" name="end_date" class="form-control" id="floatingEndDate" placeholder="End Date">
                <label for="floatingEndDate">End Date</label>
              </div>
            </div>

            <div class="col-lg-6 mb-3">
              <div class="form-floating">
                <input type="number" name="num_users" class="form-control" id="floatingUsers" placeholder="Number of Users">
                <label for="floatingUsers">Number of Users</label>
              </div>
            </div>

            <div class="col-lg-6 mb-3">
              <div class="dropdown" id="techDropdownWrap">
                <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start d-flex align-items-center justify-content-between"
                  type="button" id="techDropdownBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" style="height: calc(3.5rem + 2px);">
                  <span id="techDropdownLabel" class="text-truncate text-muted">Select technologies</span>
                </button>
                <div class="dropdown-menu w-100 p-3" style="max-height: 320px; overflow-y: auto;" aria-labelledby="techDropdownBtn">
                  <?php foreach ($tech_groups as $group_name => $techs): ?>
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
            </div>

            <div class="col-12 mb-3">
              <div class="form-floating">
                <textarea name="description" class="form-control" id="floatingDescription" placeholder="Description" style="height: 100px"></textarea>
                <label for="floatingDescription">Description</label>
              </div>
            </div>
          </div>

          <hr class="my-4">

          <h6 class="fw-bold text-dark mb-3">Work Details <small class="text-muted fw-normal">(optional — add now or later from the project page)</small></h6>

          <?php foreach ($work_sections as $sec): ?>
            <div class="card border mb-3 work-section" data-prefix="<?php echo $sec['prefix']; ?>">
              <div class="card-header d-flex justify-content-between align-items-center bg-light">
                <span class="fw-semibold"><i class="bi <?php echo $sec['icon']; ?> me-2"></i><?php echo $sec['title']; ?></span>
                <button type="button" class="btn btn-sm btn-outline-primary btn-add-work-row">
                  <i class="bi bi-plus-lg me-1"></i> Add Row
                </button>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table align-middle mb-0 work-rows-table">
                    <thead>
                      <tr class="text-secondary small">
                        <th style="min-width: 200px;"><?php echo $sec['label']; ?></th>
                        <th style="min-width: 150px;">Status</th>
                        <th style="min-width: 160px;">Created Date</th>
                        <th style="min-width: 160px;">End Date</th>
                        <th style="width: 50px;"></th>
                      </tr>
                    </thead>
                    <tbody class="work-rows-body"></tbody>
                  </table>
                </div>
                <p class="text-muted small mb-0 no-rows-msg">No <?php echo strtolower($sec['title']); ?> added yet — click "Add Row" if you have some to record.</p>
              </div>
            </div>

            <template id="tpl-row-<?php echo $sec['prefix']; ?>">
              <tr>
                <td><input type="text" class="form-control" name="<?php echo $sec['prefix']; ?>_name[]" placeholder="<?php echo $sec['label']; ?>"></td>
                <td>
                  <select class="form-select" name="<?php echo $sec['prefix']; ?>_status[]">
                    <?php foreach ($sec['statuses'] as $st): ?>
                      <option value="<?php echo $st; ?>"><?php echo $st; ?></option>
                    <?php endforeach; ?>
                  </select>
                </td>
                <td><input type="date" class="form-control" name="<?php echo $sec['prefix']; ?>_created_date[]" value="<?php echo date('Y-m-d'); ?>"></td>
                <td><input type="date" class="form-control" name="<?php echo $sec['prefix']; ?>_end_date[]"></td>
                <td class="text-center">
                  <button type="button" class="btn btn-sm btn-outline-danger btn-remove-work-row" title="Remove"><i class="bi bi-trash"></i></button>
                </td>
              </tr>
            </template>
          <?php endforeach; ?>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary px-4">
            <i class="bx bx-check me-1"></i> Save Project
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Quick Add Client Modal (triggered from the Client dropdown above) -->
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

<?php if (!empty($project_form_error)): ?>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    Swal.fire({
      title: 'Could not add project',
      text: <?php echo json_encode($project_form_error); ?>,
      icon: 'error',
      confirmButtonText: 'OK'
    }).then(function () {
      var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('addProjectModal'));
      modal.show();
    });
  });
</script>
<?php endif; ?>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    // "Add Projects" trigger buttons open the modal fresh every time.
    document.querySelectorAll('[data-bs-target="#addProjectModal"]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        document.getElementById('addProjectForm').reset();
      });
    });

    function refreshEmptyState(section) {
      var body = section.querySelector('.work-rows-body');
      var msg = section.querySelector('.no-rows-msg');
      var hasRows = body.querySelectorAll('tr').length > 0;
      msg.style.display = hasRows ? 'none' : '';
      body.closest('.table-responsive').style.display = hasRows ? '' : 'none';
    }

    document.querySelectorAll('.work-section').forEach(function (section) {
      refreshEmptyState(section);

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

  // ---- Quick Add Client (from the Client dropdown inside the modal) ----
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
