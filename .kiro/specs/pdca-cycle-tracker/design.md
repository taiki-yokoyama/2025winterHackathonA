# 設計書

## 概要

CAPサイクル管理システムは、ユーザーが継続的改善活動を記録・追跡するためのWebアプリケーションです。従来のPDCAサイクルから「Do（実行）」を除外し、Check（結果測定）、Action（分析・改善方向）、Plan（次の計画）に焦点を当てることで、計画と結果に重点を置いた改善活動を支援します。

本システムは、PHP、MySQL、JavaScriptを使用したサーバーサイドレンダリング型のWebアプリケーションとして実装されます。

## アーキテクチャ

### システム構成

```
┌─────────────────────────────────────────┐
│         クライアント（ブラウザ）          │
│  - HTML/CSS                             │
│  - JavaScript（画面制御、グラフ描画）    │
└─────────────────┬───────────────────────┘
                  │ HTTP/HTTPS
┌─────────────────┴───────────────────────┐
│         Webサーバー（Nginx/Apache）      │
└─────────────────┬───────────────────────┘
                  │
┌─────────────────┴───────────────────────┐
│         PHPアプリケーション              │
│  - 認証・セッション管理                  │
│  - ビジネスロジック                      │
│  - データベースアクセス                  │
└─────────────────┬───────────────────────┘
                  │
┌─────────────────┴───────────────────────┐
│         MySQLデータベース                │
│  - users, issues, caps, comments        │
└─────────────────────────────────────────┘
```

### 技術スタック

- **バックエンド**: PHP 7.4+
- **データベース**: MySQL 8.0+
- **フロントエンド**: HTML5, CSS3, JavaScript (ES6+)
- **グラフライブラリ**: Chart.js（または同等のライブラリ）
- **セッション管理**: PHPネイティブセッション

## コンポーネントと インターフェース

### 1. 認証コンポーネント

#### 責務
- ユーザー登録（サインアップ）
- ログイン認証
- ログアウト
- セッション管理

#### 主要機能

**signup.php**
- 入力: email, password, name
- 処理: バリデーション → ユーザー作成 → セッション開始
- 出力: Top画面へリダイレクト

**login.php**
- 入力: email, password
- 処理: 認証 → セッション開始
- 出力: Top画面へリダイレクト

**logout.php**
- 処理: セッション破棄
- 出力: ログイン画面へリダイレクト

### 2. 課題管理コンポーネント

#### 責務
- 課題（Issue）の作成
- 課題一覧の表示

#### 主要機能

**create_issue.php**
- 入力: name, metric_type, unit（オプション）
- 処理: バリデーション → Issue作成
- 出力: Top画面へリダイレクト
- 制約: 作成後の編集・削除は不可

### 3. CAP投稿コンポーネント

#### 責務
- 複数課題に対するCAP投稿の作成
- JavaScriptによる多段階入力フローの制御
- グラフ表示

#### 主要機能

**create_cap.php（サーバーサイド）**
- 入力: 全課題分の {issue_id, value, analysis, improve_direction, plan}[]
- 処理: バリデーション → 各課題ごとにCAPレコード作成
- 出力: Timeline画面へリダイレクト

**cap_form.js（クライアントサイド）**
- ステップ1: 全課題のCheck値入力
- ステップ2: 全課題のグラフ表示（過去8週間 + 新規データプレビュー）
- ステップ3: 全課題のAction入力（分析、改善方向）
- ステップ4: 全課題のPlan入力
- 機能: 前の画面に戻るボタン、データの一時保持、1回のPOST送信

### 4. Timeline表示コンポーネント

#### 責務
- ユーザーのCAP投稿履歴の表示
- Issue別タブフィルタリング
- コメント表示

#### 主要機能

**timeline.php**
- 入力: user_id（URLパラメータ）
- 処理: 
  - 対象ユーザーの全CAP取得
  - Issue別にタブ表示
  - 各CAPに紐づくコメント取得
- 出力: Timeline画面HTML

### 5. コメントコンポーネント

#### 責務
- CAP投稿へのコメント追加
- コメント一覧表示

