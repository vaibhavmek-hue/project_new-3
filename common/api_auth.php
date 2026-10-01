<?php
/**
 * API key auth guard for external HTTP requests.
 *
 * This is completely separate from the browser session auth in
 * common/auth_check.php — that guard checks $_SESSION['user_id'] and
 * redirects to loginpage.php; this one checks an API key sent in a
 * header and returns JSON, so it never redirects and never touches
 * PHP sessions.
 *
 * Include this as the very first line of any /api/*.php endpoint:
 *
 *     require_once __DIR__ . '/../common/api_auth.php';
 *
 * On success it exposes:
 *   $api_key_id      (int)      the matched row in api_keys
 *   $api_user_id     (int)      the user_id that owns the key
 *   $api_key_scopes  (string[]) e.g. ['read','write']
 * and the helper api_require_scope('write') to gate write operations.
 *
 * On failure it sends a 401/429 JSON error and exits — the including
 * endpoint never runs past this file.
 */

require_once __DIR__ . '/api_response.php';
require_once __DIR__ . '/../db.php';

// Requests allowed per API key in any rolling 60-second window.
const API_RATE_LIMIT_PER_MINUTE = 60;

function api_extract_key(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    // Some servers (e.g. PHP-FPM behind certain configs) strip the
    // Authorization header from $_SERVER; getallheaders() catches those.
    if ($header === '' && function_exists('getallheaders')) {
        foreach (getallheaders() as $name => $value) {
            if (strcasecmp($name, 'Authorization') === 0) {
                $header = $value;
                break;
            }
        }
    }

    if ($header !== '' && stripos($header, 'Bearer ') === 0) {
        return trim(substr($header, 7));
    }

    if (!empty($_SERVER['HTTP_X_API_KEY'])) {
        return trim($_SERVER['HTTP_X_API_KEY']);
    }

    return null;
}

$rawKey = api_extract_key();

if ($rawKey === null || $rawKey === '') {
    api_send_error(401, 'Missing API key. Send it as "Authorization: Bearer <key>" or an "X-API-Key" header.');
}

// Keys are stored hashed — never in plaintext — same principle as
// password_verify() for user logins in login.php.
$keyHash = hash('sha256', $rawKey);

$stmt = $conn->prepare(
    "SELECT id, user_id, scopes, status, revoked_at FROM api_keys WHERE key_hash = ? LIMIT 1"
);
$stmt->bind_param('s', $keyHash);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row || $row['status'] !== 'Active' || $row['revoked_at'] !== null) {
    api_send_error(401, 'Invalid or revoked API key.');
}

$api_key_id     = (int) $row['id'];
$api_user_id    = (int) $row['user_id'];
$api_key_scopes = array_map('trim', explode(',', $row['scopes']));

/** Call from an endpoint before a write/delete to enforce scope. */
function api_require_scope(string $scope): void
{
    global $api_key_scopes;
    if (!in_array($scope, $api_key_scopes, true)) {
        api_send_error(403, "This API key does not have the '$scope' scope.");
    }
}

// ---------- Rate limiting ----------
$stmt = $conn->prepare(
    "SELECT COUNT(*) AS c FROM api_request_log WHERE api_key_id = ? AND requested_at >= (NOW() - INTERVAL 60 SECOND)"
);
$stmt->bind_param('i', $api_key_id);
$stmt->execute();
$requestsThisMinute = (int) $stmt->get_result()->fetch_assoc()['c'];
$stmt->close();

if ($requestsThisMinute >= API_RATE_LIMIT_PER_MINUTE) {
    api_send_error(429, 'Rate limit exceeded: max ' . API_RATE_LIMIT_PER_MINUTE . ' requests per minute per API key.');
}

// ---------- Log this request + bump last_used_at ----------
$endpoint = ($_SERVER['REQUEST_METHOD'] ?? '') . ' ' . ($_SERVER['REQUEST_URI'] ?? '');

$stmt = $conn->prepare("INSERT INTO api_request_log (api_key_id, endpoint, requested_at) VALUES (?, ?, NOW())");
$stmt->bind_param('is', $api_key_id, $endpoint);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare("UPDATE api_keys SET last_used_at = NOW() WHERE id = ?");
$stmt->bind_param('i', $api_key_id);
$stmt->execute();
$stmt->close();

// Housekeeping: occasionally prune log rows older than an hour so this
// table stays small. A ~2% chance per request is enough to keep it tidy
// without adding a cleanup query to every single call.
if (mt_rand(1, 50) === 1) {
    $conn->query("DELETE FROM api_request_log WHERE requested_at < (NOW() - INTERVAL 1 HOUR)");
}
