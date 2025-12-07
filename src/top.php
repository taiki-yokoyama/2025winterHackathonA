<?php
/**
 * Top Page (Dashboard) for CAP System
 * 
 * Displays user's issues, recent comments, and summary graphs.
 * Now includes peer evaluations on graphs.
 * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5, 8.6
 */

require_once 'config.php';

// Require authentication
requireAuth();

// Get current user
$currentUser = getCurrentUser($dbh);

// Get user's issues with latest check values (Requirements 8.1, 8.2)
$issues = getUserIssues($dbh, $currentUser['id']);

// Add latest check value, recent CAPs, and peer evaluations to each issue (Requirements 8.2, 8.5, 8.6)
foreach ($issues as &$issue) {
    $issue['latest_value'] = getLatestCheckValue($dbh, $issue['id']);
    // Get recent 8 weeks of CAPs for graph display (自己評価)
    $issue['recent_caps'] = getRecentCAPsForIssue($dbh, $issue['id'], 8, $currentUser['id']);
    // Get recent peer evaluations for graph display (他者評価)
    $issue['peer_evaluations'] = getRecentPeerEvaluationsForGraph($dbh, $currentUser['id'], $issue['id'], 8);
    // Get latest peer evaluation average
    $issue['latest_peer_value'] = getLatestPeerEvaluationAverage($dbh, $currentUser['id'], $issue['id']);
}
unset($issue); // Break reference

// Build a summary series across all issues so the user sees an overall progress graph
$summary_map = [];
$summary_is_percentage = false;
foreach ($issues as $issue) {
    if (empty($issue['recent_caps'])) continue;
    if (isset($issue['metric_type']) && $issue['metric_type'] === 'percentage') {
        $summary_is_percentage = true;
    }
    foreach ($issue['recent_caps'] as $cap) {
        $d = date('Y-m-d', strtotime($cap['created_at']));
        $summary_map[$d][] = floatval($cap['value']);
    }
}
ksort($summary_map);
$summary_labels = array_map(function($d){ return date('m/d', strtotime($d)); }, array_keys($summary_map));
$summary_values = array_map(function($vals){ return array_sum($vals) / count($vals); }, $summary_map);

// Get comments for the user (Requirements 8.3, 8.4)
$comments = getCommentsForUser($dbh, $currentUser['id'], 10); // Limit to 10 most recent

// Get success message from session
$successMessage = $_SESSION['success'] ?? null;
unset($_SESSION['success']);

