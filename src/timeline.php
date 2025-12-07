<?php
/**
 * Timeline Page for CAP System
 * 
 * Displays a user's CAP posting history in chronological order.
 * Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 7.5
 */

require_once 'config.php';

// Require authentication
requireAuth();

// Get current user
$currentUser = getCurrentUser($dbh);

// Get target user ID from URL parameter (Requirement 6.1)
$targetUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $currentUser['id'];

// Get target user information
$targetUser = getUserById($dbh, $targetUserId);

if (!$targetUser) {
    $_SESSION['error'] = 'ユーザーが見つかりません。';
    header('Location: users.php');
    exit;
}

// Get selected issue ID for filtering (Requirement 6.5)
$selectedIssueId = isset($_GET['issue_id']) ? (int)$_GET['issue_id'] : null;

// Get target user's issues for tab display
$userIssues = getUserIssues($dbh, $targetUserId);

// Get CAPs for timeline (Requirement 6.1, 6.2, 6.3)
// Requirement 6.1: 対象ユーザーの全CAP投稿を時系列で取得
// Requirement 6.2: CAP投稿の表示（課題名、Check値、分析、改善方向、計画、投稿日時）
// Requirement 6.3: コメント数の表示
$caps = getCAPsForTimeline($dbh, $targetUserId, $selectedIssueId);

// Get success message from session
$successMessage = $_SESSION['success'] ?? null;
unset($_SESSION['success']);

// Check if viewing own timeline
$isOwnTimeline = ($targetUserId === $currentUser['id']);