#### 主要機能

**add_comment.php**
- 入力: to_cap_id, comment
- 処理: コメント作成（from_user_id, to_user_id, to_cap_id）
- 出力: Timeline画面へリダイレクト

### 6. Top画面（ダッシュボード）コンポーネント

#### 責務
- 課題一覧の表示
- 自分宛コメント一覧の表示
- 各課題のサマリーグラフ表示

#### 主要機能

**top.php**
- 処理:
  - ログインユーザーの全Issue取得
  - 各Issueの最新Check値取得
  - 各Issueの直近8週間のCAP取得（グラフ用）
  - 自分宛のコメント一覧取得
- 出力: Top画面HTML

### 7. ユーザー一覧コンポーネント

#### 責務
- 全ユーザーの一覧表示
- 他ユーザーのTimelineへのナビゲーション

#### 主要機能

**users.php**
- 処理: 全ユーザー取得
- 出力: ユーザー一覧HTML（各ユーザーのTimelineへのリンク付き）

## データモデル

### ER図

```
users (1) ──< (N) issues
  │                 │
  │                 │
  │            (1) ─┴─< (N) caps
  │                       │
  │                       │
  └──< (N) comments >──< (1)
```

### テーブル詳細

#### usersテーブル
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL COMMENT '平文保存（プロトタイプ）',
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### issuesテーブル
```sql
CREATE TABLE issues (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    metric_type ENUM('percentage', 'scale_5', 'numeric') NOT NULL,
    unit VARCHAR(50) NULL COMMENT '数値型の場合の単位（例：回、cm）',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

#### capsテーブル
```sql
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
```

#### commentsテーブル
```sql
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
```

## エラーハンドリング

### バリデーションエラー

**処理フロー:**
1. サーバーサイドでバリデーション実行
2. エラーがある場合、PHPセッション変数にエラーメッセージを保存
3. 元のフォーム画面にリダイレクト
4. フォーム画面でセッション変数からエラーメッセージを取得・表示
5. 表示後、セッション変数をクリア

**実装例:**
```php
// エラー設定
$_SESSION['error'] = 'メールアドレスは既に登録されています';
header('Location: signup.php');

// エラー表示
if (isset($_SESSION['error'])) {
    echo '<div class="error">' . htmlspecialchars($_SESSION['error']) . '</div>';
    unset($_SESSION['error']);
}
```

### データベースエラー

**処理フロー:**
1. データベース操作でエラー発生
2. 適切なHTTPステータスコード（500）を返す
3. ユーザーフレンドリーなエラーメッセージを表示
4. エラーログに詳細を記録

**実装例:**
```php
try {
    // データベース操作
} catch (PDOException $e) {
    error_log('Database error: ' . $e->getMessage());
    http_response_code(500);
    echo 'データベースエラーが発生しました。しばらくしてから再度お試しください。';
    exit;
}
```

## テスト戦略

### 単体テスト

**対象:**
- バリデーション関数
- データベースアクセス関数
- セッション管理関数

**ツール:** PHPUnit

**例:**
- メールアドレスのバリデーション
- パスワードの検証
- Issue作成時のデータ整合性

### 統合テスト

**対象:**
- ユーザー登録からログインまでのフロー
- CAP作成から表示までのフロー
- コメント投稿から表示までのフロー

**手法:** 手動テスト + ブラウザ自動化（Selenium等）

### UIテスト

**対象:**
- JavaScriptによる画面遷移
- グラフ表示
- タブ切り替え

**手法:** 手動テスト + ブラウザ開発者ツール

## セキュリティ考慮事項

### プロトタイプ段階での制限

- **パスワード**: 平文保存（本番環境では password_hash() を使用すべき）
- **HTTPS**: 未実装（本番環境では必須）
- **CSRF対策**: 未実装（本番環境では必須）

### 実装するセキュリティ対策

1. **SQLインジェクション対策**: PDOのプリペアドステートメント使用
2. **XSS対策**: htmlspecialchars() による出力エスケープ
3. **セッションハイジャック対策**: session_regenerate_id() の使用
4. **認証チェック**: 保護されたページでのセッション確認

## パフォーマンス考慮事項

### データベースクエリ最適化

1. **インデックス作成:**
   - users.email（UNIQUE制約で自動作成）
   - issues.user_id
   - caps.user_id, caps.issue_id
   - comments.to_user_id, comments.to_cap_id

2. **クエリ最適化:**
   - Timeline表示: JOINを使用して1回のクエリで取得
   - グラフデータ: 直近8週間のみ取得（LIMIT使用）

### フロントエンド最適化

1. **グラフ描画**: Chart.jsの軽量設定を使用
2. **JavaScript**: 必要最小限のライブラリのみ読み込み
3. **画像**: 最適化された画像を使用

## デプロイメント

### 環境構成

**開発環境:**
- Docker Compose（既存のdocker-compose.ymlを使用）
- PHP-FPM + Nginx
- MySQL 8.0

**ディレクトリ構造:**
```
src/
├── index.php（Top画面）
├── login.php
├── signup.php
├── logout.php
├── create_issue.php
├── create_cap.php
├── timeline.php
├── users.php
├── add_comment.php
├── dbconnect.php（データベース接続）
├── assets/
│   ├── css/
│   ├── js/
│   │   └── cap_form.js
│   └── img/
└── includes/
    ├── auth.php（認証関数）
    ├── validation.php（バリデーション関数）
    └── db_functions.php（データベース関数）
