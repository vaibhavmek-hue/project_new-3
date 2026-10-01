<?php
/**
 * check_ops.php - read-only diagnostic
 * Usage: check_ops.php?project_id=123
 * Shows exactly what the report generator will see for that project.
 */
require_once __DIR__ . '/db.php';

$project_id = isset($_GET['project_id']) ? (int) $_GET['project_id'] : 0;
if ($project_id <= 0) {
    die('Pass a project id, e.g. check_ops.php?project_id=123');
}

$proj = $conn->query("SELECT id, project_name FROM projects WHERE id = $project_id")->fetch_assoc();
echo "<h3>Project</h3>";
echo $proj ? "ID {$proj['id']}: " . htmlspecialchars($proj['project_name']) : "No project found with id $project_id";

foreach (['dashboard_operations', 'website_operations', 'app_operations'] as $table) {
    $res = $conn->query("SELECT id, project_id, created_date FROM `$table` WHERE project_id = $project_id ORDER BY id");
    echo "<h3>$table (project_id = $project_id)</h3>";
    echo "Row count: " . $res->num_rows . "<br>";
    while ($row = $res->fetch_assoc()) {
        echo "id={$row['id']} created_date={$row['created_date']}<br>";
    }

    // Also show: is there data under ANY other project_id, in case it went to the wrong one
    $other = $conn->query("SELECT project_id, COUNT(*) c FROM `$table` GROUP BY project_id");
    echo "<em>All project_ids with rows in this table:</em><br>";
    while ($row = $other->fetch_assoc()) {
        echo "project_id={$row['project_id']}: {$row['c']} rows<br>";
    }
}
