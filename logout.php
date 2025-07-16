<?php
session_start();

// Optional: check role before destroying session
$redirectTarget = 'index.php'; // Default redirect

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $redirectTarget = 'admin.php';
}

// Clear session
$_SESSION = array();
session_destroy();

// Redirect
header("Location: $redirectTarget");
exit;
?>
