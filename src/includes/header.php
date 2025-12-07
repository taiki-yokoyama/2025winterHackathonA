<?php
/**
 * Common Header Component for CAP System
 * 
 * Displays navigation based on login status
 * Usage: include 'includes/header.php';
 */

// Ensure we have access to current user
if (!isset($currentUser)) {
    $currentUser = isLoggedIn() ? getCurrentUser($dbh) : null;
}

// Determine current page for active state
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? sanitizeOutput($pageTitle) . ' - CAPシステム' : 'CAPシステム'; ?></title>
    <link rel="stylesheet" href="assets/styles/cap-system.css">
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if (isset($additionalHeadContent)): ?>
        <?php echo $additionalHeadContent; ?>
    <?php endif; ?>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>
                <a href="<?php echo $currentUser ? 'top.php' : 'login.php'; ?>" style="text-decoration: none; color: inherit;">
                    CAPシステム
                </a>
            </h1>
            <nav class="nav">
                <?php if ($currentUser): ?>
                    <!-- Logged in navigation -->
                    <a href="top.php" <?php echo $currentPage === 'top.php' ? 'class="active"' : ''; ?>>
                        🏠 Top
                    </a>
                    <a href="users.php" <?php echo $currentPage === 'users.php' ? 'class="active"' : ''; ?>>
                        👥 ユーザー一覧
                    </a>
                    <a href="create_issue.php" <?php echo $currentPage === 'create_issue.php' ? 'class="active"' : ''; ?>>
                        ➕ 課題作成
                    </a>
                    <a href="create_cap.php" <?php echo $currentPage === 'create_cap.php' ? 'class="active"' : ''; ?>>
                        📝 CAP投稿
                    </a>
                    <a href="timeline.php?user_id=<?php echo $currentUser['id']; ?>" <?php echo $currentPage === 'timeline.php' ? 'class="active"' : ''; ?>>
                        📊 Timeline
                    </a>
                    <span style="color: #666; padding: 0 10px;">|</span>
                    <span style="color: #666; font-size: 14px;">
                        <?php echo sanitizeOutput($currentUser['name']); ?>さん
                    </span>
                    <a href="logout.php" style="color: #f44336;">
                        🚪 ログアウト
                    </a>
                <?php else: ?>
                    <!-- Not logged in navigation -->
                    <a href="login.php" <?php echo $currentPage === 'login.php' ? 'class="active"' : ''; ?>>
                        ログイン
                    </a>
                    <a href="signup.php" <?php echo $currentPage === 'signup.php' ? 'class="active"' : ''; ?>>
                        サインアップ
                    </a>
                <?php endif; ?>
            </nav>
        </header>
