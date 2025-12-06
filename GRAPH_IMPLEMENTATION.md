# グラフ表示機能の実装

## 概要
タスク6「グラフ表示機能の実装」が完了しました。
CAP作成時に過去8週間のデータと新規入力値をグラフで表示する機能を実装しました。

## 実装内容

### 6.1 グラフライブラリの統合 ✅

#### Chart.jsの追加
- **package.json**: Chart.jsをnpm依存関係として追加
  ```bash
  npm install chart.js --save
  ```
- **create_cap.php**: CDN経由でChart.jsを読み込み（line 138）
  ```html
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  ```

#### グラフ描画用JavaScript関数
- **cap_form.js**: グラフ描画機能を実装
  - `renderCharts()`: 全課題のグラフを描画
  - `getChartConfig()`: 指標タイプに応じたグラフ設定を生成
  - `getYAxisLabel()`: Y軸ラベルを生成

### 6.2 CAP履歴取得とグラフ生成 ✅

#### 要件5.1: 直近8週間のCAP履歴取得
**実装場所**: `src/includes/db_functions.php`
```php
function getRecentCAPsForIssue($dbh, $issueId, $weeks = 8) {
    // DATE_SUB(NOW(), INTERVAL ? WEEK)で直近8週間を取得
    // ORDER BY created_at ASCで古い順にソート
}
```

**呼び出し**: `src/create_cap.php` (line 121-128)
```php
foreach ($userIssues as $issue) {
    $recentCAPs = getRecentCAPsForIssue($dbh, $issue['id'], 8);
    $issuesWithHistory[] = [
        'issue' => $issue,
        'recent_caps' => $recentCAPs
    ];
}
```

#### 要件5.2: データ不足時の処理
- データが8週間分ない場合、存在するデータのみを返す
- 空配列の場合も正常に処理（新規課題の場合）
- UIに情報メッセージを表示
  - 初回投稿: "この課題は初めてのCAP投稿です"
  - データ不足: "過去X週分のデータと今回の値を表示"

#### 要件5.3: 指標タイプ別のグラフ生成ロジック
**実装場所**: `cap_form.js` の `getChartConfig()` メソッド

各指標タイプに応じた設定:
- **percentage**: 青色、0-100%の範囲
- **scale_5**: オレンジ色、1-5の範囲、整数のみ
- **numeric**: 緑色、自動範囲、単位表示

#### 要件5.4: パーセンテージ・数値: 折れ線グラフ
- Chart.jsの`type: 'line'`を使用
- パーセンテージ: Y軸を0-100%に固定
- 数値: Y軸を自動調整、単位を表示

#### 要件5.5: 五段階尺度: 適切なグラフ形式
- 折れ線グラフを使用（離散値として表示）
- Y軸を1-5に固定
- 整数のみ表示（stepSize: 1）
- グリッド線を強調

#### 要件5.6: 新規Check値のプレビュー表示
- 履歴データの後に「今回」ラベルで新規値を追加
- 新規値のポイントを四角形で強調表示（`pointStyle: 'rectRot'`）
- ツールチップに「← 新規」と表示

## ファイル変更一覧

### 新規作成
1. `GRAPH_IMPLEMENTATION.md` - 実装ドキュメント
2. `src/test_graph.html` - グラフ機能テストページ

### 変更
1. `package.json` - Chart.js依存関係を追加
2. `src/assets/scripts/cap_form.js` - グラフ描画機能を改善
   - コメントを追加して要件との対応を明確化
   - 指標タイプ別の色分けを実装
   - 新規値の強調表示を実装
3. `src/includes/db_functions.php` - CAP履歴取得関数にコメント追加
4. `src/create_cap.php` - グラフ表示ステップを改善
   - データ不足時の情報メッセージを追加
   - 要件との対応をコメントで明記

## テスト方法

### 手動テスト
1. Dockerコンテナを起動
   ```bash
   docker-compose up -d
   ```

2. ブラウザでテストページにアクセス
   ```
   http://localhost:8080/test_graph.html
   ```

3. 以下の5つのテストケースが表示されることを確認:
   - テスト1: パーセンテージ型グラフ
   - テスト2: 五段階尺度グラフ
   - テスト3: 数値型グラフ
   - テスト4: データ不足時の処理
   - テスト5: 新規Check値のプレビュー

### 統合テスト
1. ユーザーでログイン
2. 課題を作成（各指標タイプで1つずつ）
3. CAP投稿を作成
4. ステップ2でグラフが正しく表示されることを確認
5. 複数回CAP投稿を行い、履歴が蓄積されることを確認

## 技術的な詳細

### グラフの色分け
- **パーセンテージ**: 青色 (#2196F3)
- **五段階尺度**: オレンジ色 (#FF9800)
- **数値**: 緑色 (#4CAF50)

### データフロー
```
1. PHP: getRecentCAPsForIssue() → 直近8週間のデータ取得
2. PHP: $issuesWithHistory → データをJSONとして出力
3. JS: issuesData → データを受け取り
4. JS: renderCharts() → ステップ2で呼び出し
5. JS: getChartConfig() → 指標タイプ別の設定生成
6. Chart.js: グラフを描画
```

### パフォーマンス考慮
- グラフは必要な時（ステップ2）のみ描画
- 既存のグラフは破棄してから再描画（メモリリーク防止）
- データベースクエリは8週間分のみに制限

## 今後の改善案
1. グラフのエクスポート機能（PNG/PDF）
2. グラフの拡大表示機能
3. 複数課題の比較グラフ
4. 目標値の表示（破線で表示）
5. トレンド分析（移動平均など）

## 関連要件
- 要件5.1: 直近8週間のCAP履歴取得 ✅
- 要件5.2: データ不足時の処理 ✅
- 要件5.3: 指標タイプ別のグラフ生成 ✅
- 要件5.4: パーセンテージ・数値: 折れ線グラフ ✅
- 要件5.5: 五段階尺度: 適切なグラフ形式 ✅
- 要件5.6: 新規Check値のプレビュー表示 ✅

## 完了日
2024年12月7日
