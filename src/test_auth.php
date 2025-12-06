<?php
/**
 * Manual Test Script for Authentication System
 * 
 * This script tests the authentication functionality:
 * - User creation
 * - Login validation
 * - Session management
 */

require_once 'config.php';

echo "=== CAP System Authentication Test ===\n\n";

// Test 1: Create a test user
echo "Test 1: Creating test user...\n";
$testEmail = 'test@example.com';
$testPassword = 'password123';
$testName = 'Test User';

// Check if user already exists
$existingUser = getUserByEmail($dbh, $testEmail);
if ($existingUser) {
    echo "✓ Test user already exists (ID: {$existingUser['id']})\n";
    $testUserId = $existingUser['id'];
} else {
    $testUserId = createUser($dbh, $testEmail, $testPassword, $testName);
    if ($testUserId) {
        echo "✓ Test user created successfully (ID: $testUserId)\n";
    } else {
        echo "✗ Failed to create test user\n";
        exit(1);
    }
}

// Test 2: Validate email
echo "\nTest 2: Email validation...\n";
if (validateEmail($testEmail)) {
    echo "✓ Valid email format\n";
} else {
    echo "✗ Invalid email format\n";
}

if (!validateEmail('invalid-email')) {
    echo "✓ Invalid email correctly rejected\n";
} else {
    echo "✗ Invalid email incorrectly accepted\n";
}

// Test 3: Validate password
echo "\nTest 3: Password validation...\n";
if (validatePassword($testPassword)) {
    echo "✓ Valid password (6+ characters)\n";
} else {
    echo "✗ Valid password rejected\n";
}

if (!validatePassword('short')) {
    echo "✓ Short password correctly rejected\n";
} else {
    echo "✗ Short password incorrectly accepted\n";
}

// Test 4: Validate name
echo "\nTest 4: Name validation...\n";
if (validateName($testName)) {
    echo "✓ Valid name\n";
} else {
    echo "✗ Valid name rejected\n";
}

if (!validateName('')) {
    echo "✓ Empty name correctly rejected\n";
} else {
    echo "✗ Empty name incorrectly accepted\n";
}

// Test 5: Get user by email
echo "\nTest 5: Retrieving user by email...\n";
$user = getUserByEmail($dbh, $testEmail);
if ($user && $user['email'] === $testEmail && $user['name'] === $testName) {
    echo "✓ User retrieved successfully\n";
    echo "  - Email: {$user['email']}\n";
    echo "  - Name: {$user['name']}\n";
} else {
    echo "✗ Failed to retrieve user\n";
}

// Test 6: Password verification (plain text for prototype)
echo "\nTest 6: Password verification...\n";
if ($user && $user['password'] === $testPassword) {
    echo "✓ Password matches\n";
} else {
    echo "✗ Password does not match\n";
}

// Test 7: Duplicate email prevention
echo "\nTest 7: Duplicate email prevention...\n";
$duplicateUserId = createUser($dbh, $testEmail, 'different_password', 'Different Name');
if ($duplicateUserId === false) {
    echo "✓ Duplicate email correctly prevented\n";
} else {
    echo "✗ Duplicate email was not prevented\n";
}

// Test 8: Session functions
echo "\nTest 8: Session management...\n";
setUserSession($testUserId);
if (isLoggedIn() && getCurrentUserId() === $testUserId) {
    echo "✓ Session set correctly\n";
    echo "  - User ID in session: " . getCurrentUserId() . "\n";
} else {
    echo "✗ Session not set correctly\n";
}

$currentUser = getCurrentUser($dbh);
if ($currentUser && $currentUser['id'] === $testUserId) {
    echo "✓ Current user retrieved from session\n";
} else {
    echo "✗ Failed to retrieve current user\n";
}

destroyUserSession();
if (!isLoggedIn()) {
    echo "✓ Session destroyed correctly\n";
} else {
    echo "✗ Session not destroyed\n";
}

echo "\n=== All Authentication Tests Completed ===\n";
?>
