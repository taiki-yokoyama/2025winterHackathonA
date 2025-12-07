<?php
/**
 * Validation Functions for CAP System
 * 
 * This file contains helper functions for input validation.
 */

/**
 * Validate email address
 * 
 * @param string $email Email address to validate
 * @return bool True if valid, false otherwise
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate password
 * 
 * @param string $password Password to validate
 * @param int $minLength Minimum password length (default: 6)
 * @return bool True if valid, false otherwise
 */
function validatePassword($password, $minLength = 6) {
    return strlen($password) >= $minLength;
}

/**
 * Validate required field (not empty)
 * 
 * @param string $value Value to validate
 * @return bool True if not empty, false otherwise
 */
function validateRequired($value) {
    return !empty(trim($value));
}

/**
 * Validate name (not empty and reasonable length)
 * 
 * @param string $name Name to validate
 * @param int $maxLength Maximum name length (default: 100)
 * @return bool True if valid, false otherwise
 */
function validateName($name, $maxLength = 100) {
    $trimmed = trim($name);
    return !empty($trimmed) && strlen($trimmed) <= $maxLength;
}

/**
 * Validate metric type
 * 
 * @param string $metricType Metric type to validate
 * @return bool True if valid, false otherwise
 */
function validateMetricType($metricType) {
    $validTypes = ['percentage', 'scale_5', 'numeric'];
    return in_array($metricType, $validTypes);
}

/**
 * Validate numeric value
 * 
 * @param mixed $value Value to validate
 * @return bool True if valid numeric, false otherwise
 */
function validateNumeric($value) {
    return is_numeric($value);
}

/**
 * Sanitize output for HTML display (XSS prevention)
 * 
 * @param string $value Value to sanitize
 * @return string Sanitized value
 */
function sanitizeOutput($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
