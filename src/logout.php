<?php
/**
 * Logout Page for CAP System
 * 
 * Handles user logout by destroying the session.
 * Requirement: 2.5
 */

require_once 'config.php';

// Destroy user session (Requirement 2.5)
destroyUserSession();

// Redirect to login page
header('Location: login.php');
exit;
?>
