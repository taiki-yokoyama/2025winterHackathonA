<?php
/**
 * Database Helper Functions for CAP System
 * 
 * This file contains reusable database query functions.
 * Requirements: 10.1, 10.3, 10.4, 10.5, 10.6
 */

/**
 * Get user by email
 * 
 * @param PDO $dbh Database connection
 * @param string $email User email
 * @return array|false User data or false if not found
 */
function getUserByEmail($dbh, $email) {
    try {
        $stmt = $dbh->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Error fetching user by email: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get user by ID
 * 
 * @param PDO $dbh Database connection
 * @param int $userId User ID
 * @return array|false User data or false if not found
 */
function getUserById($dbh, $userId) {
    try {
        $stmt = $dbh->prepare('SELECT id, email, name, created_at FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Error fetching user by ID: ' . $e->getMessage());
        return false;
    }
}

/**
 * Create new user
 * Requirement: 10.3
 * 
 * @param PDO $dbh Database connection
 * @param string $email User email
 * @param string $password User password (plain text for prototype)
 * @param string $name User name
 * @return int|false User ID if successful, false otherwise
 */
function createUser($dbh, $email, $password, $name) {
    try {
        $stmt = $dbh->prepare('INSERT INTO users (email, password, name) VALUES (?, ?, ?)');
        $stmt->execute([$email, $password, $name]);
        return $dbh->lastInsertId();
    } catch (PDOException $e) {
        error_log('Error creating user: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get all users
 * 
 * @param PDO $dbh Database connection
 * @return array Array of users
 */
function getAllUsers($dbh) {
    try {
        $stmt = $dbh->query('SELECT id, email, name, created_at FROM users ORDER BY name');
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Error fetching all users: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get all issues for a user
 * Requirement: 10.4
 * 
 * @param PDO $dbh Database connection
 * @param int $userId User ID
 * @return array Array of issues
 */
function getUserIssues($dbh, $userId) {
    try {
        $stmt = $dbh->prepare('SELECT * FROM issues WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Error fetching user issues: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get issue by ID
 * 
 * @param PDO $dbh Database connection
 * @param int $issueId Issue ID
 * @return array|false Issue data or false if not found
 */
function getIssueById($dbh, $issueId) {
    try {
        $stmt = $dbh->prepare('SELECT * FROM issues WHERE id = ?');
        $stmt->execute([$issueId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Error fetching issue by ID: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get CAP by ID
 * 
 * @param PDO $dbh Database connection
 * @param int $capId CAP ID
 * @return array|false CAP data or false if not found
 */
function getCapById($dbh, $capId) {
    try {
        $stmt = $dbh->prepare('SELECT * FROM caps WHERE id = ?');
        $stmt->execute([$capId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Error fetching CAP by ID: ' . $e->getMessage());
        return false;
    }
}

/**
 * Create new issue
 * Requirement: 10.4
 * 
 * @param PDO $dbh Database connection
 * @param int $userId User ID
 * @param string $name Issue name
 * @param string $metricType Metric type (percentage, scale_5, numeric)
 * @param string|null $unit Unit for numeric type (optional)
 * @return int|false Issue ID if successful, false otherwise
 */
function createIssue($dbh, $userId, $name, $metricType, $unit = null) {
    try {
        $stmt = $dbh->prepare('INSERT INTO issues (user_id, name, metric_type, unit) VALUES (?, ?, ?, ?)');
        $stmt->execute([$userId, $name, $metricType, $unit]);
        return $dbh->lastInsertId();
    } catch (PDOException $e) {
        error_log('Error creating issue: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get CAPs for a user
 * Requirement: 10.5
 * 
 * @param PDO $dbh Database connection
 * @param int $userId User ID
 * @param int|null $issueId Optional issue ID to filter by
 * @param int $limit Optional limit (default: no limit)
 * @return array Array of CAPs with issue information
 */
function getUserCAPs($dbh, $userId, $issueId = null, $limit = null) {
    try {
        $sql = 'SELECT c.*, i.name as issue_name, i.metric_type, i.unit 
                FROM caps c 
                JOIN issues i ON c.issue_id = i.id 
                WHERE c.user_id = ?';
        
        $params = [$userId];
        
        if ($issueId !== null) {
            $sql .= ' AND c.issue_id = ?';
            $params[] = $issueId;
        }
        
        $sql .= ' ORDER BY c.created_at DESC';
        
        if ($limit !== null) {
            $sql .= ' LIMIT ?';
            $params[] = $limit;
        }
        
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Error fetching user CAPs: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get recent CAPs for an issue (for graph display)
 * Requirements: 5.1, 5.2
 * 
 * Requirement 5.1: 直近8週間のCAP履歴を取得
 * Requirement 5.2: データ不足時の処理（存在するデータのみ返す）
 * 
 * @param PDO $dbh Database connection
 * @param int $issueId Issue ID
 * @param int $weeks Number of weeks to retrieve (default: 8)
 * @return array Array of CAPs (empty array if no data exists)
 */
function getRecentCAPsForIssue($dbh, $issueId, $weeks = 8) {
    try {
        // Requirement 5.1: 直近8週間（または指定週数）のCAP履歴を取得
        $stmt = $dbh->prepare('
            SELECT * FROM caps 
            WHERE issue_id = ? 
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? WEEK)
            ORDER BY created_at ASC
        ');
        $stmt->execute([$issueId, $weeks]);
        
        // Requirement 5.2: データが存在しない場合は空配列を返す
        // これにより、グラフ表示側で存在するデータのみを表示できる
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Error fetching recent CAPs for issue: ' . $e->getMessage());
        // エラー時も空配列を返す（Requirement 5.2）
        return [];
    }
}

/**
 * Create new CAP
 * Requirement: 10.5
 * 
 * @param PDO $dbh Database connection
 * @param int $userId User ID
 * @param int $issueId Issue ID
 * @param float $value Check value
 * @param string $analysis Analysis content
 * @param string $improveDirection Improvement direction
 * @param string $plan Next plan
 * @return int|false CAP ID if successful, false otherwise
 */
function createCAP($dbh, $userId, $issueId, $value, $analysis, $improveDirection, $plan) {
    try {
        $stmt = $dbh->prepare('
            INSERT INTO caps (user_id, issue_id, value, analysis, improve_direction, plan) 
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$userId, $issueId, $value, $analysis, $improveDirection, $plan]);
        return $dbh->lastInsertId();
    } catch (PDOException $e) {
        error_log('Error creating CAP: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get comments for a CAP
 * Requirement: 10.6
 * 
 * @param PDO $dbh Database connection
 * @param int $capId CAP ID
 * @return array Array of comments with user information
 */
function getCommentsForCAP($dbh, $capId) {
    try {
        $stmt = $dbh->prepare('
            SELECT c.*, u.name as from_user_name 
            FROM comments c 
            JOIN users u ON c.from_user_id = u.id 
            WHERE c.to_cap_id = ? 
            ORDER BY c.created_at DESC
        ');
        $stmt->execute([$capId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Error fetching comments for CAP: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get comments for a user (received comments)
 * 
 * @param PDO $dbh Database connection
 * @param int $userId User ID
 * @param int $limit Optional limit (default: no limit)
 * @return array Array of comments with sender and CAP information
 */
function getCommentsForUser($dbh, $userId, $limit = null) {
    try {
        $sql = 'SELECT c.*, u.name as from_user_name, 
                       cap.value as cap_value, i.name as issue_name
                FROM comments c 
                JOIN users u ON c.from_user_id = u.id 
                JOIN caps cap ON c.to_cap_id = cap.id
                JOIN issues i ON cap.issue_id = i.id
                WHERE c.to_user_id = ? 
                ORDER BY c.created_at DESC';
        
        if ($limit !== null) {
            $sql .= ' LIMIT ?';
        }
        
        $stmt = $dbh->prepare($sql);
        
        if ($limit !== null) {
            $stmt->execute([$userId, $limit]);
        } else {
            $stmt->execute([$userId]);
        }
        
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Error fetching comments for user: ' . $e->getMessage());
        return [];
    }
}

/**
 * Create new comment
 * Requirement: 10.6
 * 
 * @param PDO $dbh Database connection
 * @param int $fromUserId Comment sender ID
 * @param int $toUserId Comment receiver ID
 * @param int $toCapId Target CAP ID
 * @param string $comment Comment content
 * @return int|false Comment ID if successful, false otherwise
 */
function createComment($dbh, $fromUserId, $toUserId, $toCapId, $comment) {
    try {
        $stmt = $dbh->prepare('
            INSERT INTO comments (from_user_id, to_user_id, to_cap_id, comment) 
            VALUES (?, ?, ?, ?)
        ');
        $stmt->execute([$fromUserId, $toUserId, $toCapId, $comment]);
        return $dbh->lastInsertId();
    } catch (PDOException $e) {
        error_log('Error creating comment: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get latest check value for an issue
 * 
 * @param PDO $dbh Database connection
 * @param int $issueId Issue ID
 * @return float|null Latest check value or null if no CAPs exist
 */
function getLatestCheckValue($dbh, $issueId) {
    try {
        $stmt = $dbh->prepare('
            SELECT value FROM caps 
            WHERE issue_id = ? 
            ORDER BY created_at DESC 
            LIMIT 1
        ');
        $stmt->execute([$issueId]);
        $result = $stmt->fetch();
        return $result ? $result['value'] : null;
    } catch (PDOException $e) {
        error_log('Error fetching latest check value: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get comment count for a CAP
 * Requirement: 6.3
 * 
 * @param PDO $dbh Database connection
 * @param int $capId CAP ID
 * @return int Comment count
 */
function getCommentCountForCAP($dbh, $capId) {
    try {
        $stmt = $dbh->prepare('SELECT COUNT(*) as count FROM comments WHERE to_cap_id = ?');
        $stmt->execute([$capId]);
        $result = $stmt->fetch();
        return $result ? (int)$result['count'] : 0;
    } catch (PDOException $e) {
        error_log('Error fetching comment count: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Get CAPs with comment counts for timeline display
 * Requirements: 6.1, 6.2, 6.3
 * 
 * @param PDO $dbh Database connection
 * @param int $userId User ID
 * @param int|null $issueId Optional issue ID to filter by
 * @return array Array of CAPs with issue information and comment counts
 */
function getCAPsForTimeline($dbh, $userId, $issueId = null) {
    try {
        $sql = 'SELECT c.*, i.name as issue_name, i.metric_type, i.unit,
                       (SELECT COUNT(*) FROM comments WHERE to_cap_id = c.id) as comment_count
                FROM caps c 
                JOIN issues i ON c.issue_id = i.id 
                WHERE c.user_id = ?';
        
        $params = [$userId];
        
        if ($issueId !== null) {
            $sql .= ' AND c.issue_id = ?';
            $params[] = $issueId;
        }
        
        $sql .= ' ORDER BY c.created_at DESC';
        
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Error fetching CAPs for timeline: ' . $e->getMessage());
        return [];
    }
}
?>