```

### データベース初期化

**init.sql:**
```sql
-- テーブル作成
-- サンプルデータ挿入（オプション）
```

## 今後の拡張性

### 将来的な機能追加の可能性

1. **目標設定機能**: Issueごとに目標値を設定
2. **通知機能**: コメント受信時のメール通知
3. **エクスポート機能**: CAP履歴のCSVエクスポート
4. **グループ機能**: チームでのCAP共有
5. **モバイルアプリ**: レスポンシブデザイン対応

### アーキテクチャの改善案

1. **MVCフレームワーク導入**: Laravel、Symfonyなど
2. **API化**: RESTful APIとSPAの分離
3. **パスワードハッシュ化**: password_hash()の導入
4. **CSRF対策**: トークンベースの保護
5. **キャッシング**: Redisによるセッション・データキャッシュ


## 正確性プロパティ

*プロパティとは、システムの全ての有効な実行において真であるべき特性や振る舞いです。本質的には、システムが何をすべきかについての形式的な記述です。プロパティは、人間が読める仕様と機械で検証可能な正確性保証との橋渡しとなります。*

### プロパティ1: ユーザー登録のラウンドトリップ
*任意の*有効なユーザーデータ（email、password、name）に対して、ユーザーを作成した後、そのemailでデータベースから取得すると、同じname とemailが返されるべきである
**検証: 要件 1.2**

### プロパティ2: メールアドレスの一意性
*任意の*既存のメールアドレスに対して、同じメールアドレスで新規ユーザー登録を試みると、システムは登録を拒否し、エラーメッセージを返すべきである
**検証: 要件 1.3**

### プロパティ3: ログイン認証の正確性
*任意の*登録済みユーザーに対して、正しいemailとpasswordでログインを試みると、システムはセッションを開始し、ユーザーをTop画面にリダイレクトすべきである
**検証: 要件 2.2**

### プロパティ4: 認証失敗時のエラーハンドリング
*任意の*間違ったパスワードに対して、ログインを試みると、システムはログインを拒否し、エラーメッセージを表示すべきである
**検証: 要件 2.3**

### プロパティ5: ログアウト後のセッション破棄
*任意の*ログイン中のユーザーに対して、ログアウトを実行すると、システムはセッションを破棄し、保護されたページへのアクセスを拒否すべきである
**検証: 要件 2.5**

### プロパティ6: Issue作成のラウンドトリップ
*任意の*有効なIssueデータ（name、metric_type、unit）に対して、Issueを作成した後、データベースから取得すると、同じname、metric_type、unitが返されるべきである
**検証: 要件 3.2**

### プロパティ7: 空の課題名の拒否
*任意の*空文字列または空白文字のみの課題名に対して、Issue作成を試みると、システムは作成を拒否し、バリデーションエラーを表示すべきである
**検証: 要件 3.3**

### プロパティ8: 指標タイプの制約
*任意の*Issueに対して、metric_typeは'percentage'、'scale_5'、'numeric'のいずれかの値のみを持つべきである
**検証: 要件 3.4**

### プロパティ9: Issue編集・削除の禁止
*任意の*作成済みIssueに対して、編集または削除操作を試みると、システムは操作を拒否すべきである
**検証: 要件 3.7**

### プロパティ10: CAP作成時の全Issue表示
*任意の*ユーザーに対して、CAP作成画面にアクセスすると、システムはそのユーザーの全Issueを表示すべきである
**検証: 要件 4.1**

### プロパティ11: CAP必須項目のバリデーション
*任意の*CAPデータに対して、value、analysis、improve_direction、planのいずれかが空である場合、システムは送信を拒否し、バリデーションエラーを表示すべきである
**検証: 要件 4.5**

### プロパティ12: 複数CAP一括作成
*任意の*複数のIssueに対して、各Issueに対するCAPデータを送信すると、システムは各Issueごとに個別のCAPレコードをデータベースに作成すべきである
**検証: 要件 4.7**

### プロパティ13: CAPデータの完全性
*任意の*CAPレコードに対して、user_id、issue_id、value、analysis、improve_direction、plan、created_atの全フィールドが保存されているべきである
**検証: 要件 4.8**

### プロパティ14: CAP編集・削除の禁止
*任意の*作成済みCAPに対して、編集または削除操作を試みると、システムは操作を拒否すべきである
**検証: 要件 4.10**

### プロパティ15: 直近8週間のCAP履歴取得
*任意の*Issueに対して、グラフ表示時にシステムは直近8週間（または存在する全データ）のCAP履歴を取得すべきである
**検証: 要件 5.1, 5.2**

### プロパティ16: 指標タイプに応じたグラフ生成
*任意の*Issueに対して、metric_typeが'percentage'または'numeric'の場合は折れ線グラフ、'scale_5'の場合は五段階尺度用グラフが生成されるべきである
**検証: 要件 5.4, 5.5**

### プロパティ17: Timeline表示の時系列順序
*任意の*ユーザーのTimelineに対して、CAP投稿はcreated_atの降順（新しい順）で表示されるべきである
**検証: 要件 6.1**

### プロパティ18: CAP表示内容の完全性
*任意の*CAP投稿に対して、課題名、Check値、分析、改善方向、計画、投稿日時が表示されるべきである
**検証: 要件 6.2**

### プロパティ19: Issueタブによるフィルタリング
*任意の*Timelineに対して、特定のIssueタブを選択すると、そのIssueに関連するCAP投稿のみが表示されるべきである
**検証: 要件 6.5**

### プロパティ20: コメント作成のラウンドトリップ
*任意の*有効なコメントデータ（from_user_id、to_user_id、to_cap_id、comment）に対して、コメントを作成した後、データベースから取得すると、同じデータが返されるべきである
**検証: 要件 7.2, 7.3**

### プロパティ21: コメント表示の順序
*任意の*CAPに対して、コメントはcreated_atの降順（新しい順）で表示されるべきである
**検証: 要件 7.5**

### プロパティ22: Top画面での全Issue表示
*任意の*ユーザーに対して、Top画面にアクセスすると、そのユーザーの全Issueが表示されるべきである
**検証: 要件 8.1**

### プロパティ23: 自分宛コメントのフィルタリング
*任意の*ユーザーに対して、Top画面では to_user_id が自分のIDと一致するコメントのみが表示されるべきである
**検証: 要件 8.3**

### プロパティ24: ユーザー一覧の完全性
*任意の*時点で、ユーザー一覧画面は全ての登録済みユーザーを表示すべきである
**検証: 要件 9.1**

### プロパティ25: データベース永続化の即時性
*任意の*データ作成・更新操作に対して、操作完了後すぐにデータベースから同じデータを取得できるべきである
**検証: 要件 10.1**
