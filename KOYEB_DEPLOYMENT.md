# Koyeb Deployment Guide for Critique App

## 概要
このアプリケーションは Koyeb にデプロイできるようにセットアップされています。

## 必要なもの
- Koyeb アカウント
- PostgreSQL データベース（Koyeb Postgres または外部）
- GitHub リポジトリ

## デプロイ手順

### 1. PostgreSQL データベースの準備
Koyeb または他の PostgreSQL ホスティングサービスでデータベースを作成してください。
- ホスト名
- ポート
- データベース名
- ユーザー名
- パスワード

を記録しておきます。

### 2. 環境変数の設定
Koyeb での環境変数設定（必須）：

```env
APP_NAME=Critique
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_URL=https://your-app.koyeb.app

DB_CONNECTION=pgsql
DB_HOST=your-postgres-host
DB_PORT=5432
DB_DATABASE=critique_db
DB_USERNAME=postgres
DB_PASSWORD=your_secure_password

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database

LOG_CHANNEL=stderr
```

### 3. APP_KEY の生成
ローカル環境で以下を実行して、生成されたキーをコピーします：

```bash
cd backend
php artisan key:generate --show
```

### 4. Koyeb でのデプロイ

#### A. GitHub 連携でのデプロイ（推奨）
1. リポジトリを GitHub にプッシュ
2. Koyeb コンソールで新しいサービスを作成
3. GitHub を選択
4. リポジトリを選択
5. ビルドコマンド: `# Skip (using Dockerfile)`
6. ランコマンド: `# Skip (using Dockerfile)`
7. Dockerfile を選択
8. 環境変数を入力
9. デプロイ

#### B. Docker イメージを直接デプロイ
```bash
# 1. イメージをビルド
docker build -t critique-app:latest .

# 2. Koyeb Registry にプッシュ
docker tag critique-app:latest koyeb.io/your-org/critique-app:latest
docker push koyeb.io/your-org/critique-app:latest

# 3. Koyeb コンソールでイメージを選択してデプロイ
```

## ファイル構成

### ルートディレクトリ
- `Dockerfile` - 本番用マルチステージビルド
- `start.sh` - 起動スクリプト
- `docker-compose.yml` - ローカル開発用（Koyeb では未使用）

### nginx/
- `default.conf` - Nginx 設定（ポート 8000 でリッスン）

### backend/
- `.env.example` - 本番環境用環境変数テンプレート

## 起動時の処理

起動スクリプト（`start.sh`）が自動的に以下を実行します：

1. データベース接続確認（最大30秒待機）
2. マイグレーション実行
3. キャッシュ設定

## ポート設定

アプリケーションはポート **8000** でリッスンします。
Koyeb が自動的にこのポートをスキャンして、公開 URL に転送します。

## トラブルシューティング

### ビルドが失敗する
- `npm ci` コマンドで Node パッケージのインストールが失敗している可能性があります
- `frontend/package-lock.json` が存在することを確認してください

### データベース接続エラー
- 環境変数の `DB_*` 設定を確認してください
- PostgreSQL ホストへのネットワーク接続を確認してください
- ファイアウォール設定を確認してください

### マイグレーション失敗
- ローカルで `php artisan migrate` が成功することを確認してください
- `.env.example` の DB 設定をチェックしてください

### ログの確認
Koyeb コンソールの「Logs」タブで以下を確認できます：
- アプリケーションの起動ログ
- PHP と Nginx のエラーログ

## 開発環境での動作確認

デプロイ前に、Docker でローカルでテストできます：

```bash
# ローカル開発用（docker-compose.yml）
docker-compose up -d

# ブラウザで確認
# Frontend: http://localhost:5173
# Backend API: http://localhost:8080/api

# Koyeb 本番用をテスト
docker build -t critique-app-prod:latest .
docker run -p 8000:8000 \
  -e DB_HOST=localhost \
  -e DB_PASSWORD=password \
  critique-app-prod:latest
```

## ファイルシステムに関する注意

Koyeb はステートレスなプラットフォームのため：
- ファイルアップロードは S3 などの外部ストレージを使用してください
- ローカルストレージに保存されたファイルは再デプロイ時に削除されます

## パフォーマンス最適化

本番環境では以下が自動的に実行されます：
- PHP のクラスマップ最適化
- キャッシュ設定
- ルートキャッシング

