<?php
/**
 * Consolidated live dashboard stats endpoint.
 * Feeds every widget on index.php (except the Monthly Projects bar chart,
 * which is already served by chart_data.php) with real numbers pulled
 * straight from the database.
 *
 * Usage: dashboard_stats.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

require_once 'db.php';

$thisYear  = (int) date('Y');
$lastYear  = $thisYear - 1;
$thisMonth = (int) date('n');

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

/* ---------- Core project / client totals ---------- */
$totalClients   = scalarCount($conn, "SELECT COUNT(*) AS c FROM clients");
$totalProjects  = scalarCount($conn, "SELECT COUNT(*) AS c FROM projects");
$completed      = scalarCount($conn, "SELECT COUNT(*) AS c FROM projects WHERE status = 'Completed'");
$inProgress     = scalarCount($conn, "SELECT COUNT(*) AS c FROM projects WHERE status = 'In Progress'");
$onHold         = scalarCount($conn, "SELECT COUNT(*) AS c FROM projects WHERE status = 'On Hold'");
$completionRate = $totalProjects > 0 ? round(($completed / $totalProjects) * 100) : 0;

$projectsThisYear = scalarCount($conn, "SELECT COUNT(*) AS c FROM projects WHERE YEAR(created_at) = ?", "i", [$thisYear]);
$projectsLastYear  = scalarCount($conn, "SELECT COUNT(*) AS c FROM projects WHERE YEAR(created_at) = ?", "i", [$lastYear]);

/* ---------- New-projects trend, last 6 months (for the sparkline) ---------- */
$trend = [];
for ($i = 5; $i >= 0; $i--) {
    $ts = strtotime("first day of -$i month");
    $y  = (int) date('Y', $ts);
    $m  = (int) date('n', $ts);
    $trend[] = scalarCount(
        $conn,
        "SELECT COUNT(*) AS c FROM projects WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?",
        "ii",
        [$y, $m]
    );
}
$projectsThisMonth = $trend[count($trend) - 1];
$projectsPrevMonth = $trend[count($trend) - 2];
$monthOverMonth = $projectsPrevMonth > 0
    ? round((($projectsThisMonth - $projectsPrevMonth) / $projectsPrevMonth) * 100)
    : ($projectsThisMonth > 0 ? 100 : 0);

/* ---------- Work items: website / app / dashboard / technologies ---------- */
$websiteWork = scalarCount($conn, "SELECT COUNT(*) AS c FROM website_work");
$appWork     = scalarCount($conn, "SELECT COUNT(*) AS c FROM app_work");
$dashWork    = scalarCount($conn, "SELECT COUNT(*) AS c FROM dashboard_work");
$techCount   = scalarCount($conn, "SELECT COUNT(*) AS c FROM technologies");
$totalWorkItems = $websiteWork + $appWork + $dashWork + $techCount;

/* ---------- Clients trend, last 8 months (repurposed "income" chart) ---------- */
$clientTrendCategories = [];
$clientTrend = [];
for ($i = 7; $i >= 0; $i--) {
    $ts = strtotime("first day of -$i month");
    $y  = (int) date('Y', $ts);
    $m  = (int) date('n', $ts);
    $clientTrendCategories[] = date('M', $ts);
    $clientTrend[] = scalarCount(
        $conn,
        "SELECT COUNT(*) AS c FROM clients WHERE YEAR(created_at) = ? AND MONTH(created_at) = ?",
        "ii",
        [$y, $m]
    );
}

$clientsThisWeek = scalarCount($conn, "SELECT COUNT(*) AS c FROM clients WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE(), 1)");
$clientsLastWeek = scalarCount($conn, "SELECT COUNT(*) AS c FROM clients WHERE YEARWEEK(created_at, 1) = YEARWEEK(CURDATE() - INTERVAL 7 DAY, 1)");
$clientsWeekDelta = $clientsThisWeek - $clientsLastWeek;

/* ---------- Recent projects (replaces the demo "Transactions" list) ---------- */
$recentProjects = [];
if ($r = $conn->query("SELECT project_name, client_name, status, created_at FROM projects ORDER BY created_at DESC LIMIT 6")) {
    while ($row = $r->fetch_assoc()) {
        $recentProjects[] = [
            'project_name' => $row['project_name'],
            'client_name'  => $row['client_name'] ?: '—',
            'status'       => $row['status'],
            'created_at'   => date('M j', strtotime($row['created_at'])),
        ];
    }
}

$conn->close();

echo json_encode([
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
    'trend' => [
        'data'           => $trend,
        'thisMonth'      => $projectsThisMonth,
        'monthOverMonth' => $monthOverMonth,
        'year'           => $thisYear,
    ],
    'workItems' => [
        'website' => $websiteWork,
        'app'     => $appWork,
        'dashboard' => $dashWork,
        'technologies' => $techCount,
        'total'   => $totalWorkItems,
    ],
    'clients' => [
        'categories'  => $clientTrendCategories,
        'data'        => $clientTrend,
        'thisWeek'    => $clientsThisWeek,
        'weekDelta'   => $clientsWeekDelta,
    ],
    'recentProjects' => $recentProjects,
]);
