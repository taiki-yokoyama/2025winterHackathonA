DROP DATABASE IF EXISTS posse;
CREATE DATABASE posse;

USE posse;

-- CAP System Tables

-- Teams table (プロトタイプ: 全員チーム1に所属)
CREATE TABLE teams (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default team
INSERT INTO teams (id, name) VALUES (1, 'チーム1');

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL COMMENT '平文保存（プロトタイプ）',
    name VARCHAR(100) NOT NULL,
    team_id INT NOT NULL DEFAULT 1 COMMENT 'プロトタイプ: 全員チーム1',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id)
);

-- Issues table (チーム共有: team_idで管理)
CREATE TABLE issues (
    id INT PRIMARY KEY AUTO_INCREMENT,
    team_id INT NOT NULL COMMENT 'チーム共有の課題',
    created_by INT NOT NULL COMMENT '作成者',
    name VARCHAR(255) NOT NULL,
    metric_type ENUM('percentage', 'scale_5', 'numeric') NOT NULL,
    unit VARCHAR(50) NULL COMMENT '数値型の場合の単位（例：回、cm）',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (team_id) REFERENCES teams(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- CAPs table (自己評価)
CREATE TABLE caps (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    issue_id INT NOT NULL,
    value DECIMAL(10,2) NOT NULL COMMENT 'Check値（実測値）',
    analysis TEXT NOT NULL COMMENT '分析内容',
    improve_direction TEXT NOT NULL COMMENT '改善方向',
    plan TEXT NOT NULL COMMENT '次の計画',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (issue_id) REFERENCES issues(id)
);

-- Peer evaluations table (他者評価)
CREATE TABLE peer_evaluations (
    id INT PRIMARY KEY AUTO_INCREMENT,
    evaluator_id INT NOT NULL COMMENT '評価者',
    target_user_id INT NOT NULL COMMENT '評価対象者',
    issue_id INT NOT NULL,
    value DECIMAL(10,2) NOT NULL COMMENT '評価値',
    cap_id INT NULL COMMENT '関連するCAP投稿（評価者のCAP）',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (evaluator_id) REFERENCES users(id),
    FOREIGN KEY (target_user_id) REFERENCES users(id),
    FOREIGN KEY (issue_id) REFERENCES issues(id),
    FOREIGN KEY (cap_id) REFERENCES caps(id)
);

-- Comments table
CREATE TABLE comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    from_user_id INT NOT NULL,
    to_user_id INT NOT NULL,
    to_cap_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (from_user_id) REFERENCES users(id),
    FOREIGN KEY (to_user_id) REFERENCES users(id),
    FOREIGN KEY (to_cap_id) REFERENCES caps(id)
);

-- Create indexes for performance optimization
CREATE INDEX idx_issues_team_id ON issues(team_id);
CREATE INDEX idx_issues_created_by ON issues(created_by);
CREATE INDEX idx_caps_user_id ON caps(user_id);
CREATE INDEX idx_caps_issue_id ON caps(issue_id);
CREATE INDEX idx_peer_evaluations_evaluator ON peer_evaluations(evaluator_id);
CREATE INDEX idx_peer_evaluations_target ON peer_evaluations(target_user_id);
CREATE INDEX idx_peer_evaluations_issue ON peer_evaluations(issue_id);
CREATE INDEX idx_comments_to_user_id ON comments(to_user_id);
CREATE INDEX idx_comments_to_cap_id ON comments(to_cap_id);
CREATE INDEX idx_users_team_id ON users(team_id);

