# CAP システム - チーム改善サイクル管理

## 概要

CAP システムは、チームメンバーが継続的改善サイクル（Check-Action-Plan）を記録・追跡し、お互いに評価を送り合うための Web アプリケーションです。

## 主な機能

-   **ユーザー認証**: サインアップ・ログイン
-   **課題管理**: チーム共有の課題作成（パーセンテージ/五段階尺度/数値）
-   **CAP 投稿**: Check 値入力、分析、改善方向、計画の記録
-   **他者評価**: チームメンバーへの評価送信
-   **グラフ表示**: 自己評価と他者評価を重ねて推移を可視化
-   **タイムライン**: CAP 投稿履歴の閲覧
-   **コメント**: CAP 投稿へのフィードバック

## 環境構築手順

### 必要条件

-   Docker Desktop
-   Git

### セットアップ

1. **リポジトリをクローン**

```bash
git clone https://github.com/taiki-yokoyama/2025winterHackathonA.git
cd 2025winterHackathonA
```

2. **Docker コンテナを起動**

```bash
docker-compose up -d --build
```

3. **アプリケーションにアクセス**

```
http://localhost:8080/
```

4. **初回利用時**
    - サインアップページでアカウントを作成
    - 自動的にチーム 1 に所属します

### コンテナの停止

```bash
docker-compose down
```

### データベースを初期化する場合

```bash
docker-compose down -v
docker-compose up -d --build
```

## 技術スタック

-   **フロントエンド**: HTML, CSS, JavaScript, Chart.js
-   **バックエンド**: PHP 8.x
-   **データベース**: MySQL 8.x
-   **Web サーバー**: Nginx
-   **コンテナ**: Docker, Docker Compose

## ディレクトリ構成

```
├── docker/
│   ├── mysql/         # MySQL設定・初期化SQL
│   ├── nginx/         # Nginx設定
│   └── php/           # PHP設定
├── src/               # PHPソースコード
│   ├── includes/      # 共通関数・ヘッダー・フッター
│   ├── assets/        # CSS, JS, 画像
│   └── *.php          # 各ページ
├── docker-compose.yml
└── README.md
```

---

# ph1 POSSE 課題 サンプル

## サンプルサイト

### ■ トップページ

https://posse-ap.github.io/sample-ph1-website/

```
【参照ソースコード】
/index.html
/assets/styles/common.css
```

### ■ クイズページ

https://posse-ap.github.io/sample-ph1-website/quiz/

```
【参照ソースコード】
/quiz/index.html
/assets/styles/common.css
/assets/scripts/quiz.js
```

#### JavaScript で問題文をループ出力

https://posse-ap.github.io/sample-ph1-website/quiz2/

```
【参照ソースコード】
/quiz2/index.html
/assets/styles/common.css
/assets/scripts/quiz2.js
```

#### JavaScript で問題をランダムに並び替えて出力

https://posse-ap.github.io/sample-ph1-website/quiz3/

```
【参照ソースコード】
/quiz3/index.html
/assets/styles/common.css
/assets/scripts/quiz3.js
```
