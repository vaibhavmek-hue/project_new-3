<?php
/**
 * /api/dashboard_stats.php
 *
 * GET /api/dashboard_stats.php -> same aggregate numbers shown on the
 * admin dashboard (index.php), for external dashboards/integrations.
 * Read-only: no "write" scope required, but a valid API key still is.
 */

require_once __DIR__ . '/../common/api_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    api_send_error(405, 'Method not allowed.');
}

function scalarCount($conn, $sql, $types = '', $params = []) {
    if ($types === '') {
        $r = $conn->query($sql);
        return $r ? (int) $r->fetch_assoc()['c'] : 0;
    }
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $r = $stmt->get_result();
    $val = $r ? (int) $r->fetch_assoc()['c'] : 0;
    $stmt->close();
    return $val;
}

$thisYear = (int) date('Y');
$lastYear = $thisYear - 1;

$totalClients   = scalarCount($conn, "SELECT COUNT(*) AS c FROM clients");
$totalProjects  = scalarCount($conn, "SELECT COUNT(*) AS c FROM projects");
$completed      = scalarCount($conn, "SELECT COUNT(*) AS c FROM projects WHERE status = 'Completed'");
$inProgress     = scalarCount($conn, "SELECT COUNT(*) AS c FROM projects WHERE status = 'In Progress'");
$onHold         = scalarCount($conn, "SELECT COUNT(*) AS c FROM projects WHERE status = 'On Hold'");
$completionRate = $totalProjects > 0 ? round(($completed / $totalProjects) * 100) : 0;

$projectsThisYear = scalarCount($conn, "SELECT COUNT(*) AS c FROM projects WHERE YEAR(created_at) = ?", "i", [$thisYear]);
$projectsLastYear = scalarCount($conn, "SELECT COUNT(*) AS c FROM projects WHERE YEAR(created_at) = ?", "i", [$lastYear]);

$websiteWork = scalarCount($conn, "SELECT COUNT(*) AS c FROM website_work");
$appWork     = scalarCount($conn, "SELECT COUNT(*) AS c FROM app_work");
$dashWork    = scalarCount($conn, "SELECT COUNT(*) AS c FROM dashboard_work");
$techCount   = scalarCount($conn, "SELECT COUNT(*) AS c FROM technologies");

api_send_success([
    'totals' => [
        'clients'        => $totalClients,
        'projects'       => $totalProjects,
        'completed'      => $completed,
        'inProgress'     => $inProgress,
        'onHold'         => $onHold,
        'completionRate' => $completionRate,
        'thisYear'       => $projectsThisYear,
        'lastYear'       => $projectsLastYear,
    ],
    'workItems' => [
        'website'      => $websiteWork,
        'app'          => $appWork,
        'dashboard'    => $dashWork,
        'technologies' => $techCount,
        'total'        => $websiteWork + $appWork + $dashWork + $techCount,
    ],
]);
