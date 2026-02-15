<?php
/**
 * Logout Script
 * Campus Room Allocation System
 */

require_once 'session.php';
require_once '../config/database.php';

// Log the logout activity
if (isLoggedIn()) {
    $user_id = getCurrentUserId();
    $logSql = "INSERT INTO activity_logs (user_id, action, description, ip_address) 
              VALUES (?, 'logout', 'User logged out', ?)";
    executeQuery($logSql, [$user_id, $_SERVER['REMOTE_ADDR']]);
}

// Destroy session
destroyUserSession();

// Redirect to login with success message
header('Location: login.php?success=logout');
exit();
?>