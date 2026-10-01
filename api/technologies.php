<?php
/**
 * /api/technologies.php
 *
 * GET    /api/technologies.php               -> paginated list (?page=&per_page=&project_id=)
 * GET    /api/technologies.php?id=5          -> single record
 * POST   /api/technologies.php               -> create (JSON body)   [requires "write" scope]
 * PUT    /api/technologies.php?id=5          -> update (JSON body)   [requires "write" scope]
 * DELETE /api/technologies.php?id=5          -> delete                [requires "write" scope]
 */

require_once __DIR__ . '/../common/api_auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;

function tech_row(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare("SELECT * FROM technologies WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

switch ($method) {
    case 'GET':
        if ($id) {
            $row = tech_row($conn, $id);
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

        $countStmt = $conn->prepare("SELECT COUNT(*) AS c FROM technologies WHERE $where");
        if ($types !== '') { $countStmt->bind_param($types, ...$params); }
        $countStmt->execute();
        $total = (int) $countStmt->get_result()->fetch_assoc()['c'];
        $countStmt->close();

        $stmt = $conn->prepare("SELECT * FROM technologies WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?");
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

        $name = trim($body['name'] ?? '');
        if ($name === '') {
            api_send_error(422, 'name is required.');
        }
        $tech_type  = trim($body['tech_type'] ?? '');
        $version    = trim($body['version'] ?? '');
        $project_id = !empty($body['project_id']) ? (int) $body['project_id'] : null;

        $stmt = $conn->prepare(
            "INSERT INTO technologies (project_id, tech_type, name, version) VALUES (?, ?, ?, ?)"
        );
        $stmt->bind_param('isss', $project_id, $tech_type, $name, $version);
        $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();

        api_send_success(tech_row($conn, $newId), 201);
        break;

    case 'PUT':
        api_require_scope('write');
        if (!$id) {
            api_send_error(400, 'id query parameter is required.');
        }
        $current = tech_row($conn, $id);
        if (!$current) {
            api_send_error(404, 'Record not found.');
        }

        $body = api_json_body();

        $name       = trim($body['name'] ?? $current['name']);
        $tech_type  = trim($body['tech_type'] ?? $current['tech_type']);
        $version    = trim($body['version'] ?? $current['version']);
        $project_id = array_key_exists('project_id', $body) ? (int) $body['project_id'] : $current['project_id'];

        $stmt = $conn->prepare(
            "UPDATE technologies SET project_id=?, tech_type=?, name=?, version=? WHERE id=?"
        );
        $stmt->bind_param('isssi', $project_id, $tech_type, $name, $version, $id);
        $stmt->execute();
        $stmt->close();

        api_send_success(tech_row($conn, $id));
        break;

    case 'DELETE':
        api_require_scope('write');
        if (!$id) {
            api_send_error(400, 'id query parameter is required.');
        }
        $stmt = $conn->prepare("DELETE FROM technologies WHERE id = ?");
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