// Set page title
$pageTitle = sanitizeOutput($targetUser['name']) . 'のTimeline';

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
            color: #f3c7c4;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background 0.3s;
        }
        .nav a:hover {
            background: #e3f2fd;
        }
        .timeline-header {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .timeline-header h2 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .timeline-header p {
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
        /* Issue tabs (Requirement 6.5) */
        .issue-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
        .issue-tab {
            padding: 10px 20px;
            background: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 4px 4px 0 0;
            text-decoration: none;
            color: #666;
            font-weight: bold;
            transition: all 0.3s;
        }
        .issue-tab:hover {
            background: #e0e0e0;
        }
        .issue-tab.active {
            background: #f3c7c4;
            color: white;
            border-color: #f3c7c4;
        }
        .issue-tab.all {
            background: #f3c7c4;
            color: white;
            border-color: #f3c7c4;
        }
        .issue-tab.all:hover {
            background: #e0a7a4;
        }
        /* CAP cards (Requirement 6.2) */
        .cap-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .cap-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .cap-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }
        .cap-issue-name {
            font-size: 20px;
            font-weight: bold;
            color: #f3c7c4;
            margin: 0 0 5px 0;
        }
        .cap-date {
            font-size: 14px;
            color: #999;
        }
        .cap-check-value {
            text-align: right;
        }
        .cap-check-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        .cap-check-number {
            font-size: 32px;
            font-weight: bold;
            color: #f3c7c4;
        }
        .cap-check-unit {
            font-size: 16px;
            color: #666;
            margin-left: 5px;
        }
        .cap-content {
            display: grid;
            gap: 20px;
        }
        .cap-section {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
        }
        .cap-section-title {
            font-weight: bold;
            color: #555;
            margin-bottom: 8px;
            font-size: 14px;
            text-transform: uppercase;
        }
        .cap-section-content {
            color: #333;
            line-height: 1.6;
            white-space: pre-wrap;
        }
        /* Comment section (Requirement 6.3, 6.6, 7.5) */
        .cap-comments {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f0f0f0;
        }
        .comments-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .comments-count {
            font-weight: bold;
            color: #666;
        }
        .comment-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .comment-item {
            background: #f0f7ff;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #f3c7c4;
        }
        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .comment-author {
            font-weight: bold;
            color: #f3c7c4;
        }
        .comment-date {
            font-size: 12px;
            color: #999;
        }
        .comment-content {
            color: #333;
            line-height: 1.6;
            white-space: pre-wrap;
        }
        .comment-form {
            margin-top: 15px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .comment-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            min-height: 80px;
            resize: vertical;
            box-sizing: border-box;
        }
        .comment-form button {
            margin-top: 10px;
            padding: 8px 20px;
            background: #f3c7c4;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .comment-form button:hover {
            background: #e0a7a4;
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
        .empty-state h3 {
            color: #666;
            margin-bottom: 10px;
        }
        .btn-create-cap {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: #f3c7c4;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
        }
        .btn-create-cap:hover {
            background: #e0a7a4;
        }
    </style>
        
        <div class="timeline-header">
            <h2><?php echo sanitizeOutput($targetUser['name']); ?>さんのTimeline</h2>
            <p>
                <?php if ($isOwnTimeline): ?>
                    あなたのCAP投稿履歴です。
                <?php else: ?>
                    <?php echo sanitizeOutput($targetUser['name']); ?>さんのCAP投稿履歴を閲覧しています。
                <?php endif; ?>
            </p>
        </div>
        
        <?php if ($successMessage): ?>
            <div class="success-message">
                <?php echo sanitizeOutput($successMessage); ?>
            </div>
        <?php endif; ?>
        
        <!-- Issue tabs for filtering (Requirement 6.5) -->
        <?php if (!empty($userIssues)): ?>
            <div class="issue-tabs">
                <a href="timeline.php?user_id=<?php echo $targetUserId; ?>" 
                   class="issue-tab <?php echo $selectedIssueId === null ? 'all active' : 'all'; ?>">
                    すべて
                </a>
                <?php foreach ($userIssues as $issue): ?>
                    <a href="timeline.php?user_id=<?php echo $targetUserId; ?>&issue_id=<?php echo $issue['id']; ?>" 
                       class="issue-tab <?php echo $selectedIssueId === $issue['id'] ? 'active' : ''; ?>">
                        <?php echo sanitizeOutput($issue['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- CAP list (Requirement 6.1, 6.2, 6.3) -->
        <?php if (empty($caps)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i data-lucide="file-text" style="width: 64px; height: 64px; color: #ccc;"></i>
                </div>
                <h3>CAP投稿がありません</h3>
                <?php if ($isOwnTimeline): ?>
                    <p>最初のCAP投稿を作成しましょう。</p>
                    <a href="create_cap.php" class="btn-create-cap" style="display: inline-flex; align-items: center; gap: 8px;">
                        <i data-lucide="edit" style="width: 18px; height: 18px;"></i>
                        <span>CAP投稿を作成</span>
                    </a>
                <?php else: ?>
                    <p><?php echo sanitizeOutput($targetUser['name']); ?>さんはまだCAP投稿をしていません。</p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="cap-list">
                <?php foreach ($caps as $cap): ?>
                    <div class="cap-card">
                        <!-- CAP header with issue name and date (Requirement 6.2) -->
                        <div class="cap-header">
                            <div>
                                <h3 class="cap-issue-name"><?php echo sanitizeOutput($cap['issue_name']); ?></h3>
                                <div class="cap-date">
                                    <?php echo date('Y年m月d日 H:i', strtotime($cap['created_at'])); ?>
                                </div>
                            </div>
                            <div class="cap-check-value">
                                <div class="cap-check-label">Check値</div>
                                <div>
                                    <span class="cap-check-number"><?php echo sanitizeOutput($cap['value']); ?></span>
                                    <?php if ($cap['unit']): ?>
                                        <span class="cap-check-unit"><?php echo sanitizeOutput($cap['unit']); ?></span>
                                    <?php elseif ($cap['metric_type'] === 'percentage'): ?>
                                        <span class="cap-check-unit">%</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- CAP content (Requirement 6.2) -->
                        <div class="cap-content">
                            <div class="cap-section">
                                <div class="cap-section-title" style="display: flex; align-items: center; gap: 6px;">
                                    <i data-lucide="bar-chart" style="width: 16px; height: 16px;"></i>
                                    <span>分析</span>
                                </div>
                                <div class="cap-section-content"><?php echo sanitizeOutput($cap['analysis']); ?></div>
                            </div>
                            
                            <div class="cap-section">
                                <div class="cap-section-title" style="display: flex; align-items: center; gap: 6px;">
                                    <i data-lucide="target" style="width: 16px; height: 16px;"></i>
                                    <span>改善方向</span>
                                </div>
                                <div class="cap-section-content"><?php echo sanitizeOutput($cap['improve_direction']); ?></div>
                            </div>
                            
                            <div class="cap-section">
                                <div class="cap-section-title" style="display: flex; align-items: center; gap: 6px;">
                                    <i data-lucide="clipboard-list" style="width: 16px; height: 16px;"></i>
                                    <span>計画</span>
                                </div>
                                <div class="cap-section-content"><?php echo sanitizeOutput($cap['plan']); ?></div>
                            </div>
                        </div>
                        
                        <!-- Comments section (Requirement 6.3, 6.6, 7.5) -->
                        <div class="cap-comments">
                            <div class="comments-header">
                                <div class="comments-count" style="display: flex; align-items: center; gap: 6px;">
                                    <i data-lucide="message-circle" style="width: 18px; height: 18px;"></i>
                                    <span>コメント (<?php echo $cap['comment_count']; ?>)</span>
                                </div>
                            </div>
                            
                            <?php
                            // Get comments for this CAP (Requirement 6.6, 7.5)
                            // Requirement 6.6: 1つのCAPに複数のコメントが存在する場合、全てのコメントを表示
                            // Requirement 7.5: 新しい順でのソート
                            $comments = getCommentsForCAP($dbh, $cap['id']);
                            ?>
                            
                            <?php if (!empty($comments)): ?>
                                <div class="comment-list">
                                    <?php foreach ($comments as $comment): ?>
                                        <div class="comment-item">
                                            <div class="comment-header">
                                                <span class="comment-author">
                                                    <?php echo sanitizeOutput($comment['from_user_name']); ?>
                                                </span>
                                                <span class="comment-date">
                                                    <?php echo date('Y年m月d日 H:i', strtotime($comment['created_at'])); ?>
                                                </span>
                                            </div>
                                            <div class="comment-content">
                                                <?php echo sanitizeOutput($comment['comment']); ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Comment form (will be implemented in task 8) -->
                            <?php if (!$isOwnTimeline): ?>
                                <form method="POST" action="add_comment.php" class="comment-form">
                                    <input type="hidden" name="to_cap_id" value="<?php echo $cap['id']; ?>">
                                    <input type="hidden" name="to_user_id" value="<?php echo $targetUserId; ?>">
                                    <textarea name="comment" placeholder="コメントを入力..." required></textarea>
                                    <button type="submit" style="display: inline-flex; align-items: center; gap: 6px;">
                                        <i data-lucide="send" style="width: 16px; height: 16px;"></i>
                                        <span>コメントを投稿</span>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

<?php include 'includes/footer.php'; ?>
