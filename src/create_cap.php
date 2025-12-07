<?php
/**
 * Create CAP Page for CAP System
 * 
 * Handles CAP creation for multiple issues simultaneously.
 * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9, 4.10
 */

require_once 'config.php';

// Require authentication
requireAuth();

// Get current user
$currentUser = getCurrentUser($dbh);

// Handle form submission (Requirement 4.6, 4.7, 4.8)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];
    $capsData = [];
    
    // Get all issues for validation
    $userIssues = getUserIssues($dbh, $currentUser['id']);
    
    if (empty($userIssues)) {
        $errors[] = 'CAP投稿を作成するには、まず課題を作成してください。';
    } else {
        // Process each issue's CAP data (Requirement 4.6)
        foreach ($userIssues as $issue) {
            $issueId = $issue['id'];
            
            // Get POST data for this issue
            $value = $_POST["value_{$issueId}"] ?? '';
            $analysis = trim($_POST["analysis_{$issueId}"] ?? '');
            $improveDirection = trim($_POST["improve_direction_{$issueId}"] ?? '');
            $plan = trim($_POST["plan_{$issueId}"] ?? '');
            
            // Validate all fields are required (Requirement 4.5)
            if (!validateRequired($value) || !validateNumeric($value)) {
                $errors[] = "課題「{$issue['name']}」のCheck値を正しく入力してください。";
            }
            
            if (!validateRequired($analysis)) {
                $errors[] = "課題「{$issue['name']}」の分析を入力してください。";
            }
            
            if (!validateRequired($improveDirection)) {
                $errors[] = "課題「{$issue['name']}」の改善方向を入力してください。";
            }
            
            if (!validateRequired($plan)) {
                $errors[] = "課題「{$issue['name']}」の計画を入力してください。";
            }
            
            // Store data for creation if valid
            $capsData[] = [
                'issue_id' => $issueId,
                'value' => $value,
                'analysis' => $analysis,
                'improve_direction' => $improveDirection,
                'plan' => $plan
            ];
        }
    }
    
    // If validation passes, create all CAPs (Requirement 4.7, 4.8)
    if (empty($errors)) {
        $allSuccess = true;
        
        foreach ($capsData as $capData) {
            $capId = createCAP(
                $dbh,
                $currentUser['id'],
                $capData['issue_id'],
                $capData['value'],
                $capData['analysis'],
                $capData['improve_direction'],
                $capData['plan']
            );
            
            if (!$capId) {
                $allSuccess = false;
                break;
            }
        }
        
        if ($allSuccess) {
            // Success - redirect to timeline (Requirement 4.9)
            $_SESSION['success'] = 'CAP投稿を作成しました。';
            header('Location: timeline.php?user_id=' . $currentUser['id']);
            exit;
        } else {
            $errors[] = 'CAP投稿の作成に失敗しました。もう一度お試しください。';
        }
    }
    
    // Store errors in session for display
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        // Store form data for repopulation
        $_SESSION['cap_form_data'] = $_POST;
    }
}

// Get user's issues (Requirement 4.1)
$userIssues = getUserIssues($dbh, $currentUser['id']);

// Get errors from session
$errors = $_SESSION['errors'] ?? [];
$formData = $_SESSION['cap_form_data'] ?? [];
unset($_SESSION['errors'], $_SESSION['cap_form_data']);

// If user has no issues, show message
if (empty($userIssues)) {
    $_SESSION['error'] = 'CAP投稿を作成するには、まず課題を作成してください。';
    header('Location: top.php');
    exit;
}

// Get recent CAPs for each issue (for graph display)
// Requirements: 5.1, 5.2
// Requirement 5.1: 直近8週間のCAP履歴を取得
// Requirement 5.2: データ不足時の処理（存在するデータのみ取得）
$issuesWithHistory = [];
foreach ($userIssues as $issue) {
    // 各課題について直近8週間のCAP履歴を取得
    $recentCAPs = getRecentCAPsForIssue($dbh, $issue['id'], 8);
    $issuesWithHistory[] = [
        'issue' => $issue,
        'recent_caps' => $recentCAPs // 空配列の場合もあり（新規課題など）
    ];
}

