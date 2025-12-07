<?php
/**
 * Configuration File for CAP System
 * 
 * This file contains system-wide configuration settings.
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/dbconnect.php';

// Include helper functions
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/db_functions.php';

// Set timezone
date_default_timezone_set('Asia/Tokyo');

// Error reporting (for development)
// In production, set display_errors to 0
ini_set('display_errors', 1);
error_reporting(E_ALL);
?>
