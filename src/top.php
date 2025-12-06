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
        
        <div class="placeholder">
            <p>Top画面の詳細機能は、タスク9で実装されます。</p>
            <p>現在、認証システムが正常に動作しています。</p>
        </div>
    </div>
</body>
</html>
