<?php
/**
 * /api/website_work.php
 *
 * GET    /api/website_work.php               -> paginated list (?page=&per_page=&project_id=&status=)
 * GET    /api/website_work.php?id=5          -> single record
 * POST   /api/website_work.php               -> create (JSON body)   [requires "write" scope]
 * PUT    /api/website_work.php?id=5          -> update (JSON body)   [requires "write" scope]
 * DELETE /api/website_work.php?id=5          -> delete                [requires "write" scope]
 */

require_once __DIR__ . '/../common/api_auth.php';

$table       = 'website_work';
$nameColumn  = 'page_name';
$validStatus = ['Designed', 'Developed', 'Pending'];

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;

function work_row(mysqli $conn, string $table, int $id): ?array
{
    $stmt = $conn->prepare("SELECT * FROM `$table` WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

switch ($method) {
    case 'GET':
        if ($id) {
            $row = work_row($conn, $table, $id);
            if (!$row) {
                api_send_error(404, 'Record not found.');
            }
            api_send_success($row);
        }

        [$page, $perPage, $offset] = api_pagination_params();

        $where  = '1=1';
        $types  = '';
        $params = [];
        if (!empty($_GET['project_id'])) {
            $where   .= ' AND project_id = ?';
            $types   .= 'i';
            $params[] = (int) $_GET['project_id'];
        }
        if (!empty($_GET['status']) && in_array($_GET['status'], $validStatus, true)) {
            $where   .= ' AND status = ?';
            $types   .= 's';
            $params[] = $_GET['status'];
        }

        $countStmt = $conn->prepare("SELECT COUNT(*) AS c FROM `$table` WHERE $where");
        if ($types !== '') { $countStmt->bind_param($types, ...$params); }
        $countStmt->execute();
        $total = (int) $countStmt->get_result()->fetch_assoc()['c'];
        $countStmt->close();

        $stmt = $conn->prepare("SELECT * FROM `$table` WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $bindTypes  = $types . 'ii';
        $bindParams = array_merge($params, [$perPage, $offset]);
        $stmt->bind_param($bindTypes, ...$bindParams);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $stmt->close();

        api_send_success([
            'items'       => $items,
            'page'        => $page,
            'per_page'    => $perPage,
            'total'       => $total,
            'total_pages' => (int) ceil($total / $perPage),
        ]);
        break;

    case 'POST':
        api_require_scope('write');
        $body = api_json_body();

        $name = trim($body[$nameColumn] ?? '');
        if ($name === '') {
            api_send_error(422, "$nameColumn is required.");
        }
        $status       = in_array($body['status'] ?? '', $validStatus, true) ? $body['status'] : 'Pending';
        $project_id   = !empty($body['project_id']) ? (int) $body['project_id'] : null;
        $created_date = $body['created_date'] ?? date('Y-m-d');
        $end_date     = $body['end_date'] ?? null;

        // Stamp which API key created this row, so the admin UI can show
        // "created via API" alongside items added through the browser.
        $stmt = $conn->prepare(
            "INSERT INTO `$table` (project_id, `$nameColumn`, status, created_date, end_date, last_api_key_id, last_api_touched_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param('issssi', $project_id, $name, $status, $created_date, $end_date, $api_key_id);
        $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();

        api_send_success(work_row($conn, $table, $newId), 201);
        break;

    case 'PUT':
        api_require_scope('write');
        if (!$id) {
            api_send_error(400, 'id query parameter is required.');
        }
        $current = work_row($conn, $table, $id);
        if (!$current) {
            api_send_error(404, 'Record not found.');
        }

        $body = api_json_body();

        $name         = trim($body[$nameColumn] ?? $current[$nameColumn]);
        $status       = in_array($body['status'] ?? '', $validStatus, true) ? $body['status'] : $current['status'];
        $project_id   = array_key_exists('project_id', $body) ? (int) $body['project_id'] : $current['project_id'];
        $created_date = $body['created_date'] ?? $current['created_date'];
        $end_date     = $body['end_date'] ?? $current['end_date'];

        // Every API-driven update re-stamps the key that touched it, so the
        // admin UI always reflects the most recent API caller, not just the creator.
        $stmt = $conn->prepare(
            "UPDATE `$table` SET project_id=?, `$nameColumn`=?, status=?, created_date=?, end_date=?, last_api_key_id=?, last_api_touched_at=NOW() WHERE id=?"
        );
        $stmt->bind_param('issssii', $project_id, $name, $status, $created_date, $end_date, $api_key_id, $id);
        $stmt->execute();
        $stmt->close();

        api_send_success(work_row($conn, $table, $id));
        break;

    case 'DELETE':
        api_require_scope('write');
        if (!$id) {
            api_send_error(400, 'id query parameter is required.');
        }
        $stmt = $conn->prepare("DELETE FROM `$table` WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $deleted = $stmt->affected_rows > 0;
        $stmt->close();

        if (!$deleted) {
            api_send_error(404, 'Record not found.');
        }
        api_send_success(['deleted' => true, 'id' => $id]);
        break;

    default:
        api_send_error(405, 'Method not allowed.');
}
