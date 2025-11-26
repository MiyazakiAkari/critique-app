# Profile CRUD テストガイド

## バックエンドテスト（Laravel PHPUnit）

### テストの実行

```bash
# バックエンドディレクトリに移動
cd backend

# すべてのテストを実行
./vendor/bin/phpunit

# 特定のテストファイルを実行
./vendor/bin/phpunit tests/Feature/ProfileTest.php

# 特定のテストメソッドを実行
./vendor/bin/phpunit --filter test_it_can_get_own_profile

# カバレッジレポート付きで実行（xdebug が必要）
./vendor/bin/phpunit --coverage-html coverage
```

### テストの内容

#### Feature テスト (`tests/Feature/ProfileTest.php`)
- ✅ 自分のプロフィール取得
- ✅ プロフィールが存在しない場合の自動作成
- ✅ 他ユーザーのプロフィール取得（username で）
- ✅ 存在しないユーザーの404エラー
- ✅ プロフィール更新
- ✅ プロフィールが存在しない場合の作成と更新
- ✅ bio の文字数バリデーション（最大500文字）
- ✅ avatar_url の URL 形式バリデーション
- ✅ アバター画像のアップロード
- ✅ アバターファイルタイプのバリデーション
- ✅ アバターファイルサイズのバリデーション（最大2MB）
- ✅ 古いアバターの削除
- ✅ プロフィールのリセット
- ✅ 認証が必要なエンドポイントの401エラー

#### Unit テスト (`tests/Unit/ProfileModelTest.php`)
- ✅ Profile モデルと User モデルのリレーション
- ✅ fillable 属性の確認
- ✅ bio と avatar_url が nullable
- ✅ User が削除されたときの CASCADE 削除

---

## フロントエンドテスト（Vitest + Vue Test Utils）

### 必要なパッケージのインストール

```bash
# フロントエンドディレクトリに移動
cd frontend

# テスト用パッケージをインストール
npm install -D vitest @vue/test-utils @vitest/ui jsdom @testing-library/jest-dom happy-dom
```

### テストの実行

```bash
# すべてのテストを実行
npm run test

# watch モードで実行
npm run test -- --watch

# UI モードで実行
npm run test:ui

# カバレッジレポート付きで実行
npm run test:coverage
```

### テストの内容

#### ProfilePage コンポーネントテスト (`src/__tests__/ProfilePage.spec.ts`)
- ✅ プロフィールページのレンダリング
- ✅ 自分のプロフィールの場合は編集ボタン表示
- ✅ 他ユーザーのプロフィールの場合は編集ボタン非表示
- ✅ プロフィール取得失敗時のエラー表示
- ✅ 編集ボタンクリックでモーダル表示
- ✅ プロフィール更新機能

---

## Docker 環境でのテスト実行

### バックエンドテスト

```bash
# Docker コンテナ内でテストを実行
docker-compose exec php ./vendor/bin/phpunit

# 特定のテストを実行
docker-compose exec php ./vendor/bin/phpunit tests/Feature/ProfileTest.php
```

### データベースのリフレッシュ

```bash
# テスト用データベースをリセット
docker-compose exec php php artisan migrate:fresh --env=testing
```

---

## CI/CD パイプライン用

### GitHub Actions の例

```yaml
name: Tests

on: [push, pull_request]

jobs:
  backend-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Run Backend Tests
        run: |
          cd backend
          composer install
          php artisan test

  frontend-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup Node
        uses: actions/setup-node@v2
        with:
          node-version: '18'
      - name: Run Frontend Tests
        run: |
          cd frontend
          npm install
          npm run test
```

---

## テストのベストプラクティス

### バックエンド
1. **RefreshDatabase トレイト**を使用してテストごとにDBをリセット
2. **Factory** を使ってテストデータを生成
3. **Storage::fake()** を使ってファイルアップロードをモック
4. 認証が必要なエンドポイントは `actingAs()` を使用

### フロントエンド
1. **vi.mock()** で外部依存をモック
2. **localStorage** をモックしてユーザー情報を設定
3. **router** をモックしてナビゲーションをテスト
4. 非同期処理は `await nextTick()` で待機

---

## トラブルシューティング

### バックエンド

**エラー: Class 'Tests\TestCase' not found**
```bash
composer dump-autoload
```

**エラー: Database connection failed**
```bash
# .env.testing ファイルを作成
cp .env .env.testing
# DB_CONNECTION を sqlite に変更
```

### フロントエンド

**エラー: Cannot find module 'vitest'**
```bash
npm install -D vitest @vue/test-utils jsdom
```

**エラー: ReferenceError: localStorage is not defined**
- `vitest.config.ts` で `environment: 'jsdom'` を設定

---

## カバレッジ目標

- バックエンド: 80% 以上
- フロントエンド: 70% 以上

現在のカバレッジを確認:
```bash
# バックエンド
cd backend
./vendor/bin/phpunit --coverage-text

# フロントエンド
cd frontend
npm run test:coverage
```
