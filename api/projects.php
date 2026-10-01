<?php
/**
 * /api/projects.php
 *
 * GET    /api/projects.php               -> paginated list (?page=&per_page=&status=&client_id=)
 * GET    /api/projects.php?id=5          -> single project
 * POST   /api/projects.php               -> create (JSON body)   [requires "write" scope]
 * PUT    /api/projects.php?id=5          -> update (JSON body)   [requires "write" scope]
 * DELETE /api/projects.php?id=5          -> delete                [requires "write" scope]
 */

require_once __DIR__ . '/../common/api_auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;

$fields = ['project_name', 'client_id', 'client_name', 'email', 'phone', 'num_users',
           'start_date', 'end_date', 'description', 'status', 'progress', 'team_image'];

function project_row(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

switch ($method) {
    case 'GET':
        if ($id) {
            $project = project_row($conn, $id);
            if (!$project) {
                api_send_error(404, 'Project not found.');
            }
            api_send_success($project);
        }

        [$page, $perPage, $offset] = api_pagination_params();

        $where  = '1=1';
        $types  = '';
        $params = [];
        if (!empty($_GET['status']) && in_array($_GET['status'], ['In Progress', 'Completed', 'On Hold'], true)) {
            $where   .= ' AND status = ?';
            $types   .= 's';
            $params[] = $_GET['status'];
        }
        if (!empty($_GET['client_id'])) {
            $where   .= ' AND client_id = ?';
            $types   .= 'i';
            $params[] = (int) $_GET['client_id'];
        }

        $countStmt = $conn->prepare("SELECT COUNT(*) AS c FROM projects WHERE $where");
        if ($types !== '') { $countStmt->bind_param($types, ...$params); }
        $countStmt->execute();
        $total = (int) $countStmt->get_result()->fetch_assoc()['c'];
        $countStmt->close();

        $stmt = $conn->prepare("SELECT * FROM projects WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $bindTypes  = $types . 'ii';
        $bindParams = array_merge($params, [$perPage, $offset]);
        $stmt->bind_param($bindTypes, ...$bindParams);
        $stmt->execute();
        $result = $stmt->get_result();
        $projects = [];
        while ($row = $result->fetch_assoc()) {
            $projects[] = $row;
        }
        $stmt->close();

        api_send_success([
            'items'       => $projects,
            'page'        => $page,
            'per_page'    => $perPage,
            'total'       => $total,
            'total_pages' => (int) ceil($total / $perPage),
        ]);
        break;

    case 'POST':
        api_require_scope('write');
        $body = api_json_body();

        $project_name = trim($body['project_name'] ?? '');
        if ($project_name === '') {
            api_send_error(422, 'project_name is required.');
        }
        $status = in_array($body['status'] ?? '', ['In Progress', 'Completed', 'On Hold'], true) ? $body['status'] : 'In Progress';

        $client_id   = !empty($body['client_id']) ? (int) $body['client_id'] : null;
        $client_name = trim($body['client_name'] ?? '');
        $email       = trim($body['email'] ?? '');
        $phone       = trim($body['phone'] ?? '');
        $num_users   = isset($body['num_users']) ? (int) $body['num_users'] : null;
        $start_date  = $body['start_date'] ?? null;
        $end_date    = $body['end_date'] ?? null;
        $description = trim($body['description'] ?? '');
        $progress    = isset($body['progress']) ? max(0, min(100, (int) $body['progress'])) : 0;

        $stmt = $conn->prepare(
            "INSERT INTO projects (project_name, client_id, client_name, email, phone, num_users, start_date, end_date, description, status, progress)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        // project_name(s) client_id(i) client_name(s) email(s) phone(s)
        // num_users(i) start_date(s) end_date(s) description(s) status(s) progress(i)
        $stmt->bind_param(
            'sisssissssi',
            $project_name, $client_id, $client_name, $email, $phone,
            $num_users, $start_date, $end_date, $description, $status, $progress
        );
        $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();

        api_send_success(project_row($conn, $newId), 201);
        break;

    case 'PUT':
        api_require_scope('write');
        if (!$id) {
            api_send_error(400, 'id query parameter is required.');
        }
        $current = project_row($conn, $id);
        if (!$current) {
            api_send_error(404, 'Project not found.');
        }

        $body = api_json_body();

        $project_name = trim($body['project_name'] ?? $current['project_name']);
        $status       = in_array($body['status'] ?? '', ['In Progress', 'Completed', 'On Hold'], true) ? $body['status'] : $current['status'];
        $client_id    = array_key_exists('client_id', $body) ? (int) $body['client_id'] : $current['client_id'];
        $client_name  = trim($body['client_name'] ?? $current['client_name']);
        $email        = trim($body['email'] ?? $current['email']);
        $phone        = trim($body['phone'] ?? $current['phone']);
        $num_users    = array_key_exists('num_users', $body) ? (int) $body['num_users'] : $current['num_users'];
        $start_date   = $body['start_date'] ?? $current['start_date'];
        $end_date     = $body['end_date'] ?? $current['end_date'];
        $description  = trim($body['description'] ?? $current['description']);
        $progress     = array_key_exists('progress', $body) ? max(0, min(100, (int) $body['progress'])) : $current['progress'];

        $stmt = $conn->prepare(
            "UPDATE projects SET project_name=?, client_id=?, client_name=?, email=?, phone=?, num_users=?, start_date=?, end_date=?, description=?, status=?, progress=? WHERE id=?"
        );
        $stmt->bind_param(
            'sisssissssii',
            $project_name, $client_id, $client_name, $email, $phone,
            $num_users, $start_date, $end_date, $description, $status, $progress, $id
        );
        $stmt->execute();
        $stmt->close();

        api_send_success(project_row($conn, $id));
        break;

    case 'DELETE':
        api_require_scope('write');
        if (!$id) {
            api_send_error(400, 'id query parameter is required.');
        }
        $stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $deleted = $stmt->affected_rows > 0;
        $stmt->close();

        if (!$deleted) {
            api_send_error(404, 'Project not found.');
        }
        api_send_success(['deleted' => true, 'id' => $id]);
        break;

    default:
        api_send_error(405, 'Method not allowed.');
}
