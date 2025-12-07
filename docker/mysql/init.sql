DROP DATABASE IF EXISTS posse;
CREATE DATABASE posse;

USE posse;

-- CAP System Tables

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL COMMENT '平文保存（プロトタイプ）',
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Issues table
CREATE TABLE issues (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    metric_type ENUM('percentage', 'scale_5', 'numeric') NOT NULL,
    unit VARCHAR(50) NULL COMMENT '数値型の場合の単位（例：回、cm）',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- CAPs table
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
CREATE INDEX idx_issues_user_id ON issues(user_id);
CREATE INDEX idx_caps_user_id ON caps(user_id);
CREATE INDEX idx_caps_issue_id ON caps(issue_id);
CREATE INDEX idx_comments_to_user_id ON comments(to_user_id);
CREATE INDEX idx_comments_to_cap_id ON comments(to_cap_id);

-- Old quiz tables (keeping for backward compatibility)
CREATE TABLE questions(
    id INT PRIMARY KEY AUTO_INCREMENT,
    content VARCHAR(255) NOT NULL,
    image VARCHAR(255),
    supplement VARCHAR(255)
);

INSERT INTO questions(content,image,supplement) VALUES 
('日本のIT人材が2030年には最大どれくらい不足すると言われているでしょうか？', 'img-quiz01.png', '経済産業省 2019年3月 － IT 人材需給に関する調査'),
('既存業界のビジネスと、先進的なテクノロジーを結びつけて生まれた、新しいビジネスのことをなんと言うでしょう？', 'img-quiz02.png', 'なし'),
('IoTとは何の略でしょう？', 'img-quiz03.png', 'なし'),
('サイバー空間とフィジカル空間を高度に融合させたシステムにより、経済発展と社会的課題の解決を両立する、人間中心の社会のことをなんと言うでしょう？', 'img-quiz04.png', 'Society5.0 - 科学技術政策 - 内閣府'),
('イギリスのコンピューター科学者であるギャビン・ウッド氏が提唱した、ブロックチェーン技術を活用した「次世代分散型インターネット」のことをなんと言うでしょう？', 'img-quiz05.png', 'なし'),
('先進テクノロジー活用企業と出遅れた企業の収益性の差はどれくらいあると言われているでしょうか？', 'img-quiz06.png', 'Accenture Technology Vision 2021')
;

CREATE TABLE choice(
    id INT PRIMARY KEY AUTO_INCREMENT,
    question_id INT NOT NULL,
    name VARCHAR(255),
    valid INT NOT NULL
);

INSERT INTO choice(id,question_id,name,valid) VALUES
(1, 1, '約28万人', 0),
(2, 1, '約79万人', 1),
(3, 1, '約183万人', 0),
(4, 2, 'INTECH', 0),
(5, 2, 'BIZZTECH', 0),
(6, 2, 'X-TECH', 1),
(7, 3, 'Internet of Things', 1),
(8, 3, 'Integrate into Technology', 0),
(9, 3, 'Information on Tool', 0),
(10, 4, 'Society 5.0', 1),
(11, 4, 'CyPhy', 0),
(12, 4, 'SDGs', 0),
(13, 5, 'Web3.0', 1),
(14, 5, 'NFT', 0),
(15, 5, 'メタバース', 0),
(16, 6, '約2倍', 0),
(17, 6, '約5倍', 1),
(18, 6, '約11倍', 0)
;
