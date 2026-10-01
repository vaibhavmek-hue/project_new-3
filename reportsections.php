<?php
require_once 'common/auth_check.php';
include 'db.php';

/**
 * reportsections.php
 * --------------------
 * One screen to manage every "extra" list that the full client
 * report (download_report.php) can print: team members, Figma
 * design pages, live page links, operations checklists, database
 * tables, the API list, and the changes/suggestions log.
 *
 * Each section below is just a table name + which columns it has;
 * the add/edit/delete form and the list are both generated from
 * that config, so adding a new section later means adding one
 * entry here rather than a whole new file.
 */

// ===============================================================
// SECTION CONFIG
// ===============================================================

$SECTIONS = [
    'team' => [
        'table'    => 'project_team_members',
        'label'    => 'Team Members',
        'singular' => 'Team Member',
        'icon'     => 'fa-solid fa-users',
        'columns'  => [
            'name' => ['label' => 'Name', 'type' => 'text', 'required' => true],
            'role' => ['label' => 'Role', 'type' => 'text', 'required' => true, 'placeholder' => 'e.g. Sr. Web Developer'],
        ],
    ],
    'figma' => [
        'table'    => 'figma_pages',
        'label'    => 'Figma Design Work',
        'singular' => 'Figma Page',
        'icon'     => 'fa-brands fa-figma',
        'columns'  => [
            'page_name'    => ['label' => 'Page', 'type' => 'text', 'required' => true],
            'created_date' => ['label' => 'Date Added', 'type' => 'date', 'required' => true],
        ],
    ],
    'website_designed' => [
        'table'    => 'website_pages_designed',
        'label'    => 'Website Pages - Designed',
        'singular' => 'Website Page',
        'icon'     => 'fa-solid fa-pen-ruler',
        'columns'  => [
            'page_name'    => ['label' => 'Page', 'type' => 'text', 'required' => true],
            'created_date' => ['label' => 'Date Added', 'type' => 'date', 'required' => true],
        ],
    ],
    'website_links' => [
        'table'    => 'website_page_links',
        'label'    => 'Website Pages - Developed',
        'singular' => 'Website Page',
        'icon'     => 'fa-solid fa-globe',
        'columns'  => [
            'page_name'    => ['label' => 'Page', 'type' => 'text', 'required' => true],
            'page_link'    => ['label' => 'Page Link', 'type' => 'url', 'required' => false, 'placeholder' => 'https://example.com/page'],
            'created_date' => ['label' => 'Date Added', 'type' => 'date', 'required' => true],
        ],
    ],
    'dashboard_designed' => [
        'table'    => 'dashboard_pages_designed',
        'label'    => 'Dashboard Pages - Designed',
        'singular' => 'Dashboard Page',
        'icon'     => 'fa-solid fa-pen-ruler',
        'columns'  => [
            'page_name'    => ['label' => 'Page', 'type' => 'text', 'required' => true],
            'created_date' => ['label' => 'Date Added', 'type' => 'date', 'required' => true],
        ],
    ],
    'dashboard_links' => [
        'table'    => 'dashboard_page_links',
        'label'    => 'Dashboard Pages - Developed',
        'singular' => 'Dashboard Page',
        'icon'     => 'fa-solid fa-laptop',
        'columns'  => [
            'page_name'    => ['label' => 'Page', 'type' => 'text', 'required' => true],
            'page_link'    => ['label' => 'Page Link', 'type' => 'url', 'required' => false, 'placeholder' => 'https://admin.example.com/page'],
            'created_date' => ['label' => 'Date Added', 'type' => 'date', 'required' => true],
        ],
    ],
    'app_designed' => [
        'table'    => 'app_pages_designed',
        'label'    => 'App Pages - Designed',
        'singular' => 'App Page',
        'icon'     => 'fa-solid fa-mobile-button',
        'columns'  => [
            'page_name'    => ['label' => 'Screen', 'type' => 'text', 'required' => true],
            'created_date' => ['label' => 'Date Added', 'type' => 'date', 'required' => true],
        ],
    ],
    'app_links' => [
        'table'    => 'app_page_links',
        'label'    => 'App Pages - Developed',
        'singular' => 'App Page',
        'icon'     => 'fa-solid fa-mobile-button',
        'columns'  => [
            'page_name'    => ['label' => 'Screen', 'type' => 'text', 'required' => true],
            'page_link'    => ['label' => 'Page Link', 'type' => 'url', 'required' => false, 'placeholder' => 'https://example.com/app-screen'],
            'created_date' => ['label' => 'Date Added', 'type' => 'date', 'required' => true],
        ],
    ],
    'dashboard_ops' => [
        'table'    => 'dashboard_operations',
        'label'    => 'Dashboard - Operations List',
        'singular' => 'Operation',
        'icon'     => 'fa-solid fa-list-check',
        'columns'  => [
            'page'         => ['label' => 'Page', 'type' => 'text', 'required' => true],
            'section'      => ['label' => 'Section', 'type' => 'text', 'required' => true],
            'operation'    => ['label' => 'Operation', 'type' => 'textarea', 'required' => true],
            'created_date' => ['label' => 'Date Added', 'type' => 'date', 'required' => true],
        ],
    ],
    'app_ops' => [
        'table'    => 'app_operations',
        'label'    => 'App - Operations List',
        'singular' => 'Operation',
        'icon'     => 'fa-solid fa-list-check',
        'columns'  => [
            'screen'       => ['label' => 'Screen', 'type' => 'text', 'required' => true],
            'section'      => ['label' => 'Section', 'type' => 'text', 'required' => true],
            'operation'    => ['label' => 'Operation', 'type' => 'textarea', 'required' => true],
            'created_date' => ['label' => 'Date Added', 'type' => 'date', 'required' => true],
        ],
    ],
    'website_ops' => [
        'table'    => 'website_operations',
        'label'    => 'Website - Operations List',
        'singular' => 'Operation',
        'icon'     => 'fa-solid fa-list-check',
        'columns'  => [
            'section'      => ['label' => 'Section', 'type' => 'text', 'required' => true],
            'sub_section'  => ['label' => 'Sub-section', 'type' => 'text', 'required' => true],
            'operation'    => ['label' => 'Operation', 'type' => 'textarea', 'required' => true],
            'created_date' => ['label' => 'Date Added', 'type' => 'date', 'required' => true],
        ],
    ],
    'database_tables' => [
        'table'    => 'database_tables',
        'label'    => 'Database Tables',
        'singular' => 'Table',
        'icon'     => 'fa-solid fa-database',
        'columns'  => [
            'table_name'   => ['label' => 'Table Name', 'type' => 'text', 'required' => true],
            'created_date' => ['label' => 'Date Added', 'type' => 'date', 'required' => true],
        ],
    ],
    'api_list' => [
        'table'    => 'api_list',
        'label'    => 'API Creation &amp; Integration',
        'singular' => 'API',
        'icon'     => 'fa-solid fa-plug',
        'columns'  => [
            'api_name'        => ['label' => 'API Name', 'type' => 'text', 'required' => true],
            'created_flag'    => ['label' => 'Created', 'type' => 'checkbox'],
            'integrated_flag' => ['label' => 'Integrated', 'type' => 'checkbox'],
            'status'          => ['label' => 'Status', 'type' => 'text', 'placeholder' => 'Completed'],
            'created_date'    => ['label' => 'Date Added', 'type' => 'date', 'required' => true],
        ],
    ],
    'changes_log' => [
        'table'    => 'changes_log',
        'label'    => 'Changes / Suggestions / Modifications',
        'singular' => 'Change',
        'icon'     => 'fa-solid fa-clipboard-list',
        'columns'  => [
            'change_date' => ['label' => 'Date', 'type' => 'date', 'required' => true],
            'page'        => ['label' => 'Page', 'type' => 'text', 'required' => true],
            'section'     => ['label' => 'Section', 'type' => 'text', 'required' => true],
            'note'        => ['label' => 'Note / Suggestion', 'type' => 'textarea', 'required' => true],
        ],
    ],
];

