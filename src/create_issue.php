<?php
/**
 * Create Issue Page for CAP System
 * 
 * Handles issue creation with name, metric type, and optional unit.
 * Requirements: 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 3.7
 */

require_once 'config.php';

// Require authentication
requireAuth();

// Get current user
$currentUser = getCurrentUser($dbh);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $metricType = $_POST['metric_type'] ?? '';
    $unit = trim($_POST['unit'] ?? '');
    
    $errors = [];
    
    // Validation (Requirement 3.1, 3.3)
    if (!validateRequired($name)) {
        $errors[] = '課題名を入力してください。';
    } elseif (strlen($name) > 255) {
        $errors[] = '課題名は255文字以内で入力してください。';
    }
    
    // Validate metric type (Requirement 3.4)
    if (!validateMetricType($metricType)) {
        $errors[] = '有効な指標タイプを選択してください。';
    }
    
    // Validate unit for numeric type (Requirement 3.5)
    if ($metricType === 'numeric' && !empty($unit) && strlen($unit) > 50) {
        $errors[] = '単位は50文字以内で入力してください。';
    }
    
    // If validation passes, create issue
    if (empty($errors)) {
        // Prepare unit value (null if not numeric type or empty)
        $unitValue = ($metricType === 'numeric' && !empty($unit)) ? $unit : null;
        
        // Create issue (Requirement 3.2, 3.6)
        $issueId = createIssue($dbh, $currentUser['id'], $name, $metricType, $unitValue);
        
        if ($issueId) {
            // Success - redirect to top page (Requirement 3.6)
            $_SESSION['success'] = '課題を作成しました。';
            header('Location: top.php');
            exit;
        } else {
            $errors[] = '課題の作成に失敗しました。もう一度お試しください。';
        }
    }
    
    // Store errors in session for display
    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        $_SESSION['form_data'] = [
            'name' => $name,
            'metric_type' => $metricType,
            'unit' => $unit
        ];
    }
}

// Get errors and form data from session
$errors = $_SESSION['errors'] ?? [];
$formData = $_SESSION['form_data'] ?? [];
unset($_SESSION['errors'], $_SESSION['form_data']);

// Set page title
$pageTitle = '課題作成';

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
            max-width: 700px;
            margin: 0 auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-title {
            margin-top: 0;
            margin-bottom: 30px;
            color: #333;
            text-align: center;
        }
        .form-group {
            margin-bottom: 25px;
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
        .form-select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
            background: white;
        }
        .form-select:focus {
            outline: none;
            border-color: #4CAF50;
        }
        .form-help {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        .metric-type-info {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
            font-size: 14px;
            color: #555;
        }
        .metric-type-info ul {
            margin: 10px 0 0 0;
            padding-left: 20px;
        }
        .metric-type-info li {
            margin-bottom: 5px;
        }
        .unit-field {
            display: none;
        }
        .unit-field.show {
            display: block;
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
        .btn-cancel {
            width: 100%;
            padding: 12px;
            background: #9E9E9E;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            box-sizing: border-box;
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
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
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
            <h2 class="form-title" style="display: flex; align-items: center; justify-content: center; gap: 10px;">
                <i data-lucide="plus-circle" style="width: 28px; height: 28px; color: #4CAF50;"></i>
                <span>新しい課題を作成</span>
            </h2>
            
            
            <div class="note-box" style="display: flex; align-items: flex-start; gap: 10px; background: #e3f2fd; border-color: #2196F3; color: #0d47a1;">
                <i data-lucide="users" style="width: 20px; height: 20px; flex-shrink: 0; margin-top: 2px;"></i>
                <div>
                    <strong>チーム共有:</strong>
                    作成した課題はチーム全員に共有されます。チームメンバー全員がこの課題に対してCAP投稿を行い、お互いに評価を送り合うことができます。
                </div>
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
            
            <form method="POST" action="create_issue.php" id="issueForm">
                <div class="form-group">
                    <label for="name" class="form-label" style="display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="target" style="width: 16px; height: 16px;"></i>
                        <span>課題名 <span style="color: red;">*</span></span>
                    </label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="form-input" 
                        value="<?php echo sanitizeOutput($formData['name'] ?? ''); ?>"
                        placeholder="例: 稼働時間が足りない、本音が言えていない"
                        required
                        maxlength="255"
                    >
                    <div class="form-help">改善したい具体的なテーマを入力してください（最大255文字）</div>
                </div>
                
                <div class="form-group">
                    <label for="metric_type" class="form-label" style="display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="sliders" style="width: 16px; height: 16px;"></i>
                        <span>指標タイプ <span style="color: red;">*</span></span>
                    </label>
                    <select 
                        id="metric_type" 
                        name="metric_type" 
                        class="form-select" 
                        required
                    >
                        <option value="">選択してください</option>
                        <option value="percentage" <?php echo ($formData['metric_type'] ?? '') === 'percentage' ? 'selected' : ''; ?>>
                            パーセンテージ (0-100%)
                        </option>
                        <option value="scale_5" <?php echo ($formData['metric_type'] ?? '') === 'scale_5' ? 'selected' : ''; ?>>
                            五段階尺度 (1-5)
                        </option>
                        <option value="numeric" <?php echo ($formData['metric_type'] ?? '') === 'numeric' ? 'selected' : ''; ?>>
                            数値
                        </option>
                    </select>
                    
                    <div class="metric-type-info">
                        <strong>指標タイプについて:</strong>
                        <ul>
                            <li><strong>パーセンテージ:</strong> 達成率や進捗率など（例: 目標達成率 75%）</li>
                            <li><strong>五段階尺度:</strong> 満足度や評価など（例: どれだけできたか 4/5）</li>
                            <li><strong>数値:</strong> 具体的な数値で測定（例: 稼働時間 120分）</li>
                        </ul>
                    </div>
                </div>
                
                <!-- Unit field (only shown for numeric type) -->
                <div class="form-group unit-field" id="unitField">
                    <label for="unit" class="form-label" style="display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="tag" style="width: 16px; height: 16px;"></i>
                        <span>単位（オプション）</span>
                    </label>
                    <input 
                        type="text" 
                        id="unit" 
                        name="unit" 
                        class="form-input" 
                        value="<?php echo sanitizeOutput($formData['unit'] ?? ''); ?>"
                        placeholder="例: kg, 分, 回, cm"
                        maxlength="50"
                    >
                    <div class="form-help">数値型の場合、単位を指定できます（最大50文字）</div>
                </div>
                
                <button type="submit" class="btn-submit" style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i data-lucide="check-circle" style="width: 18px; height: 18px;"></i>
                    <span>課題を作成</span>
                </button>
                <a href="top.php" class="btn-cancel" style="display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i data-lucide="x-circle" style="width: 18px; height: 18px;"></i>
                    <span>キャンセル</span>
                </a>
            </form>
        </div>

<script>
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        
        // Show/hide unit field based on metric type selection
        document.getElementById('metric_type').addEventListener('change', function() {
            const unitField = document.getElementById('unitField');
            if (this.value === 'numeric') {
                unitField.classList.add('show');
            } else {
                unitField.classList.remove('show');
                document.getElementById('unit').value = '';
            }
        });
        
        // Initialize unit field visibility on page load
        window.addEventListener('DOMContentLoaded', function() {
            const metricType = document.getElementById('metric_type').value;
            const unitField = document.getElementById('unitField');
            if (metricType === 'numeric') {
                unitField.classList.add('show');
            }
        });
    </script>

<?php include 'includes/footer.php'; ?>
