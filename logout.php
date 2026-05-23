<?php
// logout.php – bulletproof logout & redirect to index
ob_start();
require_once __DIR__ . '/common/config.php';

// Destroy session data
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Now redirect to homepage
header('Location: index.php');
exit;
?>