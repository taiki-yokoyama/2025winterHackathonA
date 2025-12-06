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
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>サインアップ - CAPシステム</title>
    <link rel="stylesheet" href="assets/styles/common.css">
    <style>
        .auth-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .auth-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        .form-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .form-input:focus {
            outline: none;
            border-color: #4CAF50;
        }
        .btn-submit {
            width: 100%;
            padding: 12px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-submit:hover {
            background: #45a049;
        }
        .error-messages {
            background: #ffebee;
            border: 1px solid #f44336;
            color: #c62828;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .error-messages ul {
            margin: 0;
            padding-left: 20px;
        }
        .auth-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        .auth-link a {
            color: #4CAF50;
            text-decoration: none;
        }
        .auth-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <h1 class="auth-title">サインアップ</h1>
        
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
            
            <div class="form-group">
                <label for="name" class="form-label">名前</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    class="form-input" 
                    value="<?php echo sanitizeOutput($formData['name'] ?? ''); ?>"
                    required
                >
            </div>
            
            <button type="submit" class="btn-submit">登録</button>
        </form>
        
        <div class="auth-link">
            既にアカウントをお持ちですか？ <a href="login.php">ログイン</a>
        </div>
    </div>
</body>
</html>