// Set page title and additional scripts
$pageTitle = 'Top';
$additionalJS = ['https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js'];

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
        .comments-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .comment-item {
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 15px;
            background: #fafafa;
            transition: box-shadow 0.3s;
        }
        .comment-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .comment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e0e0e0;
        }
        .comment-from {
            font-size: 14px;
            color: #333;
        }
        .comment-date {
            font-size: 12px;
            color: #999;
        }
        .comment-target {
            font-size: 13px;
            color: #666;
            margin-bottom: 10px;
        }
        .comment-content {
            background: #fff;
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 10px;
            line-height: 1.6;
            color: #333;
        }
        .comment-actions {
            text-align: right;
        }
        .comment-link {
            color: #2196F3;
            text-decoration: none;
            font-size: 14px;
            transition: color 0.3s;
        }
        .comment-link:hover {
            color: #1976d2;
            text-decoration: underline;
        }
        .graphs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 20px;
        }
        .graph-item {
            border: 1px solid #e0e0e0;
            border-radius: 4px;
            padding: 15px;
            background: #fff;
        }
        .graph-title {
            margin: 0 0 15px 0;
            font-size: 16px;
            color: #333;
            font-weight: bold;
        }
        .graph-empty {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        .graph-empty p {
            margin: 5px 0;
        }
        .graph-empty-hint {
            font-size: 13px;
            color: #bbb;
        }
    </style>
        
        <div class="welcome">
            <h2>ようこそ、<?php echo sanitizeOutput($currentUser['name']); ?>さん</h2>
            <p>CAPシステムへようこそ。継続的改善サイクルを記録・追跡しましょう。</p>
            <?php if (!empty($issues)): ?>
                <p style="margin-top: 15px;">
                    <a href="create_cap.php" class="btn-create" style="display: inline-flex;">
                        <i data-lucide="edit" class="btn-icon"></i>
                        <span>CAP投稿を作成</span>
                    </a>
                </p>
            <?php endif; ?>
        </div>
        
        <?php if ($successMessage): ?>
            <div class="success-message">
                <?php echo sanitizeOutput($successMessage); ?>
            </div>
        <?php endif; ?>

        <!-- Overall Summary Graph removed per user request -->
        
        <!-- Issues Section -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">あなたのチームの課題</h2>
                <a href="create_issue.php" class="btn-create">
                    <i data-lucide="plus-circle" class="btn-icon"></i>
                    <span>新しい課題を作成</span>
                </a>
            </div>
            
            <?php if (empty($issues)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i data-lucide="clipboard" style="width: 64px; height: 64px; color: #ccc;"></i>
                    </div>
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
                                    <span>最新Check値:</span>
                                    <strong>
                                        <?php 
                                        if ($issue['latest_value'] !== null) {
                                            echo sanitizeOutput($issue['latest_value']);
                                            if ($issue['metric_type'] === 'percentage') {
                                                echo '%';
                                            } elseif ($issue['unit']) {
                                                echo sanitizeOutput($issue['unit']);
                                            }
                                        } else {
                                            echo 'データなし';
                                        }
                                        ?>
                                    </strong>
                                </div>
                                <div class="issue-meta-item">
                                    <span>他者評価平均:</span>
                                    <strong style="color: #FF9800;">
                                        <?php 
                                        if ($issue['latest_peer_value'] !== null) {
                                            echo number_format($issue['latest_peer_value'], 1);
                                            if ($issue['metric_type'] === 'percentage') {
                                                echo '%';
                                            } elseif ($issue['unit']) {
                                                echo sanitizeOutput($issue['unit']);
                                            }
                                        } else {
                                            echo 'データなし';
                                        }
                                        ?>
                                    </strong>
                                </div>
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
        
        <!-- Comments Section (Requirements 8.3, 8.4) -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">自分宛のコメント</h2>
            </div>
            
            <?php if (empty($comments)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i data-lucide="message-circle" style="width: 64px; height: 64px; color: #ccc;"></i>
                    </div>
                    <p>まだコメントがありません。</p>
                </div>
            <?php else: ?>
                <div class="comments-list">
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment-item">
                            <div class="comment-header">
                                <span class="comment-from">
                                    <strong><?php echo sanitizeOutput($comment['from_user_name']); ?></strong>さんからのコメント
                                </span>
                                <span class="comment-date">
                                    <?php echo date('Y年m月d日 H:i', strtotime($comment['created_at'])); ?>
                                </span>
                            </div>
                            <div class="comment-target">
                                対象CAP: <strong><?php echo sanitizeOutput($comment['issue_name']); ?></strong> 
                                (Check値: <?php echo sanitizeOutput($comment['cap_value']); ?>)
                            </div>
                            <div class="comment-content">
                                <?php echo nl2br(sanitizeOutput($comment['comment'])); ?>
                            </div>
                            <div class="comment-actions">
                                <a href="timeline.php?user_id=<?php echo $currentUser['id']; ?>" class="comment-link" style="display: inline-flex; align-items: center; gap: 5px;">
                                    <span>Timelineで確認</span>
                                    <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Summary Graphs Section (Requirements 8.5, 8.6) -->
        <div class="section">
            <div class="section-header">
                <h2 class="section-title">課題の推移グラフ</h2>
            </div>
            
            <div style="background: #e3f2fd; padding: 10px 15px; border-radius: 4px; margin-bottom: 20px; font-size: 14px;">
                <i data-lucide="info" style="width: 16px; height: 16px; display: inline-block; vertical-align: middle;"></i>
                <span style="vertical-align: middle;">
                    <strong style="color: rgb(75, 192, 192);">━━</strong> 自己評価 / 
                    <strong style="color: rgb(255, 159, 64);">- - -</strong> 他者評価（チームメンバーからの平均）
                </span>
            </div>
            
            <?php if (empty($issues)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">
                        <i data-lucide="bar-chart-2" style="width: 64px; height: 64px; color: #ccc;"></i>
                    </div>
                    <p>課題を作成すると、ここに推移グラフが表示されます。</p>
                </div>
            <?php else: ?>
                <div class="graphs-grid">
                    <?php foreach ($issues as $issue): ?>
                        <div class="graph-item">
                            <h3 class="graph-title"><?php echo sanitizeOutput($issue['name']); ?></h3>
                            <?php if (empty($issue['recent_caps']) && empty($issue['peer_evaluations'])): ?>
                                <div class="graph-empty">
                                    <p>データがありません</p>
                                    <p class="graph-empty-hint">CAP投稿を作成すると、グラフが表示されます。</p>
                                </div>
                            <?php else: ?>
                                <canvas id="chart-<?php echo $issue['id']; ?>" width="400" height="250"></canvas>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

<script>
        // Overall summary chart removed

        // Initialize charts for each issue with data
        <?php foreach ($issues as $issue): ?>
            <?php if (!empty($issue['recent_caps']) || !empty($issue['peer_evaluations'])): ?>
                window.addEventListener('load', function() {
                    const ctx = document.getElementById('chart-<?php echo $issue['id']; ?>');
                    if (!ctx) return;
                    
                    // 自己評価データ
                    const selfLabels = <?php echo json_encode(array_map(function($cap) {
                        return date('m/d', strtotime($cap['created_at']));
                    }, $issue['recent_caps'])); ?>;
                    
                    const selfData = <?php echo json_encode(array_map(function($cap) {
                        return floatval($cap['value']);
                    }, $issue['recent_caps'])); ?>;
                    
                    // 他者評価データ
                    const peerLabels = <?php echo json_encode(array_map(function($pe) {
                        return date('m/d', strtotime($pe['eval_date']));
                    }, $issue['peer_evaluations'])); ?>;
                    
                    const peerData = <?php echo json_encode(array_map(function($pe) {
                        return floatval($pe['avg_value']);
                    }, $issue['peer_evaluations'])); ?>;
                    
                    // 全ラベルをマージして一意にする
                    const allLabelsSet = new Set([...selfLabels, ...peerLabels]);
                    const allLabels = Array.from(allLabelsSet).sort();
                    
                    // 各データセットをラベルに合わせてマッピング
                    const selfDataMap = {};
                    selfLabels.forEach((label, i) => { selfDataMap[label] = selfData[i]; });
                    const selfAligned = allLabels.map(l => selfDataMap[l] !== undefined ? selfDataMap[l] : null);
                    
                    const peerDataMap = {};
                    peerLabels.forEach((label, i) => { peerDataMap[label] = peerData[i]; });
                    const peerAligned = allLabels.map(l => peerDataMap[l] !== undefined ? peerDataMap[l] : null);
                    
                    const metricType = '<?php echo $issue['metric_type']; ?>';
                    
                    // Chart configuration with both datasets
                    let chartConfig = {
                        type: 'line',
                        data: {
                            labels: allLabels,
                            datasets: [
                                {
                                    label: '自己評価',
                                    data: selfAligned,
                                    borderColor: 'rgb(75, 192, 192)',
                                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                    tension: 0.1,
                                    fill: false,
                                    spanGaps: true
                                },
                                {
                                    label: '他者評価',
                                    data: peerAligned,
                                    borderColor: 'rgb(255, 159, 64)',
                                    backgroundColor: 'rgba(255, 159, 64, 0.2)',
                                    tension: 0.1,
                                    fill: false,
                                    borderDash: [5, 5],
                                    spanGaps: true
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    display: true,
                                    position: 'top'
                                },
                                title: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    };
                    
                    // Adjust scale for percentage
                    if (metricType === 'percentage') {
                        chartConfig.options.scales.y.max = 100;
                        chartConfig.options.scales.y.ticks = {
                            callback: function(value) {
                                return value + '%';
                            }
                        };
                    }
                    
                    // Adjust scale for 5-point scale
                    if (metricType === 'scale_5') {
                        chartConfig.options.scales.y.max = 5;
                        chartConfig.options.scales.y.min = 1;
                        chartConfig.options.scales.y.ticks = {
                            stepSize: 1
                        };
                    }
                    
                    new Chart(ctx, chartConfig);
                });
            <?php endif; ?>
        <?php endforeach; ?>
    </script>

<?php include 'includes/footer.php'; ?>
