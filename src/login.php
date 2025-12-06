<?php
/**
 * Login Page for CAP System
 * 
 * Handles user authentication with email and password.
 * Requirements: 2.1, 2.2, 2.3, 2.4
 */

require_once 'config.php';

// If already logged in, redirect to top page
if (isLoggedIn()) {
    header('Location: top.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $errors = [];
    
    // Validate input (Requirement 2.1)
    if (!validateEmail($email)) {
        $errors[] = 'メールアドレスを入力してください。';
    }
    
    if (empty($password)) {
        $errors[] = 'パスワードを入力してください。';
    }
    
    // Authenticate user
    if (empty($errors)) {
        $user = getUserByEmail($dbh, $email);
        
        // Check if user exists and password matches (Requirement 2.1, 2.3)
        // Note: Using plain text password comparison for prototype
        if ($user && $user['password'] === $password) {
            // Start session and redirect to top page (Requirement 2.2, 2.4)
            setUserSession($user['id']);
            header('Location: top.php');
            exit;
        } else {
            // Authentication failed (Requirement 2.3)
            $errors[] = 'メールアドレスまたはパスワードが正しくありません。';
        }
    }
    
    // Store errors in session for display
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = ['email' => $email];
    }
}

// Get errors and form data from session
$errors = $_SESSION['errors'] ?? [];
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['errors'], $_SESSION['form_data']);

// Set page title
$pageTitle = 'ログイン';

// Include header
include 'includes/header.php';
?>
    <div class="auth-container">
        <h1 class="auth-title">ログイン</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo sanitizeOutput($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="login.php">
            <div class="form-group">
                <label for="email" class="form-label">メールアドレス</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-input" 
                    value="<?php echo sanitizeOutput($formData['email'] ?? ''); ?>"
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="password" class="form-label">パスワード</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-input" 
                    required
                >
            </div>
            
            <button type="submit" class="btn-submit">ログイン</button>
        </form>
        
        <div class="auth-link">
            アカウントをお持ちでないですか？ <a href="signup.php">サインアップ</a>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>
