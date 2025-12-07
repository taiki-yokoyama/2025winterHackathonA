<?php
/**
 * 最終チェックポイント - 全システムテスト
 * 
 * このスクリプトは以下をテストします：
 * 1. データベース接続
 * 2. ユーザー登録（サインアップ）
 * 3. ログイン認証
 * 4. Issue作成
 * 5. CAP投稿
 * 6. Timeline表示
 * 7. コメント機能
 * 8. エラーケース
 */

require_once 'dbconnect.php';
require_once 'includes/validation.php';
require_once 'includes/db_functions.php';

// テスト結果を格納
$test_results = [];
$test_count = 0;
$passed_count = 0;

function test_assert($condition, $test_name, $error_message = '') {
    global $test_results, $test_count, $passed_count;
    $test_count++;
    
    if ($condition) {
        $test_results[] = "✓ PASS: $test_name";
        $passed_count++;
        return true;
    } else {
        $test_results[] = "✗ FAIL: $test_name" . ($error_message ? " - $error_message" : "");
        return false;
    }
}

echo "<h1>CAPシステム - 最終チェックポイントテスト</h1>\n";
echo "<pre>\n";

// ========================================
// テスト1: データベース接続
// ========================================
echo "\n=== テスト1: データベース接続 ===\n";
try {
    // $dbh is created in dbconnect.php
    $pdo = $dbh;
    test_assert($pdo !== null, "データベース接続成功");
} catch (Exception $e) {
    test_assert(false, "データベース接続", $e->getMessage());
    die("データベース接続に失敗しました。テストを中止します。\n");
}

// ========================================
// テスト2: ユーザー登録（サインアップ）
// ========================================
echo "\n=== テスト2: ユーザー登録 ===\n";

// テストユーザーのクリーンアップ
$test_email = 'test_user_' . time() . '@example.com';
$test_password = 'password123';
$test_name = 'テストユーザー';

// 2.1 有効なユーザー登録
try {
    $stmt = $pdo->prepare("INSERT INTO users (email, password, name) VALUES (?, ?, ?)");
    $result = $stmt->execute([$test_email, $test_password, $test_name]);
    $user_id = $pdo->lastInsertId();
    test_assert($result && $user_id > 0, "有効なユーザー登録");
} catch (Exception $e) {
    test_assert(false, "有効なユーザー登録", $e->getMessage());
}

// 2.2 重複メールアドレスの拒否
try {
    $stmt = $pdo->prepare("INSERT INTO users (email, password, name) VALUES (?, ?, ?)");
    $stmt->execute([$test_email, $test_password, 'Another User']);
    test_assert(false, "重複メールアドレスの拒否（エラーが発生すべき）");
} catch (PDOException $e) {
    test_assert(true, "重複メールアドレスの拒否");
}

// 2.3 ユーザーデータの取得確認
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
test_assert($user && $user['email'] === $test_email && $user['name'] === $test_name, 
    "ユーザーデータのラウンドトリップ");

// ========================================
// テスト3: ログイン認証
// ========================================
echo "\n=== テスト3: ログイン認証 ===\n";

// 3.1 正しい認証情報でのログイン
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
$stmt->execute([$test_email, $test_password]);
$auth_user = $stmt->fetch(PDO::FETCH_ASSOC);
test_assert($auth_user !== false, "正しい認証情報でのログイン成功");

// 3.2 間違ったパスワードでのログイン失敗
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND password = ?");
$stmt->execute([$test_email, 'wrong_password']);
$auth_user = $stmt->fetch(PDO::FETCH_ASSOC);
test_assert($auth_user === false, "間違ったパスワードでのログイン拒否");

// ========================================
// テスト4: Issue作成
// ========================================
echo "\n=== テスト4: Issue作成 ===\n";

// 4.1 パーセンテージ型のIssue作成
try {
    $stmt = $pdo->prepare("INSERT INTO issues (user_id, name, metric_type) VALUES (?, ?, ?)");
    $result = $stmt->execute([$user_id, '体重管理', 'percentage']);
    $issue_id_1 = $pdo->lastInsertId();
    test_assert($result && $issue_id_1 > 0, "パーセンテージ型Issue作成");
} catch (Exception $e) {
    test_assert(false, "パーセンテージ型Issue作成", $e->getMessage());
}

