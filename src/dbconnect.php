<?php
/**
 * Database Connection File for CAP System
 * 
 * This file establishes a PDO connection to the MySQL database.
 * Include this file in any script that needs database access.
 * 
 * Requirements: 10.1, 10.3, 10.4, 10.5, 10.6
 */

// Database configuration
$dsn = 'mysql:host=db;dbname=posse;charset=utf8mb4';
$user = 'root';
$password = 'root';

try {
    // Create PDO instance with error mode set to exception
    $dbh = new PDO($dsn, $user, $password);
    
    // Set PDO error mode to exception for better error handling
    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to associative array
    $dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Disable emulated prepared statements for better security
    $dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    
} catch (PDOException $e) {
    // Log error and display user-friendly message
    error_log('Database connection error: ' . $e->getMessage());
    http_response_code(500);
    die('データベース接続エラーが発生しました。しばらくしてから再度お試しください。');
}
?>