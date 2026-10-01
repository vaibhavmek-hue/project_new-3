<?php
/**
 * /api/clients.php
 *
 * GET    /api/clients.php               -> paginated list (?page=&per_page=&status=)
 * GET    /api/clients.php?id=5          -> single client
 * POST   /api/clients.php               -> create (JSON body)   [requires "write" scope]
 * PUT    /api/clients.php?id=5          -> update (JSON body)   [requires "write" scope]
 * DELETE /api/clients.php?id=5          -> delete                [requires "write" scope]
 */

require_once __DIR__ . '/../common/api_auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int) $_GET['id'] : null;

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $client = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$client) {
                api_send_error(404, 'Client not found.');
            }
            api_send_success($client);
        }

        [$page, $perPage, $offset] = api_pagination_params();

        $where  = '1=1';
        $types  = '';
        $params = [];
        if (!empty($_GET['status']) && in_array($_GET['status'], ['Active', 'Inactive'], true)) {
            $where   .= ' AND status = ?';
            $types   .= 's';
            $params[] = $_GET['status'];
        }

        $countStmt = $conn->prepare("SELECT COUNT(*) AS c FROM clients WHERE $where");
        if ($types !== '') { $countStmt->bind_param($types, ...$params); }
        $countStmt->execute();
        $total = (int) $countStmt->get_result()->fetch_assoc()['c'];
        $countStmt->close();

        $sql = "SELECT * FROM clients WHERE $where ORDER BY created_at DESC LIMIT ? OFFSET ?";
        $stmt = $conn->prepare($sql);
        $bindTypes  = $types . 'ii';
        $bindParams = array_merge($params, [$perPage, $offset]);
        $stmt->bind_param($bindTypes, ...$bindParams);
        $stmt->execute();
        $result = $stmt->get_result();
        $clients = [];
        while ($row = $result->fetch_assoc()) {
            $clients[] = $row;
        }
        $stmt->close();

        api_send_success([
            'items'      => $clients,
            'page'       => $page,
            'per_page'   => $perPage,
            'total'      => $total,
            'total_pages'=> (int) ceil($total / $perPage),
        ]);
        break;

    case 'POST':
        api_require_scope('write');
        $body = api_json_body();

        $client_name = trim($body['client_name'] ?? '');
        if ($client_name === '') {
            api_send_error(422, 'client_name is required.');
        }

        $organization_name = trim($body['organization_name'] ?? '');
        $contact_person     = trim($body['contact_person'] ?? '');
        $designation        = trim($body['designation'] ?? '');
        $mobile             = trim($body['mobile'] ?? '');
        $email              = trim($body['email'] ?? '');
        $address            = trim($body['address'] ?? '');
        $state              = trim($body['state'] ?? '');
        $pin_code           = trim($body['pin_code'] ?? '');
        $status             = in_array($body['status'] ?? '', ['Active', 'Inactive'], true) ? $body['status'] : 'Active';

        if ($mobile !== '' && !preg_match('/^[0-9]{10}$/', $mobile)) {
            api_send_error(422, 'mobile must be exactly 10 digits.');
        }

        $stmt = $conn->prepare(
            "INSERT INTO clients (client_name, organization_name, contact_person, designation, mobile, email, address, state, pin_code, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'ssssssssss',
            $client_name, $organization_name, $contact_person, $designation,
            $mobile, $email, $address, $state, $pin_code, $status
        );
        $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();

        $stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->bind_param('i', $newId);
        $stmt->execute();
        $client = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        api_send_success($client, 201);
        break;

    case 'PUT':
        api_require_scope('write');
        if (!$id) {
            api_send_error(400, 'id query parameter is required.');
        }

        $stmt = $conn->prepare("SELECT id FROM clients WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$exists) {
            api_send_error(404, 'Client not found.');
        }

        $body = api_json_body();

        // Partial update: keep existing values for any field not sent.
        $stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $current = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $client_name       = trim($body['client_name'] ?? $current['client_name']);
        $organization_name = trim($body['organization_name'] ?? $current['organization_name']);
        $contact_person     = trim($body['contact_person'] ?? $current['contact_person']);
        $designation        = trim($body['designation'] ?? $current['designation']);
        $mobile             = trim($body['mobile'] ?? $current['mobile']);
        $email              = trim($body['email'] ?? $current['email']);
        $address            = trim($body['address'] ?? $current['address']);
        $state              = trim($body['state'] ?? $current['state']);
        $pin_code           = trim($body['pin_code'] ?? $current['pin_code']);
        $status             = in_array($body['status'] ?? '', ['Active', 'Inactive'], true) ? $body['status'] : $current['status'];

        if ($mobile !== '' && !preg_match('/^[0-9]{10}$/', $mobile)) {
            api_send_error(422, 'mobile must be exactly 10 digits.');
        }

        $stmt = $conn->prepare(
            "UPDATE clients SET client_name=?, organization_name=?, contact_person=?, designation=?, mobile=?, email=?, address=?, state=?, pin_code=?, status=? WHERE id=?"
        );
        $stmt->bind_param(
            'ssssssssssi',
            $client_name, $organization_name, $contact_person, $designation,
            $mobile, $email, $address, $state, $pin_code, $status, $id
        );
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("SELECT * FROM clients WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $client = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        api_send_success($client);
        break;

    case 'DELETE':
        api_require_scope('write');
        if (!$id) {
            api_send_error(400, 'id query parameter is required.');
        }

        $stmt = $conn->prepare("DELETE FROM clients WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $deleted = $stmt->affected_rows > 0;
        $stmt->close();

        if (!$deleted) {
            api_send_error(404, 'Client not found.');
        }
        api_send_success(['deleted' => true, 'id' => $id]);
        break;

    default:
        api_send_error(405, 'Method not allowed.');
}
