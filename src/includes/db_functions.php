<?php
/**
 * Database Helper Functions for CAP System
 * 
 * This file contains reusable database query functions.
 * Requirements: 10.1, 10.3, 10.4, 10.5, 10.6
 */

// ===================================
// Team Functions
// ===================================

/**
 * Get team by ID
 * 
 * @param PDO $dbh Database connection
 * @param int $teamId Team ID
 * @return array|false Team data or false if not found
 */
function getTeamById($dbh, $teamId) {
    try {
        $stmt = $dbh->prepare('SELECT * FROM teams WHERE id = ?');
        $stmt->execute([$teamId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log('Error fetching team by ID: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get all team members (excluding specified user)
 * 
 * @param PDO $dbh Database connection
 * @param int $teamId Team ID
 * @param int|null $excludeUserId Optional user ID to exclude from results
 * @return array Array of team members
 */
function getTeamMembers($dbh, $teamId, $excludeUserId = null) {
    try {
        $sql = 'SELECT id, email, name, created_at FROM users WHERE team_id = ?';
        $params = [$teamId];
        
        if ($excludeUserId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeUserId;
        }
        
        $sql .= ' ORDER BY name';
        
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Error fetching team members: ' . $e->getMessage());
        return [];
    }
}

// ===================================
// User Functions
// ===================================

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
        $stmt = $dbh->prepare('SELECT id, email, name, team_id, created_at FROM users WHERE id = ?');
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
 * @param int $teamId Team ID (default: 1 for prototype)
 * @return int|false User ID if successful, false otherwise
 */
function createUser($dbh, $email, $password, $name, $teamId = 1) {
    try {
        $stmt = $dbh->prepare('INSERT INTO users (email, password, name, team_id) VALUES (?, ?, ?, ?)');
        $stmt->execute([$email, $password, $name, $teamId]);
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
 * Get all issues for a user's team
 * Requirement: 10.4
 * 
 * @param PDO $dbh Database connection
 * @param int $userId User ID
 * @return array Array of issues
 */
function getUserIssues($dbh, $userId) {
    try {
        // Get user's team_id first
        $user = getUserById($dbh, $userId);
        if (!$user) {
            return [];
        }
        
        $stmt = $dbh->prepare('
            SELECT i.*, u.name as created_by_name 
            FROM issues i 
            JOIN users u ON i.created_by = u.id
            WHERE i.team_id = ? 
            ORDER BY i.created_at DESC
        ');
        $stmt->execute([$user['team_id']]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Error fetching team issues: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get all issues for a team
 * 
 * @param PDO $dbh Database connection
 * @param int $teamId Team ID
 * @return array Array of issues
 */
function getTeamIssues($dbh, $teamId) {
    try {
        $stmt = $dbh->prepare('
            SELECT i.*, u.name as created_by_name 
            FROM issues i 
            JOIN users u ON i.created_by = u.id
            WHERE i.team_id = ? 
            ORDER BY i.created_at DESC
        ');
        $stmt->execute([$teamId]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Error fetching team issues: ' . $e->getMessage());
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
 * Create new issue (for team)
 * Requirement: 10.4
 * 
 * @param PDO $dbh Database connection
 * @param int $userId User ID (creator)
 * @param string $name Issue name
 * @param string $metricType Metric type (percentage, scale_5, numeric)
 * @param string|null $unit Unit for numeric type (optional)
 * @return int|false Issue ID if successful, false otherwise
 */
function createIssue($dbh, $userId, $name, $metricType, $unit = null) {
    try {
        // Get user's team_id
        $user = getUserById($dbh, $userId);
        if (!$user) {
            return false;
        }
        
        $stmt = $dbh->prepare('INSERT INTO issues (team_id, created_by, name, metric_type, unit) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$user['team_id'], $userId, $name, $metricType, $unit]);
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
 * @param int|null $userId Optional user ID to filter by specific user
 * @return array Array of CAPs (empty array if no data exists)
 */
function getRecentCAPsForIssue($dbh, $issueId, $weeks = 8, $userId = null) {
    try {
        // Requirement 5.1: 直近8週間（または指定週数）のCAP履歴を取得
        $sql = '
            SELECT c.*, u.name as user_name FROM caps c
            JOIN users u ON c.user_id = u.id
            WHERE c.issue_id = ? 
            AND c.created_at >= DATE_SUB(NOW(), INTERVAL ? WEEK)
        ';
        $params = [$issueId, $weeks];
        
        if ($userId !== null) {
            $sql .= ' AND c.user_id = ?';
            $params[] = $userId;
        }
        
        $sql .= ' ORDER BY c.created_at ASC';
        
        $stmt = $dbh->prepare($sql);
        $stmt->execute($params);
        
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

// ===================================
// Peer Evaluation Functions (他者評価)
// ===================================

/**
 * Create peer evaluation
 * 
 * @param PDO $dbh Database connection
 * @param int $evaluatorId 評価者のUser ID
 * @param int $targetUserId 評価対象者のUser ID
 * @param int $issueId Issue ID
 * @param float $value 評価値
 * @param int|null $capId 関連するCAP投稿のID（任意）
 * @return int|false Peer evaluation ID if successful, false otherwise
 */
function createPeerEvaluation($dbh, $evaluatorId, $targetUserId, $issueId, $value, $capId = null) {
    try {
        $stmt = $dbh->prepare('
            INSERT INTO peer_evaluations (evaluator_id, target_user_id, issue_id, value, cap_id) 
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([$evaluatorId, $targetUserId, $issueId, $value, $capId]);
        return $dbh->lastInsertId();
    } catch (PDOException $e) {
        error_log('Error creating peer evaluation: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get peer evaluations received by a user for an issue
 * 
 * @param PDO $dbh Database connection
 * @param int $targetUserId 評価対象者のUser ID
 * @param int $issueId Issue ID
 * @param int $weeks 取得する週数（デフォルト: 8）
 * @return array Array of peer evaluations
 */
function getPeerEvaluationsForUser($dbh, $targetUserId, $issueId, $weeks = 8) {
    try {
        $stmt = $dbh->prepare('
            SELECT pe.*, u.name as evaluator_name 
            FROM peer_evaluations pe 
            JOIN users u ON pe.evaluator_id = u.id
            WHERE pe.target_user_id = ? 
            AND pe.issue_id = ?
            AND pe.created_at >= DATE_SUB(NOW(), INTERVAL ? WEEK)
            ORDER BY pe.created_at ASC
        ');
        $stmt->execute([$targetUserId, $issueId, $weeks]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Error fetching peer evaluations: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get average peer evaluation for a user on an issue (most recent period)
 * 
 * @param PDO $dbh Database connection
 * @param int $targetUserId 評価対象者のUser ID
 * @param int $issueId Issue ID
 * @return float|null 平均値、またはデータがない場合はnull
 */
function getLatestPeerEvaluationAverage($dbh, $targetUserId, $issueId) {
    try {
        $stmt = $dbh->prepare('
            SELECT AVG(value) as avg_value 
            FROM peer_evaluations 
            WHERE target_user_id = ? 
            AND issue_id = ?
            AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)
        ');
        $stmt->execute([$targetUserId, $issueId]);
        $result = $stmt->fetch();
        return $result && $result['avg_value'] !== null ? floatval($result['avg_value']) : null;
    } catch (PDOException $e) {
        error_log('Error fetching peer evaluation average: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get recent peer evaluations for an issue (averaged by date for graph)
 * 
 * @param PDO $dbh Database connection
 * @param int $targetUserId 評価対象者のUser ID
 * @param int $issueId Issue ID
 * @param int $weeks 取得する週数（デフォルト: 8）
 * @return array Array with dates and averaged values
 */
function getRecentPeerEvaluationsForGraph($dbh, $targetUserId, $issueId, $weeks = 8) {
    try {
        $stmt = $dbh->prepare('
            SELECT DATE(created_at) as eval_date, AVG(value) as avg_value, COUNT(*) as eval_count
            FROM peer_evaluations 
            WHERE target_user_id = ? 
            AND issue_id = ?
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? WEEK)
            GROUP BY DATE(created_at)
            ORDER BY eval_date ASC
        ');
        $stmt->execute([$targetUserId, $issueId, $weeks]);
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log('Error fetching peer evaluations for graph: ' . $e->getMessage());
        return [];
    }
}
?>
