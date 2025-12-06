<?php
/**
 * Top Page (Dashboard) for CAP System
 * 
 * Displays user's issues, recent comments, and summary graphs.
 * This is a placeholder that will be fully implemented in task 9.
 */

require_once 'config.php';

// Require authentication
requireAuth();

// Get current user
$currentUser = getCurrentUser($dbh);

// Get user's issues
$issues = getUserIssues($dbh, $currentUser['id']);

// Get success message from session
$successMessage = $_SESSION['success'] ?? null;
unset($_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top - CAPシステム</title>
    <link rel="stylesheet" href="assets/styles/common.css">
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
        .welcome {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .welcome h2 {
            margin-top: 0;
            color: #333;
        }
        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .section {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .section-title {
            margin: 0;
            color: #333;
        }
        .btn-create {
            background: #4CAF50;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            transition: background 0.3s;
        }
        .btn-create:hover {
            background: #45a049;
        }
        .issues-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .issue-item {
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
            transition: box-shadow 0.3s;
        }
        .issue-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .issue-name {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }
        .issue-meta {
            display: flex;
            gap: 20px;
            font-size: 14px;
            color: #666;
        }
        .issue-meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .metric-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .metric-percentage {
            background: #e3f2fd;
            color: #1976d2;
        }
        .metric-scale {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        .metric-numeric {
            background: #e8f5e9;
            color: #388e3c;
        }
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        .placeholder {
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="header">
            <h1>CAPシステム</h1>
            <nav class="nav">
                <a href="top.php">Top</a>
                <a href="users.php">ユーザー一覧</a>
                <a href="logout.php">ログアウト</a>
            </nav>
        </header>
        
        <div class="welcome">
            <h2>ようこそ、<?php echo sanitizeOutput($currentUser['name']); ?>さん</h2>
            <p>CAPシステムへようこそ。継続的改善サイクルを記録・追跡しましょう。</p>
        </div>
        
        <?php if ($successMessage): ?>
            <div class="success-message">
                <?php echo sanitizeOutput($successMessage); ?>
            </div>
        <?php endif; ?>
        
        <!-- Issues Section -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">あなたの課題</h2>
                <a href="create_issue.php" class="btn-create">+ 新しい課題を作成</a>
            </div>
            
            <?php if (empty($issues)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <p>まだ課題が登録されていません。</p>
                    <p>「新しい課題を作成」ボタンから最初の課題を作成しましょう。</p>
                </div>
            <?php else: ?>
                <ul class="issues-list">
                    <?php foreach ($issues as $issue): ?>
                        <li class="issue-item">
                            <div class="issue-name"><?php echo sanitizeOutput($issue['name']); ?></div>
                            <div class="issue-meta">
                                <div class="issue-meta-item">
                                    <span>指標タイプ:</span>
                                    <?php
                                    $metricTypeLabels = [
                                        'percentage' => 'パーセンテージ',
                                        'scale_5' => '五段階尺度',
                                        'numeric' => '数値'
                                    ];
                                    $metricTypeClasses = [
                                        'percentage' => 'metric-percentage',
                                        'scale_5' => 'metric-scale',
                                        'numeric' => 'metric-numeric'
                                    ];
                                    $metricLabel = $metricTypeLabels[$issue['metric_type']] ?? $issue['metric_type'];
                                    $metricClass = $metricTypeClasses[$issue['metric_type']] ?? '';
                                    ?>
                                    <span class="metric-badge <?php echo $metricClass; ?>">
                                        <?php echo sanitizeOutput($metricLabel); ?>
                                    </span>
                                </div>
                                <?php if ($issue['unit']): ?>
                                    <div class="issue-meta-item">
                                        <span>単位:</span>
                                        <strong><?php echo sanitizeOutput($issue['unit']); ?></strong>
                                    </div>
                                <?php endif; ?>
                                <div class="issue-meta-item">
                                    <span>作成日:</span>
                                    <span><?php echo date('Y年m月d日', strtotime($issue['created_at'])); ?></span>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        
        <!-- Placeholder for future features -->
        <div class="placeholder">
            <p>コメント一覧とサマリーグラフは、タスク9で実装されます。</p>
        </div>
    </div>
</body>
</html>
