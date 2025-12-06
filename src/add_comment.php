<?php
/**
 * Add Comment Page for CAP System
 * 
 * Handles comment posting to CAP entries.
 * Requirements: 7.1, 7.2, 7.3, 7.4
 */

require_once 'config.php';

// Require authentication
requireAuth();

// Get current user
$currentUser = getCurrentUser($dbh);

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = '無効なリクエストです。';
    header('Location: top.php');
    exit;
}

// Get form data
$toCapId = isset($_POST['to_cap_id']) ? (int)$_POST['to_cap_id'] : 0;
$toUserId = isset($_POST['to_user_id']) ? (int)$_POST['to_user_id'] : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

// Validate required fields (Requirement 7.1)
if (!validateRequired($comment)) {
    $_SESSION['error'] = 'コメントを入力してください。';
    header('Location: timeline.php?user_id=' . $toUserId);
    exit;
}

// Validate CAP exists
$cap = getCapById($dbh, $toCapId);
if (!$cap) {
    $_SESSION['error'] = '指定されたCAP投稿が見つかりません。';
    header('Location: timeline.php?user_id=' . $toUserId);
    exit;
}

// Validate that user is not commenting on their own CAP
if ($cap['user_id'] === $currentUser['id']) {
    $_SESSION['error'] = '自分のCAP投稿にはコメントできません。';
    header('Location: timeline.php?user_id=' . $toUserId);
    exit;
}

// Create comment (Requirements 7.2, 7.3)
// Requirement 7.2: commentsテーブルに新しいレコードを作成
// Requirement 7.3: from_user_id、to_user_id、to_cap_id、comment、作成タイムスタンプを保存
$commentId = createComment(
    $dbh,
    $currentUser['id'],  // from_user_id
    $toUserId,           // to_user_id
    $toCapId,            // to_cap_id
    $comment             // comment
);

if ($commentId === false) {
    $_SESSION['error'] = 'コメントの投稿に失敗しました。もう一度お試しください。';
    header('Location: timeline.php?user_id=' . $toUserId);
    exit;
}

// Success - redirect to timeline (Requirement 7.4)
// Requirement 7.4: Timeline画面を更新してコメントを表示
$_SESSION['success'] = 'コメントを投稿しました。';
header('Location: timeline.php?user_id=' . $toUserId);
exit;
?>
