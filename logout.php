<?php
// Always start the session so we can actually clear it, even if the
// browser only sent a stale/expired session cookie.
session_start();

// Wipe all session data
$_SESSION = [];

// Remove the session cookie itself (not just the server-side data),
// so the browser can't keep re-sending an old session id.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy the session on the server
session_destroy();

// Prevent the browser from serving a cached copy of the previous page
// (fixes "click Back after logout and the dashboard still shows")
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

header("Location: loginpage.php");
exit;
