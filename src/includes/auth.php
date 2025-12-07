<?php
/**
 * Authentication Functions for CAP System
 * 
 * This file contains helper functions for user authentication,
 * session management, and access control.
 */

/**
 * Check if user is logged in
 * 
 * @return bool True if user is logged in, false otherwise
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Require authentication for protected pages
 * Redirects to login page if user is not logged in
 */
function requireAuth() {
    if (!isLoggedIn()) {
        $_SESSION['error'] = 'ログインが必要です。';
        header('Location: login.php');
        exit;
    }
}

/**
 * Get current logged-in user ID
 * 
 * @return int|null User ID or null if not logged in
 */
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current logged-in user data
 * 
 * @param PDO $dbh Database connection
 * @return array|null User data or null if not logged in
 */
function getCurrentUser($dbh) {
    $userId = getCurrentUserId();
    if (!$userId) {
        return null;
    }
    
    try {
        $stmt = $dbh->prepare('SELECT id, email, name, team_id, created_at FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Error fetching current user: ' . $e->getMessage());
        return null;
    }
}

/**
 * Set user session after successful login/signup
 * 
 * @param int $userId User ID to set in session
 */
function setUserSession($userId) {
    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
}

/**
 * Destroy user session (logout)
 */
function destroyUserSession() {
    // Unset all session variables
    $_SESSION = array();
    
    // Destroy the session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy the session
    session_destroy();
}
?>
