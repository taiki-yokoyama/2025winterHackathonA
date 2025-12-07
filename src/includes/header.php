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
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
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
            <h1 class="logo">
                <a href="<?php echo $currentUser ? 'top.php' : 'login.php'; ?>" style="text-decoration: none; color: inherit; display: flex; align-items: center; gap: 10px;">
                    <i data-lucide="target" style="width: 32px; height: 32px;"></i>
                    <span>CAPシステム</span>
                </a>
            </h1>
            <nav class="nav">
                <?php if ($currentUser): ?>
                    <!-- Logged in navigation -->
                    <a href="top.php" <?php echo $currentPage === 'top.php' ? 'class="active"' : ''; ?>>
                        <i data-lucide="home" class="nav-icon"></i>
                        <span>Top</span>
                    </a>
                    <a href="users.php" <?php echo $currentPage === 'users.php' ? 'class="active"' : ''; ?>>
                        <i data-lucide="users" class="nav-icon"></i>
                        <span>ユーザー一覧</span>
                    </a>
                    <a href="create_issue.php" <?php echo $currentPage === 'create_issue.php' ? 'class="active"' : ''; ?>>
                        <i data-lucide="plus-circle" class="nav-icon"></i>
                        <span>課題作成</span>
                    </a>
                    <a href="create_cap.php" <?php echo $currentPage === 'create_cap.php' ? 'class="active"' : ''; ?>>
                        <i data-lucide="edit" class="nav-icon"></i>
                        <span>CAP投稿</span>
                    </a>
                    <a href="timeline.php?user_id=<?php echo $currentUser['id']; ?>" <?php echo $currentPage === 'timeline.php' ? 'class="active"' : ''; ?>>
                        <i data-lucide="activity" class="nav-icon"></i>
                        <span>Timeline</span>
                    </a>
                    <span style="color: #666; padding: 0 10px;">|</span>
                    <span style="color: #666; font-size: 14px; display: flex; align-items: center; gap: 5px;">
                        <i data-lucide="user" style="width: 16px; height: 16px;"></i>
                        <?php echo sanitizeOutput($currentUser['name']); ?>さん
                    </span>
                    <a href="logout.php" style="color: #f44336;">
                        <i data-lucide="log-out" class="nav-icon"></i>
                        <span>ログアウト</span>
                    </a>
                <?php else: ?>
                    <!-- Not logged in navigation -->
                    <a href="login.php" <?php echo $currentPage === 'login.php' ? 'class="active"' : ''; ?>>
                        <i data-lucide="log-in" class="nav-icon"></i>
                        <span>ログイン</span>
                    </a>
                    <a href="signup.php" <?php echo $currentPage === 'signup.php' ? 'class="active"' : ''; ?>>
                        <i data-lucide="user-plus" class="nav-icon"></i>
                        <span>サインアップ</span>
                    </a>
                <?php endif; ?>
            </nav>
        </header>
        <script>
            // Initialize Lucide icons
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        </script>