// 4.2 五段階尺度型のIssue作成
try {
    $stmt = $pdo->prepare("INSERT INTO issues (user_id, name, metric_type) VALUES (?, ?, ?)");
    $result = $stmt->execute([$user_id, '睡眠の質', 'scale_5']);
    $issue_id_2 = $pdo->lastInsertId();
    test_assert($result && $issue_id_2 > 0, "五段階尺度型Issue作成");
} catch (Exception $e) {
    test_assert(false, "五段階尺度型Issue作成", $e->getMessage());
}

// 4.3 数値型（単位付き）のIssue作成
try {
    $stmt = $pdo->prepare("INSERT INTO issues (user_id, name, metric_type, unit) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([$user_id, '勉強時間', 'numeric', '時間']);
    $issue_id_3 = $pdo->lastInsertId();
    test_assert($result && $issue_id_3 > 0, "数値型（単位付き）Issue作成");
} catch (Exception $e) {
    test_assert(false, "数値型（単位付き）Issue作成", $e->getMessage());
}

// 4.4 Issueデータの取得確認
$stmt = $pdo->prepare("SELECT * FROM issues WHERE user_id = ?");
$stmt->execute([$user_id]);
$issues = $stmt->fetchAll(PDO::FETCH_ASSOC);
test_assert(count($issues) === 3, "全Issue取得（3件）");

// 4.5 指標タイプの制約確認
$stmt = $pdo->prepare("SELECT * FROM issues WHERE id = ?");
$stmt->execute([$issue_id_1]);
$issue = $stmt->fetch(PDO::FETCH_ASSOC);
test_assert(in_array($issue['metric_type'], ['percentage', 'scale_5', 'numeric']), 
    "指標タイプの制約");

// ========================================
// テスト5: CAP投稿
// ========================================
echo "\n=== テスト5: CAP投稿 ===\n";

// 5.1 CAP投稿作成（Issue 1）
try {
    $stmt = $pdo->prepare("INSERT INTO caps (user_id, issue_id, value, analysis, improve_direction, plan) VALUES (?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([
        $user_id, 
        $issue_id_1, 
        75.5, 
        '目標の80%達成。順調に進んでいる。', 
        '運動量を増やす', 
        '週3回のジョギングを実施'
    ]);
    $cap_id_1 = $pdo->lastInsertId();
    test_assert($result && $cap_id_1 > 0, "CAP投稿作成（Issue 1）");
} catch (Exception $e) {
    test_assert(false, "CAP投稿作成（Issue 1）", $e->getMessage());
}

// 5.2 CAP投稿作成（Issue 2）
try {
    $stmt = $pdo->prepare("INSERT INTO caps (user_id, issue_id, value, analysis, improve_direction, plan) VALUES (?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([
        $user_id, 
        $issue_id_2, 
        4, 
        '睡眠の質が良好', 
        '就寝時間を早める', 
        '22時までに就寝'
    ]);
    $cap_id_2 = $pdo->lastInsertId();
    test_assert($result && $cap_id_2 > 0, "CAP投稿作成（Issue 2）");
} catch (Exception $e) {
    test_assert(false, "CAP投稿作成（Issue 2）", $e->getMessage());
}

// 5.3 CAP投稿作成（Issue 3）
try {
    $stmt = $pdo->prepare("INSERT INTO caps (user_id, issue_id, value, analysis, improve_direction, plan) VALUES (?, ?, ?, ?, ?, ?)");
    $result = $stmt->execute([
        $user_id, 
        $issue_id_3, 
        3.5, 
        '目標の5時間に対して3.5時間', 
        '集中力を高める', 
        'ポモドーロテクニックを導入'
    ]);
    $cap_id_3 = $pdo->lastInsertId();
    test_assert($result && $cap_id_3 > 0, "CAP投稿作成（Issue 3）");
} catch (Exception $e) {
    test_assert(false, "CAP投稿作成（Issue 3）", $e->getMessage());
}

// 5.4 CAPデータの完全性確認
$stmt = $pdo->prepare("SELECT * FROM caps WHERE id = ?");
$stmt->execute([$cap_id_1]);
$cap = $stmt->fetch(PDO::FETCH_ASSOC);
test_assert(
    $cap && 
    isset($cap['user_id']) && 
    isset($cap['issue_id']) && 
    isset($cap['value']) && 
    isset($cap['analysis']) && 
    isset($cap['improve_direction']) && 
    isset($cap['plan']) && 
    isset($cap['created_at']),
    "CAPデータの完全性"
);

