<?php
/**
 * Users List Page for CAP System
 * 
 * Displays all users with links to their timelines.
 * Requirements: 9.1, 9.2, 9.3
 */

require_once 'config.php';

// Require authentication
requireAuth();

// Get current user
$currentUser = getCurrentUser($dbh);

// Get all users (Requirement 9.1)
$users = getAllUsers($dbh);

// Get success message from session
$successMessage = $_SESSION['success'] ?? null;
unset($_SESSION['success']);

// Set page title
$pageTitle = 'ユーザー一覧';

// Include header
include 'includes/header.php';
?>

    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 2px solid #eee;
            margin-bottom: 30px;
        }
        .header h1 {
            margin: 0;
            color: #333;
        }
        .nav {
            display: flex;
            gap: 20px;
        }
        .nav a {
            text-decoration: none;
            color: #2196F3;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background 0.3s;
        }
        .nav a:hover {
            background: #e3f2fd;
        }
        .page-header {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .page-header h2 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .page-header p {
            margin: 0;
            color: #666;
        }
        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .users-section {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        .user-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            background: #fafafa;
            transition: all 0.3s;
            cursor: pointer;
        }
        .user-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateY(-2px);
            background: #fff;
        }
        .user-card.current-user {
            border: 2px solid #4CAF50;
            background: #f1f8f4;
        }
        .user-card.current-user:hover {
            background: #e8f5e9;
        }
        .user-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
            flex-shrink: 0;
        }
        .user-card.current-user .user-avatar {
            background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
        }
        .user-info {
            flex: 1;
            min-width: 0;
        }
        .user-name {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin: 0 0 5px 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .user-email {
            font-size: 14px;
            color: #666;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .user-badge {
            display: inline-block;
            padding: 4px 10px;
            background: #4CAF50;
            color: white;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            margin-top: 5px;
        }
        .user-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #e0e0e0;
        }
        .user-joined {
            font-size: 13px;
            color: #999;
        }
        .view-timeline-btn {
            padding: 8px 16px;
            background: #2196F3;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
            transition: background 0.3s;
            display: inline-block;
        }
        .view-timeline-btn:hover {
            background: #0b7dda;
        }
        .user-card.current-user .view-timeline-btn {
            background: #4CAF50;
        }
        .user-card.current-user .view-timeline-btn:hover {
            background: #45a049;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .users-count {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
            padding: 10px;
            background: #f0f0f0;
            border-radius: 4px;
            text-align: center;
        }
        .users-count strong {
            color: #2196F3;
            font-size: 18px;
        }
    </style>
        
        <div class="page-header">
            <h2 style="display: flex; align-items: center; gap: 10px;">
                <i data-lucide="users" style="width: 28px; height: 28px; color: #2196F3;"></i>
                <span>ユーザー一覧</span>
            </h2>
            <p>他のユーザーのTimelineを閲覧して、改善活動を参考にしましょう。</p>
        </div>
        
        <?php if ($successMessage): ?>
            <div class="success-message">
                <?php echo sanitizeOutput($successMessage); ?>
            </div>
        <?php endif; ?>
        
        <div class="users-section">
            <?php if (empty($users)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i data-lucide="users" style="width: 64px; height: 64px; color: #ccc;"></i>
                    </div>
                    <p>ユーザーが見つかりません。</p>
                </div>
            <?php else: ?>
                <div class="users-count">
                    登録ユーザー数: <strong><?php echo count($users); ?></strong>人
                </div>
                
                <!-- Requirement 9.1, 9.2: 全ユーザーの一覧を表示（名前、メールアドレス） -->
                <div class="users-grid">
                    <?php foreach ($users as $user): ?>
                        <?php $isCurrentUser = ($user['id'] === $currentUser['id']); ?>
                        <div class="user-card <?php echo $isCurrentUser ? 'current-user' : ''; ?>" 
                             onclick="window.location.href='timeline.php?user_id=<?php echo $user['id']; ?>'">
                            <div class="user-header">
                                <div class="user-avatar">
                                    <?php 
                                    // Display first character of name as avatar
                                    echo mb_substr($user['name'], 0, 1, 'UTF-8'); 
                                    ?>
                                </div>
                                <div class="user-info">
                                    <h3 class="user-name">
                                        <?php echo sanitizeOutput($user['name']); ?>
                                    </h3>
                                    <div class="user-email">
                                        <?php echo sanitizeOutput($user['email']); ?>
                                    </div>
                                    <?php if ($isCurrentUser): ?>
                                        <span class="user-badge">あなた</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="user-meta">
                                <span class="user-joined">
                                    登録日: <?php echo date('Y年m月d日', strtotime($user['created_at'])); ?>
                                </span>
                                <!-- Requirement 9.3: 各ユーザーのTimelineへのリンク -->
                                <a href="timeline.php?user_id=<?php echo $user['id']; ?>" 
                                   class="view-timeline-btn"
                                   onclick="event.stopPropagation();">
                                    <?php echo $isCurrentUser ? '自分のTimeline' : 'Timelineを見る'; ?> →
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

<?php include 'includes/footer.php'; ?>
