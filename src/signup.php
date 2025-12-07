<?php
/**
 * Signup Page for CAP System
 * 
 * Handles user registration with email, password, and name.
 * Requirements: 1.1, 1.2, 1.3, 1.4
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
    $name = trim($_POST['name'] ?? '');
    
    $errors = [];
    
    // Validation (Requirement 1.1)
    if (!validateEmail($email)) {
        $errors[] = '有効なメールアドレスを入力してください。';
    }
    
    if (!validatePassword($password)) {
        $errors[] = 'パスワードは6文字以上で入力してください。';
    }
    
    if (!validateName($name)) {
        $errors[] = '名前を入力してください。';
    }
    
    // Check if email already exists (Requirement 1.3)
    if (empty($errors)) {
        $existingUser = getUserByEmail($dbh, $email);
        if ($existingUser) {
            $errors[] = 'このメールアドレスは既に登録されています。';
        }
    }
    
    // If validation passes, create user
    if (empty($errors)) {
        // Create user (Requirement 1.2)
        $userId = createUser($dbh, $email, $password, $name);
        
        if ($userId) {
            // Set session and redirect to top page (Requirement 1.4)
            setUserSession($userId);
            header('Location: top.php');
            exit;
        } else {
            $errors[] = 'ユーザー登録に失敗しました。もう一度お試しください。';
        }
    }
    
    // Store errors in session for display
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = ['email' => $email, 'name' => $name];
    }
}

// Get errors and form data from session
$errors = $_SESSION['errors'] ?? [];
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['errors'], $_SESSION['form_data']);

// Set page title
$pageTitle = 'サインアップ';

// Include header
include 'includes/header.php';
?>
    <div class="auth-container">
        <h1 class="auth-title" style="display: flex; align-items: center; justify-content: center; gap: 10px;">
            <i data-lucide="user-plus" style="width: 32px; height: 32px; color: #f3c7c4;"></i>
            <span>サインアップ</span>
        </h1>
        
        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo sanitizeOutput($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="signup.php">
            <div class="form-group">
                <label for="email" class="form-label" style="display: flex; align-items: center; gap: 6px;">
                    <i data-lucide="mail" style="width: 16px; height: 16px;"></i>
                    <span>メールアドレス</span>
                </label>
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
                <label for="password" class="form-label" style="display: flex; align-items: center; gap: 6px;">
                    <i data-lucide="lock" style="width: 16px; height: 16px;"></i>
                    <span>パスワード</span>
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-input" 
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="name" class="form-label" style="display: flex; align-items: center; gap: 6px;">
                    <i data-lucide="user" style="width: 16px; height: 16px;"></i>
                    <span>名前</span>
                </label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    class="form-input" 
                    value="<?php echo sanitizeOutput($formData['name'] ?? ''); ?>"
                    required
                >
            </div>
            
            <button type="submit" class="btn-submit" style="display: flex; align-items: center; justify-content: center; gap: 8px; width: 100%;">
                <i data-lucide="user-plus" style="width: 18px; height: 18px;"></i>
                <span>登録</span>
            </button>
        </form>
        
        <div class="auth-link">
            既にアカウントをお持ちですか？ <a href="login.php">ログイン</a>
        </div>
    </div>
    
    <script>
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>

<?php include 'includes/footer.php'; ?>
