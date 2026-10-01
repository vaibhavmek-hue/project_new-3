<?php
/**
 * Shared JSON response helpers so every /api/*.php endpoint replies in
 * the same consistent shape:
 *
 *   success -> { "success": true,  "data": ... }
 *   error   -> { "success": false, "message": "..." }
 *
 * Include this once per request (api_auth.php already does, so most
 * endpoints never need to include it directly).
 */

if (!headers_sent()) {
    header('Content-Type: application/json');
}

function api_send_success($data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode(['success' => true, 'data' => $data], JSON_PRETTY_PRINT);
    exit;
}

function api_send_error(int $status, string $message): void
{
    http_response_code($status);
    echo json_encode(['success' => false, 'message' => $message], JSON_PRETTY_PRINT);
    exit;
}

/**
 * Reads pagination params from the query string with sane bounds, so a
 * caller can't accidentally (or deliberately) ask for a huge page size.
 */
function api_pagination_params(): array
{
    $page    = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $perPage = isset($_GET['per_page']) ? (int) $_GET['per_page'] : 20;
    $perPage = max(1, min(100, $perPage));

    return [$page, $perPage, ($page - 1) * $perPage];
}

/** Reads and decodes a JSON request body into an assoc array (POST/PUT). */
function api_json_body(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}