// Set page title and additional scripts
$pageTitle = 'CAP投稿作成';
$additionalJS = ['https://cdn.jsdelivr.net/npm/chart.js', 'assets/scripts/cap_form.js'];

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
        .form-container {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-title {
            margin-top: 0;
            margin-bottom: 10px;
            color: #333;
            text-align: center;
        }
        .step-indicator {
            text-align: center;
            margin-bottom: 30px;
            font-size: 18px;
            color: #666;
            font-weight: bold;
        }
        .step {
            display: none;
        }
        .step.active {
            display: block;
        }
        .issue-card {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .issue-card h3 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .issue-meta {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            display: block;
            margin-bottom: 8px;
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
        .form-textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
            min-height: 100px;
            resize: vertical;
        }
        .form-textarea:focus {
            outline: none;
            border-color: #4CAF50;
        }
        .chart-container {
            margin: 20px 0;
            padding: 20px;
            background: white;
            border-radius: 8px;
        }
        .chart-wrapper {
            position: relative;
            height: 300px;
        }
        .btn-container {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-primary {
            background: #4CAF50;
            color: white;
        }
        .btn-primary:hover {
            background: #45a049;
        }
        .btn-secondary {
            background: #2196F3;
            color: white;
        }
        .btn-secondary:hover {
            background: #0b7dda;
        }
        .btn-cancel {
            background: #9E9E9E;
            color: white;
        }
        .btn-cancel:hover {
            background: #757575;
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
        .note-box {
            background: #e3f2fd;
            border: 1px solid #2196F3;
            color: #0d47a1;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .note-box strong {
            display: block;
            margin-bottom: 5px;
        }
    </style>
        
        <div class="form-container">
            <h2 class="form-title">CAP投稿を作成</h2>
            <div class="step-indicator" id="stepIndicator">ステップ 1/4: Check値の入力</div>
            
            <div class="note-box">
                <strong>注意:</strong>
                CAP投稿は作成後、編集・削除できません。全ての課題について慎重に入力してください。
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="error-messages">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo sanitizeOutput($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="create_cap.php" id="capForm">
                <!-- Step 1: Check値入力 (Requirement 4.2) -->
                <div class="step active" id="step1">
                    <h3 style="text-align: center; color: #4CAF50; margin-bottom: 30px;">全ての課題のCheck値を入力してください</h3>
                    <?php foreach ($issuesWithHistory as $data): 
                        $issue = $data['issue'];
                        $issueId = $issue['id'];
                    ?>
                        <div class="issue-card">
                            <h3><?php echo sanitizeOutput($issue['name']); ?></h3>
                            <div class="issue-meta">
                                指標タイプ: 
                                <?php 
                                    $typeLabels = [
                                        'percentage' => 'パーセンテージ (0-100%)',
                                        'scale_5' => '五段階尺度 (1-5)',
                                        'numeric' => '数値' . ($issue['unit'] ? ' (' . sanitizeOutput($issue['unit']) . ')' : '')
                                    ];
                                    echo $typeLabels[$issue['metric_type']];
                                ?>
                            </div>
                            <div class="form-group">
                                <label for="value_<?php echo $issueId; ?>" class="form-label">
                                    Check値 <span style="color: red;">*</span>
                                </label>
                                <input 
                                    type="number" 
                                    step="0.01"
                                    id="value_<?php echo $issueId; ?>" 
                                    name="value_<?php echo $issueId; ?>" 
                                    class="form-input check-value" 
                                    data-issue-id="<?php echo $issueId; ?>"
                                    value="<?php echo sanitizeOutput($formData["value_{$issueId}"] ?? ''); ?>"
                                    required
                                    <?php if ($issue['metric_type'] === 'percentage'): ?>
                                        min="0" max="100"
                                    <?php elseif ($issue['metric_type'] === 'scale_5'): ?>
                                        min="1" max="5" step="1"
                                    <?php endif; ?>
                                >
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Step 2: グラフ表示 (Requirement 4.2, 5.1-5.6) -->
                <!-- Requirement 5.1: 直近8週間のCAP履歴取得 -->
                <!-- Requirement 5.2: データ不足時の処理（存在するデータのみ表示） -->
                <!-- Requirement 5.3: 指標タイプ別のグラフ生成ロジック -->
                <!-- Requirement 5.4: パーセンテージ・数値: 折れ線グラフ -->
                <!-- Requirement 5.5: 五段階尺度: 適切なグラフ形式 -->
                <!-- Requirement 5.6: 新規Check値のプレビュー表示 -->
                <div class="step" id="step2">
                    <h3 style="text-align: center; color: #4CAF50; margin-bottom: 30px;">推移グラフを確認してください</h3>
                    <div class="note-box" style="background: #fff3cd; border-color: #ffc107; color: #856404;">
                        <strong>グラフについて:</strong>
                        過去8週間のデータと今回入力した値（新規）を表示しています。データが少ない場合は、存在するデータのみ表示されます。
                    </div>
                    <?php foreach ($issuesWithHistory as $data): 
                        $issue = $data['issue'];
                        $issueId = $issue['id'];
                        $recentCAPs = $data['recent_caps'];
                        $dataCount = count($recentCAPs);
                    ?>
                        <div class="issue-card">
                            <h3><?php echo sanitizeOutput($issue['name']); ?></h3>
                            <?php if ($dataCount === 0): ?>
                                <div style="background: #e3f2fd; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px;">
                                    ℹ️ この課題は初めてのCAP投稿です。今回の値のみ表示されます。
                                </div>
                            <?php elseif ($dataCount < 8): ?>
                                <div style="background: #fff3cd; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px;">
                                    ℹ️ 過去<?php echo $dataCount; ?>週分のデータと今回の値を表示しています。
                                </div>
                            <?php endif; ?>
                            <div class="chart-container">
                                <div class="chart-wrapper">
                                    <canvas id="chart_<?php echo $issueId; ?>"></canvas>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Step 3: Action入力 (Requirement 4.2) -->
                <div class="step" id="step3">
                    <h3 style="text-align: center; color: #4CAF50; margin-bottom: 30px;">分析と改善方向を入力してください</h3>
                    <?php foreach ($issuesWithHistory as $data): 
                        $issue = $data['issue'];
                        $issueId = $issue['id'];
                    ?>
                        <div class="issue-card">
                            <h3><?php echo sanitizeOutput($issue['name']); ?></h3>
                            <div class="form-group">
                                <label for="analysis_<?php echo $issueId; ?>" class="form-label">
                                    分析 <span style="color: red;">*</span>
                                </label>
                                <textarea 
                                    id="analysis_<?php echo $issueId; ?>" 
                                    name="analysis_<?php echo $issueId; ?>" 
                                    class="form-textarea"
                                    placeholder="今週の結果について分析してください"
                                    required
                                ><?php echo sanitizeOutput($formData["analysis_{$issueId}"] ?? ''); ?></textarea>
                            </div>
                            <div class="form-group">
                                <label for="improve_direction_<?php echo $issueId; ?>" class="form-label">
                                    改善方向 <span style="color: red;">*</span>
                                </label>
                                <textarea 
                                    id="improve_direction_<?php echo $issueId; ?>" 
                                    name="improve_direction_<?php echo $issueId; ?>" 
                                    class="form-textarea"
                                    placeholder="どのように改善していくか方向性を記入してください"
                                    required
                                ><?php echo sanitizeOutput($formData["improve_direction_{$issueId}"] ?? ''); ?></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Step 4: Plan入力 (Requirement 4.2) -->
                <div class="step" id="step4">
                    <h3 style="text-align: center; color: #4CAF50; margin-bottom: 30px;">次の計画を入力してください</h3>
                    <?php foreach ($issuesWithHistory as $data): 
                        $issue = $data['issue'];
                        $issueId = $issue['id'];
                    ?>
                        <div class="issue-card">
                            <h3><?php echo sanitizeOutput($issue['name']); ?></h3>
                            <div class="form-group">
                                <label for="plan_<?php echo $issueId; ?>" class="form-label">
                                    計画 <span style="color: red;">*</span>
                                </label>
                                <textarea 
                                    id="plan_<?php echo $issueId; ?>" 
                                    name="plan_<?php echo $issueId; ?>" 
                                    class="form-textarea"
                                    placeholder="次週の具体的な計画を記入してください"
                                    required
                                ><?php echo sanitizeOutput($formData["plan_{$issueId}"] ?? ''); ?></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Navigation buttons (Requirement 4.3, 4.4) -->
                <div class="btn-container">
                    <button type="button" class="btn btn-secondary" id="btnPrev" style="display: none;">前へ</button>
                    <button type="button" class="btn btn-primary" id="btnNext">次へ</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmit" style="display: none;">投稿する</button>
                    <a href="top.php" class="btn btn-cancel">キャンセル</a>
                </div>
            </form>
        </div>

<script>
        // Store issue data for JavaScript (Requirement 4.3)
        const issuesData = <?php echo json_encode($issuesWithHistory); ?>;
    </script>

<?php include 'includes/footer.php'; ?>
