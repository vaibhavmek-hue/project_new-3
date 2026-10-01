<?php
/**
 * Live chart data endpoint.
 * Returns monthly project counts (Completed vs In Progress) for a given year,
 * pulled straight from the `projects` table so the dashboard bar chart
 * always reflects real, current data instead of demo numbers.
 *
 * Usage: chart_data.php?year=2026
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

require_once 'db.php';
header('Content-Type: application/json');

$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));

// Initialize all 12 months to 0 so the chart always has a full x-axis,
// even for months with no projects yet.
$completed  = array_fill(1, 12, 0);
$inProgress = array_fill(1, 12, 0);
$onHold     = array_fill(1, 12, 0);

$sql = "SELECT MONTH(created_at) AS m, status, COUNT(*) AS cnt
        FROM projects
        WHERE YEAR(created_at) = ?
        GROUP BY MONTH(created_at), status";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $year);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $m = (int) $row['m'];
    switch ($row['status']) {
        case 'Completed':
            $completed[$m] = (int) $row['cnt'];
            break;
        case 'In Progress':
            $inProgress[$m] = (int) $row['cnt'];
            break;
        case 'On Hold':
            $onHold[$m] = (int) $row['cnt'];
            break;
    }
}

$stmt->close();

// Total projects (all-time) and this year, handy for the side summary cards.
$totalAllTime = 0;
if ($r = $conn->query("SELECT COUNT(*) AS c FROM projects")) {
    $totalAllTime = (int) $r->fetch_assoc()['c'];
}

$totalThisYear = array_sum($completed) + array_sum($inProgress) + array_sum($onHold);

$conn->close();

echo json_encode([
    'year'        => $year,
    'categories'  => ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
    'completed'   => array_values($completed),
    'inprogress'  => array_values($inProgress),
    'onhold'      => array_values($onHold),
    'totalAllTime'  => $totalAllTime,
    'totalThisYear' => $totalThisYear,
]);
