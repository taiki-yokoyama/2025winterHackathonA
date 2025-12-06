<?php
/**
 * Users List Page for CAP System
 * 
 * Displays all users and links to their timelines.
 * This is a placeholder that will be fully implemented in task 10.
 */

require_once 'config.php';

// Require authentication
requireAuth();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ユーザー一覧 - CAPシステム</title>
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
            <h1>ユーザー一覧</h1>
            <nav class="nav">
                <a href="top.php">Top</a>
                <a href="users.php">ユーザー一覧</a>
                <a href="logout.php">ログアウト</a>
            </nav>
        </header>
        
        <div class="placeholder">
            <p>ユーザー一覧画面は、タスク10で実装されます。</p>
        </div>
    </div>
</body>
</html>