// ===============================================================
// PARAMS
// ===============================================================

$project_id = isset($_GET['project_id']) ? (int) $_GET['project_id'] : 0;

if ($project_id <= 0) {
    http_response_code(400);
    die('Missing project_id. Open this page from a project\'s detail page.');
}

$project_res = $conn->query("SELECT * FROM projects WHERE id = " . (int) $project_id . " LIMIT 1");
if (!$project_res || $project_res->num_rows === 0) {
    http_response_code(404);
    die('Project not found.');
}
$project = $project_res->fetch_assoc();

$section = isset($_GET['section']) && isset($SECTIONS[$_GET['section']]) ? $_GET['section'] : 'team';
$config  = $SECTIONS[$section];
$table   = $config['table'];
$columns = $config['columns'];
$colKeys = array_keys($columns);

// ===============================================================
// HANDLE ADD / UPDATE
// ===============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['save_item']) || isset($_POST['update_item']))) {

    $values = [];
    foreach ($columns as $key => $def) {
        if ($def['type'] === 'checkbox') {
            $values[$key] = isset($_POST[$key]) ? 1 : 0;
        } else {
            $values[$key] = trim($_POST[$key] ?? '');
        }
    }

    if (isset($_POST['update_item'])) {
        $id = (int) $_POST['item_id'];
        $sets = [];
        $types = '';
        $bindValues = [];
        foreach ($colKeys as $key) {
            $sets[] = "`$key` = ?";
            $types .= ($columns[$key]['type'] === 'checkbox') ? 'i' : 's';
            $bindValues[] = $values[$key];
        }
        $types .= 'ii';
        $bindValues[] = $id;
        $bindValues[] = $project_id;

        $sql = "UPDATE `$table` SET " . implode(', ', $sets) . " WHERE id = ? AND project_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$bindValues);
        $stmt->execute();
        $stmt->close();

        header("Location: reportsections.php?project_id=$project_id&section=$section&updated=1");
        exit;
    } else {
        $cols = array_merge($colKeys, ['project_id']);
        $placeholders = implode(', ', array_fill(0, count($cols), '?'));
        $types = '';
        $bindValues = [];
        foreach ($colKeys as $key) {
            $types .= ($columns[$key]['type'] === 'checkbox') ? 'i' : 's';
            $bindValues[] = $values[$key];
        }
        $types .= 'i';
        $bindValues[] = $project_id;

        $sql = "INSERT INTO `$table` (`" . implode('`, `', $cols) . "`) VALUES ($placeholders)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$bindValues);
        $stmt->execute();
        $stmt->close();

        header("Location: reportsections.php?project_id=$project_id&section=$section&added=1");
        exit;
    }
}

