<?php require_once 'common/auth_check.php'; ?>
<?php
include 'db.php';

$user_id = (int) $_SESSION['user_id'];
$errors  = [];

// ---------- Generate a new key ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_key'])) {
    $label = trim($_POST['label'] ?? '');
    $scope = ($_POST['scope'] ?? 'read_write') === 'read_only' ? 'read' : 'read,write';

    if ($label === '') {
        $errors[] = "Please give the key a label (e.g. \"Mobile app\").";
    }

    if (empty($errors)) {
        // 32 random bytes -> 64 hex chars, prefixed so it's recognisable
        // at a glance (similar to how Stripe/GitHub key formats work).
        $rawKey    = 'pwrg_' . bin2hex(random_bytes(32));
        $keyHash   = hash('sha256', $rawKey);
        $keyPrefix = substr($rawKey, 0, 12) . '...';

        $stmt = $conn->prepare(
            "INSERT INTO api_keys (user_id, label, key_prefix, key_hash, scopes) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('issss', $user_id, $label, $keyPrefix, $keyHash, $scope);
        $stmt->execute();
        $stmt->close();

        // Shown exactly once. We never store the plaintext key anywhere,
        // so this is the only chance the user has to copy it.
        $_SESSION['new_api_key'] = $rawKey;
        header("Location: api_keys.php?generated=1");
        exit;
    }
}

// ---------- Revoke a key ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['revoke_key'])) {
    $id = (int) $_POST['key_id'];
    $stmt = $conn->prepare(
        "UPDATE api_keys SET status = 'Revoked', revoked_at = NOW() WHERE id = ? AND user_id = ?"
    );
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: api_keys.php?revoked=1");
    exit;
}

// One-time reveal of a freshly generated key.
$justGeneratedKey = $_SESSION['new_api_key'] ?? null;
unset($_SESSION['new_api_key']);

// ---------- Existing keys ----------
$keys = [];
$stmt = $conn->prepare(
    "SELECT id, label, key_prefix, scopes, status, created_at, last_used_at
     FROM api_keys WHERE user_id = ? ORDER BY created_at DESC"
);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $keys[] = $row;
}
$stmt->close();

// ---------- Items each key has touched (create/update via the API) ----------
function get_touched_items(mysqli $conn, int $keyId): array
{
    $items = [];

    $queries = [
        'Website Work'   => "SELECT ww.id, ww.page_name AS name, p.project_name, ww.last_api_touched_at
                              FROM website_work ww LEFT JOIN projects p ON p.id = ww.project_id
                              WHERE ww.last_api_key_id = ?",
        'App Work'       => "SELECT aw.id, aw.screen_name AS name, p.project_name, aw.last_api_touched_at
                              FROM app_work aw LEFT JOIN projects p ON p.id = aw.project_id
                              WHERE aw.last_api_key_id = ?",
        'Dashboard Work' => "SELECT dw.id, dw.page_name AS name, p.project_name, dw.last_api_touched_at
                              FROM dashboard_work dw LEFT JOIN projects p ON p.id = dw.project_id
                              WHERE dw.last_api_key_id = ?",
    ];

    foreach ($queries as $label => $sql) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $keyId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['work_type'] = $label;
            $items[] = $row;
        }
        $stmt->close();
    }

    usort($items, fn($a, $b) => strcmp($b['last_api_touched_at'] ?? '', $a['last_api_touched_at'] ?? ''));

    return $items;
}

foreach ($keys as &$k) {
    $k['touched_items'] = get_touched_items($conn, (int) $k['id']);
}
unset($k);
?>
<!DOCTYPE html>

