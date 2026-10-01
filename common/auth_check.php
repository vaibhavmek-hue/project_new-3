<?php
/**
 * Auth guard. Include this as the very first line of any page that
 * should only be visible to a logged-in user:
 *
 *     require_once 'common/auth_check.php';
 *
 * It must run before any HTML/output, since it may send a redirect header.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit;
}

// Stop the browser from caching protected pages, so hitting Back after
// logout can't show a stale copy of the dashboard.
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
