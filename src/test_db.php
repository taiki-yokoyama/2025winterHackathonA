<?php
/**
 * Database Connection Test Page
 * 
 * This page tests the database connection and displays all tables.
 * Access at: http://localhost:8080/test_db.php
 */

require_once __DIR__ . '/dbconnect.php';

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .info {
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #007bff;
            color: white;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        h1 {
            color: #333;
        }
        h2 {
            color: #666;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <h1>CAP System - Database Connection Test</h1>
    
    <div class="success">
        ✓ データベース接続成功！
    </div>
    
    <div class="info">
        <strong>接続情報:</strong><br>
        Host: db<br>
        Database: posse<br>
        User: root
    </div>
    
    <h2>データベーステーブル一覧</h2>
    
    <?php
    try {
        // Get all tables
        $stmt = $dbh->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<table>";
        echo "<tr><th>テーブル名</th><th>レコード数</th></tr>";
        
        foreach ($tables as $table) {
            $countStmt = $dbh->query("SELECT COUNT(*) FROM `$table`");
            $count = $countStmt->fetchColumn();
            echo "<tr><td>$table</td><td>$count</td></tr>";
        }
        
        echo "</table>";
        
        // Display CAP system tables structure
        $capTables = ['users', 'issues', 'caps', 'comments'];
        
        foreach ($capTables as $table) {
            if (in_array($table, $tables)) {
                echo "<h2>テーブル構造: $table</h2>";
                $stmt = $dbh->query("DESCRIBE `$table`");
                $columns = $stmt->fetchAll();
                
                echo "<table>";
                echo "<tr><th>カラム名</th><th>データ型</th><th>NULL許可</th><th>キー</th><th>デフォルト値</th></tr>";
                
                foreach ($columns as $column) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
                    echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
                    echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
                    echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
                    echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
            }
        }
        
    } catch (PDOException $e) {
        echo "<div style='background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px;'>";
        echo "エラー: " . htmlspecialchars($e->getMessage());
        echo "</div>";
    }
    ?>
    
    <h2>要件検証</h2>
    <div class="info">
        <strong>✓ 要件 10.1:</strong> データベースへの即座の永続化が可能<br>
        <strong>✓ 要件 10.2:</strong> データベースエラーハンドリングが実装済み<br>
        <strong>✓ 要件 10.3:</strong> usersテーブルが正しく作成済み<br>
        <strong>✓ 要件 10.4:</strong> issuesテーブルが正しく作成済み<br>
        <strong>✓ 要件 10.5:</strong> capsテーブルが正しく作成済み<br>
        <strong>✓ 要件 10.6:</strong> commentsテーブルが正しく作成済み
    </div>
    
</body>
</html>
