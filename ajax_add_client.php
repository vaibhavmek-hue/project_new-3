<?php
// Lightweight endpoint used by the "+ Add New Client" option on the
// Add Project / Edit Project client dropdowns. Creates a client from a
// small inline modal (no full page navigation) and returns it as JSON
// so the calling page can drop it straight into the <select>.
require_once 'common/auth_check.php';
include 'db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$client_name       = trim($_POST['client_name'] ?? '');
$organization_name = trim($_POST['organization_name'] ?? '');
$email              = trim($_POST['email'] ?? '');
$mobile             = trim($_POST['mobile'] ?? '');

if ($client_name === '') {
    echo json_encode(['success' => false, 'message' => 'Client Name is required.']);
    exit;
}
if ($mobile !== '' && !preg_match('/^[0-9]{10}$/', $mobile)) {
    echo json_encode(['success' => false, 'message' => 'Mobile Number must be exactly 10 digits, numbers only.']);
    exit;
}

$stmt = $conn->prepare(
    "INSERT INTO clients (client_name, organization_name, contact_person, designation, email, mobile, address, state, pin_code, status, created_at)
     VALUES (?, ?, '', '', ?, ?, '', '', '', 'Active', NOW())"
);
$stmt->bind_param('ssss', $client_name, $organization_name, $email, $mobile);

if ($stmt->execute()) {
    $new_id = $stmt->insert_id;
    $stmt->close();
    $label = $client_name . ($organization_name !== '' ? ' — ' . $organization_name : '');
    echo json_encode(['success' => true, 'id' => $new_id, 'label' => $label]);
} else {
    $stmt->close();
    echo json_encode(['success' => false, 'message' => 'Could not save the client. Please try again.']);
}