// ===============================================================
// HANDLE DELETE
// ===============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_item'])) {
    $id = (int) $_POST['item_id'];
    $stmt = $conn->prepare("DELETE FROM `$table` WHERE id = ? AND project_id = ?");
    $stmt->bind_param('ii', $id, $project_id);
    $stmt->execute();
    $stmt->close();

    header("Location: reportsections.php?project_id=$project_id&section=$section&deleted=1");
    exit;
}

// ===============================================================
// FETCH ROWS FOR CURRENT SECTION
// ===============================================================

$orderCol = in_array('created_date', $colKeys) ? 'created_date' : (in_array('change_date', $colKeys) ? 'change_date' : 'id');
$rows = [];
$stmt = $conn->prepare("SELECT * FROM `$table` WHERE project_id = ? ORDER BY sort_order ASC, id ASC");
$stmt->bind_param('i', $project_id);
$stmt->execute();
$result = $stmt->get_result();
while ($result && ($row = $result->fetch_assoc())) {
    $rows[] = $row;
}
$stmt->close();

// Counts for the tab badges
$sectionCounts = [];
foreach ($SECTIONS as $key => $cfg) {
    $cRes = $conn->query("SELECT COUNT(*) AS total FROM `{$cfg['table']}` WHERE project_id = " . (int) $project_id);
    $sectionCounts[$key] = $cRes ? (int) $cRes->fetch_assoc()['total'] : 0;
}
?>
<!DOCTYPE html>
<html lang="en" class="light-style layout-menu-fixed" dir="ltr" data-theme="theme-default" data-assets-path="./assets/" data-template="vertical-menu-template-free">
  <?php include 'common/header.php'; ?>

  <body>
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <?php include 'common/sidebar.php'; ?>

        <div class="layout-page">
          <?php include 'common/navbar.php'; ?>

          <div class="m-4">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3 mb-3">
              <div>
                <h1 class="h3 fw-bold m-0 text-dark">Report Details</h1>
                <p class="text-muted mb-0">
                  For <a href="projectdetail.php?id=<?php echo (int) $project_id; ?>"><?php echo htmlspecialchars($project['project_name']); ?></a>
                  &mdash; these lists print in the full PDF report (team, Figma pages, page links, operations, database tables, API list, changes log).
                </p>
              </div>
              <a href="download_report.php?project_id=<?php echo (int) $project_id; ?>&view=1" class="btn btn-outline-primary btn-sm align-self-sm-auto" target="_blank">
                <i class="bi bi-eye"></i> Preview Report
              </a>
            </div>

            <!-- Section tabs -->
            <ul class="nav nav-pills mb-4 flex-wrap gap-2">
              <?php foreach ($SECTIONS as $key => $cfg): ?>
                <li class="nav-item">
                  <a class="nav-link <?php echo $key === $section ? 'active' : ''; ?>"
                     href="reportsections.php?project_id=<?php echo (int) $project_id; ?>&section=<?php echo $key; ?>">
                    <i class="<?php echo $cfg['icon']; ?> me-1"></i>
                    <?php echo $cfg['label']; ?>
                    <span class="badge bg-light text-dark ms-1"><?php echo $sectionCounts[$key]; ?></span>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>

            <!-- ============ LIST VIEW ============ -->
            <div id="listView">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold m-0"><?php echo $config['label']; ?></h2>
                <button type="button" id="showAddFormBtn" class="btn btn-primary px-3 py-2 fw-semibold d-flex align-items-center gap-2">
                  <i class="fa-solid fa-plus"></i> Add <?php echo $config['singular']; ?>
                </button>
              </div>

              <div class="card border rounded-3 overflow-hidden">
                <div class="table-responsive">
                  <table class="table align-middle mb-0 text-nowrap">
                    <thead class="table-light">
                      <tr class="text-secondary small">
                        <th class="ps-4 py-3 fw-bold">Sr No</th>
                        <?php foreach ($columns as $key => $def): ?>
                          <th class="py-3 fw-bold"><?php echo htmlspecialchars($def['label']); ?></th>
                        <?php endforeach; ?>
                        <th class="py-3 fw-bold text-center">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php if (!empty($rows)): $sr = 1; foreach ($rows as $row): ?>
                        <tr>
                          <td class="ps-4 text-secondary small"><?php echo $sr++; ?></td>
                          <?php foreach ($columns as $key => $def): ?>
                            <td class="small">
                              <?php
                                $val = $row[$key] ?? '';
                                if ($def['type'] === 'checkbox') {
                                    echo $val ? '<span class="badge bg-success-subtle text-success">Yes</span>' : '<span class="badge bg-secondary-subtle text-secondary">No</span>';
                                } elseif ($def['type'] === 'url' && $val !== '') {
                                    echo '<a href="' . htmlspecialchars($val) . '" target="_blank" class="text-truncate d-inline-block" style="max-width:260px;">' . htmlspecialchars($val) . '</a>';
                                } elseif ($def['type'] === 'date' && $val !== '') {
                                    echo htmlspecialchars(date('M d, Y', strtotime($val)));
                                } else {
                                    echo nl2br(htmlspecialchars((string) $val));
                                }
                              ?>
                            </td>
                          <?php endforeach; ?>
                          <td class="text-center">
                            <div class="d-flex justify-content-center gap-2">
                              <button type="button" class="btn btn-outline-secondary btn-sm rounded-2 border px-2 py-1 btn-edit btn-primary" title="Edit"
                                data-id="<?php echo (int) $row['id']; ?>"
                                <?php foreach ($columns as $key => $def): ?>
                                  data-<?php echo $key; ?>="<?php echo htmlspecialchars((string) ($row[$key] ?? '')); ?>"
                                <?php endforeach; ?>
                              >
                                <i class="fa-solid fa-pen-clip text-white"></i>
                              </button>
                              <button type="button" class="btn btn-outline-secondary btn-sm rounded-2 border px-2 py-1 btn-delete btn-primary" title="Delete"
                                data-id="<?php echo (int) $row['id']; ?>">
                                <i class="fa-regular fa-trash-can text-white"></i>
                              </button>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; else: ?>
                        <tr>
                          <td colspan="<?php echo count($columns) + 2; ?>" class="text-center py-4 text-muted">
                            No <?php echo strtolower($config['label']); ?> added yet.
                          </td>
                        </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <!-- ============ / LIST VIEW ============ -->

            <!-- ============ ADD / EDIT FORM VIEW ============ -->
            <div id="addFormView" style="display: none;">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h5 fw-bold m-0" id="formTitle">Add <?php echo $config['singular']; ?></h2>
                <button type="button" id="showListBtn" class="btn btn-outline-secondary px-3 py-2 fw-semibold d-flex align-items-center gap-2">
                  <i class="fa-solid fa-arrow-left"></i> Back to list
                </button>
              </div>

              <div class="card border rounded-3 overflow-hidden">
                <div class="card-body p-4">
                  <form method="POST" action="" id="itemForm">
                    <input type="hidden" name="item_id" id="item_id" value="">
                    <div class="row g-4">
                      <?php foreach ($columns as $key => $def): ?>
                        <div class="col-12 <?php echo $def['type'] === 'textarea' ? '' : 'col-md-6'; ?>">
                          <?php if ($def['type'] === 'checkbox'): ?>
                            <div class="form-check mt-4">
                              <input class="form-check-input" type="checkbox" id="<?php echo $key; ?>" name="<?php echo $key; ?>" checked>
                              <label class="form-check-label fw-semibold" for="<?php echo $key; ?>"><?php echo htmlspecialchars($def['label']); ?></label>
                            </div>
                          <?php elseif ($def['type'] === 'textarea'): ?>
                            <label for="<?php echo $key; ?>" class="form-label fw-semibold"><?php echo htmlspecialchars($def['label']); ?></label>
                            <textarea class="form-control" id="<?php echo $key; ?>" name="<?php echo $key; ?>" rows="3"
                              <?php echo !empty($def['required']) ? 'required' : ''; ?>
                              placeholder="<?php echo htmlspecialchars($def['placeholder'] ?? ''); ?>"></textarea>
                          <?php else: ?>
                            <label for="<?php echo $key; ?>" class="form-label fw-semibold"><?php echo htmlspecialchars($def['label']); ?></label>
                            <input type="<?php echo $def['type']; ?>" class="form-control" id="<?php echo $key; ?>" name="<?php echo $key; ?>"
                              <?php echo !empty($def['required']) ? 'required' : ''; ?>
                              placeholder="<?php echo htmlspecialchars($def['placeholder'] ?? ''); ?>">
                          <?php endif; ?>
                        </div>
                      <?php endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-5">
                      <button type="button" id="cancelAddBtn" class="btn btn-outline-secondary px-4 py-2">Cancel</button>
                      <button type="submit" name="save_item" id="submitBtn" class="btn btn-primary px-4 py-2">
                        <i class="fa-solid fa-check me-1"></i> Save
                      </button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
            <!-- ============ / ADD / EDIT FORM VIEW ============ -->
          </div>

          <?php include 'common/footer.php'; ?>

          <form method="POST" action="" id="deleteForm" style="display:none;">
            <input type="hidden" name="item_id" id="delete_item_id" value="">
            <input type="hidden" name="delete_item" value="1">
          </form>

          <script>
            document.addEventListener('DOMContentLoaded', function () {
              var listView = document.getElementById('listView');
              var addFormView = document.getElementById('addFormView');
              var showAddFormBtn = document.getElementById('showAddFormBtn');
              var showListBtn = document.getElementById('showListBtn');
              var cancelAddBtn = document.getElementById('cancelAddBtn');
              var itemForm = document.getElementById('itemForm');
              var formTitle = document.getElementById('formTitle');
              var submitBtn = document.getElementById('submitBtn');
              var itemIdInput = document.getElementById('item_id');
              var columnKeys = <?php echo json_encode($colKeys); ?>;
              var columnTypes = <?php echo json_encode(array_map(fn($d) => $d['type'], $columns)); ?>;

              function showAddForm() { listView.style.display = 'none'; addFormView.style.display = 'block'; }
              function showList() { addFormView.style.display = 'none'; listView.style.display = 'block'; }

              function resetFormToAddMode() {
                formTitle.textContent = 'Add <?php echo addslashes($config['singular']); ?>';
                submitBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Save';
                submitBtn.name = 'save_item';
                itemIdInput.value = '';
                itemForm.reset();
                columnKeys.forEach(function (key) {
                  if (columnTypes[key] === 'checkbox') {
                    document.getElementById(key).checked = true;
                  }
                });
              }

              showAddFormBtn.addEventListener('click', function () { resetFormToAddMode(); showAddForm(); });
              showListBtn.addEventListener('click', function () { resetFormToAddMode(); showList(); });
              cancelAddBtn.addEventListener('click', function () { resetFormToAddMode(); showList(); });

              document.querySelectorAll('.btn-edit').forEach(function (btn) {
                btn.addEventListener('click', function () {
                  formTitle.textContent = 'Edit <?php echo addslashes($config['singular']); ?>';
                  submitBtn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Update';
                  submitBtn.name = 'update_item';
                  itemIdInput.value = btn.getAttribute('data-id');

                  columnKeys.forEach(function (key) {
                    var field = document.getElementById(key);
                    if (!field) return;
                    if (columnTypes[key] === 'checkbox') {
                      field.checked = btn.getAttribute('data-' + key) === '1';
                    } else {
                      field.value = btn.getAttribute('data-' + key) || '';
                    }
                  });

                  showAddForm();
                });
              });

              var deleteForm = document.getElementById('deleteForm');
              var deleteItemIdInput = document.getElementById('delete_item_id');
              document.querySelectorAll('.btn-delete').forEach(function (btn) {
                btn.addEventListener('click', function () {
                  Swal.fire({
                    title: 'Delete this item?',
                    text: 'This cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#d33'
                  }).then(function (result) {
                    if (result.isConfirmed) {
                      deleteItemIdInput.value = btn.getAttribute('data-id');
                      deleteForm.submit();
                    }
                  });
                });
              });

              var params = new URLSearchParams(window.location.search);
              var alertMap = {
                added: { title: 'Added!', text: 'Item added successfully.' },
                updated: { title: 'Updated!', text: 'Item updated successfully.' },
                deleted: { title: 'Deleted!', text: 'Item deleted successfully.' }
              };
              Object.keys(alertMap).forEach(function (key) {
                if (params.get(key) === '1') {
                  Swal.fire({ title: alertMap[key].title, text: alertMap[key].text, icon: 'success', timer: 2000, showConfirmButton: false });
                  params.delete(key);
                  var newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
                  window.history.replaceState({}, document.title, newUrl);
                }
              });
            });
          </script>
        </div>
      </div>
    </div>
  </body>
</html>