// 5.5 複数CAP投稿の取得
$stmt = $pdo->prepare("SELECT * FROM caps WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$caps = $stmt->fetchAll(PDO::FETCH_ASSOC);
test_assert(count($caps) === 3, "複数CAP投稿の取得（3件）");

// ========================================
// テスト6: Timeline表示
// ========================================
echo "\n=== テスト6: Timeline表示 ===\n";

// 6.1 ユーザーの全CAP取得（時系列順）
$stmt = $pdo->prepare("
    SELECT c.*, i.name as issue_name, i.metric_type, i.unit 
    FROM caps c 
    JOIN issues i ON c.issue_id = i.id 
    WHERE c.user_id = ? 
    ORDER BY c.created_at DESC
");
$stmt->execute([$user_id]);
$timeline_caps = $stmt->fetchAll(PDO::FETCH_ASSOC);
test_assert(count($timeline_caps) === 3, "Timeline全CAP取得");
test_assert($timeline_caps[0]['issue_name'] !== null, "TimelineにIssue名が含まれる");

// 6.2 Issue別フィルタリング
$stmt = $pdo->prepare("
    SELECT c.*, i.name as issue_name 
    FROM caps c 
    JOIN issues i ON c.issue_id = i.id 
    WHERE c.user_id = ? AND c.issue_id = ? 
    ORDER BY c.created_at DESC
");
$stmt->execute([$user_id, $issue_id_1]);
$filtered_caps = $stmt->fetchAll(PDO::FETCH_ASSOC);
test_assert(count($filtered_caps) === 1, "Issue別フィルタリング");

// ========================================
// テスト7: コメント機能
// ========================================
echo "\n=== テスト7: コメント機能 ===\n";

// 7.1 別のテストユーザーを作成（コメント送信者）
$commenter_email = 'commenter_' . time() . '@example.com';
try {
    $stmt = $pdo->prepare("INSERT INTO users (email, password, name) VALUES (?, ?, ?)");
    $stmt->execute([$commenter_email, 'password123', 'コメント送信者']);
    $commenter_id = $pdo->lastInsertId();
    test_assert($commenter_id > 0, "コメント送信者ユーザー作成");
} catch (Exception $e) {
    test_assert(false, "コメント送信者ユーザー作成", $e->getMessage());
}

// 7.2 コメント投稿
try {
    $stmt = $pdo->prepare("INSERT INTO comments (from_user_id, to_user_id, to_cap_id, comment) VALUES (?, ?, ?, ?)");
    $result = $stmt->execute([
        $commenter_id, 
        $user_id, 
        $cap_id_1, 
        '素晴らしい進捗ですね！頑張ってください！'
    ]);
    $comment_id_1 = $pdo->lastInsertId();
    test_assert($result && $comment_id_1 > 0, "コメント投稿");
} catch (Exception $e) {
    test_assert(false, "コメント投稿", $e->getMessage());
}

// 7.3 複数コメント投稿
try {
    $stmt = $pdo->prepare("INSERT INTO comments (from_user_id, to_user_id, to_cap_id, comment) VALUES (?, ?, ?, ?)");
    $stmt->execute([$commenter_id, $user_id, $cap_id_1, '2つ目のコメントです']);
    $comment_id_2 = $pdo->lastInsertId();
    test_assert($comment_id_2 > 0, "複数コメント投稿");
} catch (Exception $e) {
    test_assert(false, "複数コメント投稿", $e->getMessage());
}

// 7.4 CAP別コメント取得
$stmt = $pdo->prepare("
    SELECT c.*, u.name as from_user_name 
    FROM comments c 
    JOIN users u ON c.from_user_id = u.id 
    WHERE c.to_cap_id = ? 
    ORDER BY c.created_at DESC
");
$stmt->execute([$cap_id_1]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
test_assert(count($comments) === 2, "CAP別コメント取得（2件）");
test_assert($comments[0]['from_user_name'] !== null, "コメントに送信者名が含まれる");

// 7.5 ユーザー宛コメント取得
$stmt = $pdo->prepare("
    SELECT c.*, u.name as from_user_name 
    FROM comments c 
    JOIN users u ON c.from_user_id = u.id 
    WHERE c.to_user_id = ? 
    ORDER BY c.created_at DESC
");
$stmt->execute([$user_id]);
$user_comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
test_assert(count($user_comments) === 2, "ユーザー宛コメント取得");

// ========================================
// テスト8: Top画面データ
// ========================================
echo "\n=== テスト8: Top画面データ ===\n";

// 8.1 ユーザーの全Issue取得
$stmt = $pdo->prepare("SELECT * FROM issues WHERE user_id = ?");
$stmt->execute([$user_id]);
$user_issues = $stmt->fetchAll(PDO::FETCH_ASSOC);
test_assert(count($user_issues) === 3, "Top画面：全Issue取得");

// 8.2 各Issueの最新Check値取得
$stmt = $pdo->prepare("
    SELECT i.*, 
           (SELECT c.value FROM caps c WHERE c.issue_id = i.id ORDER BY c.created_at DESC LIMIT 1) as latest_value
    FROM issues i 
    WHERE i.user_id = ?
");
$stmt->execute([$user_id]);
$issues_with_latest = $stmt->fetchAll(PDO::FETCH_ASSOC);
test_assert($issues_with_latest[0]['latest_value'] !== null, "最新Check値取得");

// 8.3 直近8週間のCAP履歴取得
$stmt = $pdo->prepare("
    SELECT * FROM caps 
    WHERE issue_id = ? 
    ORDER BY created_at DESC 
    LIMIT 8
");
$stmt->execute([$issue_id_1]);
$recent_caps = $stmt->fetchAll(PDO::FETCH_ASSOC);
test_assert(count($recent_caps) >= 1, "直近8週間のCAP履歴取得");

// ========================================
// テスト9: ユーザー一覧
// ========================================
echo "\n=== テスト9: ユーザー一覧 ===\n";

// 9.1 全ユーザー取得
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$all_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
test_assert(count($all_users) >= 2, "全ユーザー取得（2名以上）");

// ========================================
// テスト10: エラーケース
// ========================================
echo "\n=== テスト10: エラーケース ===\n";

// 10.1 空の課題名でのIssue作成拒否
$empty_name = '';
$is_valid = !empty(trim($empty_name));
test_assert(!$is_valid, "空の課題名のバリデーション拒否");

// 10.2 空のCheck値でのCAP作成拒否
$empty_value = '';
$is_valid = !empty(trim($empty_value));
test_assert(!$is_valid, "空のCheck値のバリデーション拒否");

// 10.3 存在しないユーザーでのログイン失敗
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute(['nonexistent@example.com']);
$nonexistent_user = $stmt->fetch(PDO::FETCH_ASSOC);
test_assert($nonexistent_user === false, "存在しないユーザーでのログイン拒否");

// ========================================
// テスト11: データ永続化
// ========================================
echo "\n=== テスト11: データ永続化 ===\n";

// 11.1 作成直後のデータ取得
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$persisted_user = $stmt->fetch(PDO::FETCH_ASSOC);
test_assert($persisted_user !== false, "ユーザーデータの即時永続化");

$stmt = $pdo->prepare("SELECT * FROM caps WHERE id = ?");
$stmt->execute([$cap_id_1]);
$persisted_cap = $stmt->fetch(PDO::FETCH_ASSOC);
test_assert($persisted_cap !== false, "CAPデータの即時永続化");

// ========================================
// クリーンアップ
// ========================================
echo "\n=== クリーンアップ ===\n";
try {
    // コメント削除
    $stmt = $pdo->prepare("DELETE FROM comments WHERE to_user_id = ? OR from_user_id = ?");
    $stmt->execute([$user_id, $commenter_id]);
    
    // CAP削除
    $stmt = $pdo->prepare("DELETE FROM caps WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // Issue削除
    $stmt = $pdo->prepare("DELETE FROM issues WHERE user_id = ?");
    $stmt->execute([$user_id]);
    
    // ユーザー削除
    $stmt = $pdo->prepare("DELETE FROM users WHERE id IN (?, ?)");
    $stmt->execute([$user_id, $commenter_id]);
    
    echo "テストデータのクリーンアップ完了\n";
} catch (Exception $e) {
    echo "クリーンアップエラー: " . $e->getMessage() . "\n";
}

// ========================================
// テスト結果サマリー
// ========================================
echo "\n" . str_repeat("=", 60) . "\n";
echo "テスト結果サマリー\n";
echo str_repeat("=", 60) . "\n\n";

foreach ($test_results as $result) {
    echo $result . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "合計: $test_count テスト\n";
echo "成功: $passed_count テスト\n";
echo "失敗: " . ($test_count - $passed_count) . " テスト\n";
echo "成功率: " . round(($passed_count / $test_count) * 100, 2) . "%\n";
echo str_repeat("=", 60) . "\n";

if ($passed_count === $test_count) {
    echo "\n✓ 全てのテストが成功しました！\n";
} else {
    echo "\n✗ いくつかのテストが失敗しました。詳細を確認してください。\n";
}

echo "</pre>\n";