<!-- =========================================================
* Sneat - Bootstrap 5 HTML Admin Template - Pro | v1.0.0
==============================================================
* Product Page: https://themeselection.com/products/sneat-bootstrap-html-admin-template/
* Created by: ThemeSelection
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
                <h2 class="fw-bold m-0 text-dark">API Keys</h2>
              </div>

              <p class="text-muted mb-4">
                API keys let external apps and services call this app's data over HTTP,
                separate from your browser login. Send a key as an
                <code>Authorization: Bearer &lt;key&gt;</code> header (or <code>X-API-Key</code>)
                when calling anything under <code>/api/</code>.
              </p>

              <?php if ($justGeneratedKey): ?>
                <div class="alert alert-success">
                  <h6 class="fw-semibold mb-2"><i class="bi bi-check-circle me-1"></i> Key generated</h6>
                  <p class="mb-2">Copy this key now — for your security, it won't be shown again.</p>
                  <code class="d-block p-2 bg-light rounded" style="word-break: break-all;">
                    <?php echo htmlspecialchars($justGeneratedKey); ?>
                  </code>
                </div>
              <?php endif; ?>

              <?php if (isset($_GET['revoked'])): ?>
                <div class="alert alert-info py-2">That key has been revoked and can no longer be used.</div>
              <?php endif; ?>

              <?php if (!empty($errors)): ?>
                <div class="alert alert-danger py-2">
                  <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $err): ?>
                      <li><?php echo htmlspecialchars($err); ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>

              <div class="row">
                <!-- Generate new key -->
                <div class="col-12 col-lg-4 mb-4">
                  <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-3 p-md-4">
                      <h5 class="fw-semibold mb-1">Generate a new key</h5>
                      <p class="text-muted small mb-4">Give it a label so you remember what it's for.</p>

                      <form method="POST" action="">
                        <div class="mb-3">
                          <label for="label" class="form-label fw-semibold">Label</label>
                          <input type="text" class="form-control" id="label" name="label"
                                 placeholder="e.g. Mobile app" required>
                        </div>

                        <div class="mb-4">
                          <label class="form-label fw-semibold">Access</label>
                          <select class="form-select" name="scope">
                            <option value="read_write">Read &amp; write</option>
                            <option value="read_only">Read only</option>
                          </select>
                        </div>

                        <div class="d-flex justify-content-end">
                          <button type="submit" name="generate_key" class="btn btn-primary px-4 py-2">
                            <i class="bi bi-key me-1"></i> Generate Key
                          </button>
                        </div>
                      </form>
                    </div>
                  </div>
                </div>

                <!-- Existing keys -->
                <div class="col-12 col-lg-8 mb-4">
                  <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body p-3 p-md-4">
                      <h5 class="fw-semibold mb-3">Your keys</h5>

                      <?php if (empty($keys)): ?>
                        <p class="text-muted mb-0">You haven't generated any API keys yet.</p>
                      <?php else: ?>
                        <div class="table-responsive">
                          <table class="table align-middle">
                            <thead>
                              <tr>
                                <th>Label</th>
                                <th>Key</th>
                                <th>Access</th>
                                <th>Status</th>
                                <th>Last used</th>
                                <th>Used on</th>
                                <th></th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php foreach ($keys as $k): ?>
                                <tr>
                                  <td><?php echo htmlspecialchars($k['label']); ?></td>
                                  <td><code><?php echo htmlspecialchars($k['key_prefix']); ?></code></td>
                                  <td><?php echo $k['scopes'] === 'read' ? 'Read only' : 'Read &amp; write'; ?></td>
                                  <td>
                                    <span class="badge <?php echo $k['status'] === 'Active' ? 'bg-success' : 'bg-secondary'; ?>">
                                      <?php echo htmlspecialchars($k['status']); ?>
                                    </span>
                                  </td>
                                  <td class="text-muted small">
                                    <?php echo $k['last_used_at'] ? date('M j, Y g:ia', strtotime($k['last_used_at'])) : 'Never'; ?>
                                  </td>
                                  <td>
                                    <?php if (!empty($k['touched_items'])): ?>
                                      <button type="button" class="btn btn-sm btn-outline-secondary"
                                              data-bs-toggle="collapse" data-bs-target="#items-<?php echo (int) $k['id']; ?>">
                                        <?php echo count($k['touched_items']); ?> item<?php echo count($k['touched_items']) === 1 ? '' : 's'; ?>
                                      </button>
                                    <?php else: ?>
                                      <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                  </td>
                                  <td class="text-end">
                                    <?php if ($k['status'] === 'Active'): ?>
                                      <form method="POST" action="" onsubmit="return confirm('Revoke this key? Any app using it will immediately lose access.');">
                                        <input type="hidden" name="key_id" value="<?php echo (int) $k['id']; ?>">
                                        <button type="submit" name="revoke_key" class="btn btn-sm btn-outline-danger">
                                          Revoke
                                        </button>
                                      </form>
                                    <?php endif; ?>
                                  </td>
                                </tr>
                                <?php if (!empty($k['touched_items'])): ?>
                                  <tr class="collapse" id="items-<?php echo (int) $k['id']; ?>">
                                    <td colspan="7" class="bg-light p-0">
                                      <div class="p-3">
                                        <table class="table table-sm table-borderless mb-0">
                                          <thead>
                                            <tr class="text-secondary small">
                                              <th>Type</th>
                                              <th>Item</th>
                                              <th>Project</th>
                                              <th>Touched</th>
                                            </tr>
                                          </thead>
                                          <tbody>
                                            <?php foreach ($k['touched_items'] as $item): ?>
                                              <tr class="small">
                                                <td><span class="badge bg-info-subtle text-info border-0"><?php echo htmlspecialchars($item['work_type']); ?></span></td>
                                                <td class="fw-medium"><?php echo htmlspecialchars($item['name']); ?></td>
                                                <td class="text-muted"><?php echo htmlspecialchars($item['project_name'] ?? '— Unlinked —'); ?></td>
                                                <td class="text-muted">
                                                  <?php echo $item['last_api_touched_at'] ? date('M j, Y g:ia', strtotime($item['last_api_touched_at'])) : '—'; ?>
                                                </td>
                                              </tr>
                                            <?php endforeach; ?>
                                          </tbody>
                                        </table>
                                      </div>
                                    </td>
                                  </tr>
                                <?php endif; ?>
                              <?php endforeach; ?>
                            </tbody>
                          </table>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
          <!-- / Content wrapper -->

            <?php include 'common/footer.php'; ?>
        </div>
        <!-- / Layout page -->
      </div>
    </div>
    <!-- / Layout wrapper -->
  </body>
</html>
