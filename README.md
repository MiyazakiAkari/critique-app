# Critique App

> SNS形式でイラストの添削・リポスト・報酬支払いが可能なWebアプリ

## 📌 プロジェクト概要
- ユーザーはイラストを投稿できる
- 投稿には報酬を設定し、添削者に前払い
- 添削は画像・文字・動画が可能
- 投稿者は複数添削の中からベスト添削を選択可能
- 投稿・添削はいいね、リポスト可能
- フォロー/フォロワー機能あり
- 通知機能あり
- 文字検索可能
- フロント: Vue + Vite + TypeScript
- バックエンド: Laravel + PostgreSQL
- Docker で統合開発環境構築済み

## ⚙ 開発環境

### 必要ソフト
- Docker / docker-compose
- Node.js 20+
- npm
- PHP 8.2+
- Composer

### 初回セットアップ
1. リポジトリをクローン
```bash
git clone git@github.com:MiyazakiAkari/critique-app.git
cd critique-app
2. Docker でコンテナを立ち上げ
docker-compose up -d --build
3. Laravel 初期セットアップ
docker exec -it critique-php bash
composer install
php artisan key:generate
php artisan migrate
exit
4. Vue フロント起動
docker-compose exec frontend npm install
docker-compose exec frontend npm run dev
```

## 🚀 Koyeb へのデプロイ

本番環境へのデプロイについては、[KOYEB_DEPLOYMENT.md](./KOYEB_DEPLOYMENT.md) を参照してください。

要点：
- `Dockerfile` がマルチステージビルド対応
- ポート 8000 でリッスン
- PostgreSQL データベースが必須
- GitHub 連携でワンクリックデプロイ可能

## 💻 開発ルール（チーム向け）

- **ブランチ運用**
  - main ブランチには必ず **動作確認済みコードのみ** push
  - 新機能は `feature/<機能名>` ブランチを作成
  - バグ修正は `fix/<修正内容>` ブランチを作成
- **コミットメッセージ**
  - `feat:` 新機能追加
  - `fix:` バグ修正
  - `docs:` ドキュメント変更
  - `chore:` 雑務・設定変更
- **レビュー**
  - Pull Request は必ず作成
  - 1人以上のレビュー承認が必要
- **Docker/環境**
  - すべての開発メンバーは Docker で同一環境を使用
  - DB マイグレーションは `php artisan migrate` 後、動作確認
- **コード品質**
  - フロント・バックエンドともに TypeScript 型チェック
  - ESLint / PHPStan 導入（後で追加予定）
- **その他**
  - ローカルで動作することを確認してから push
  - 秘密情報は `.env` に置き、絶対に Git に push しない

## 技術スタック
🖥️ フロントエンド
| カテゴリ        | 技術                       | バージョン   |
| ----------- | ------------------------ | ------- |
| フレームワーク     | Vue.js                   | ^3.5.24 |
| 言語          | TypeScript               | ~5.9.3  |
| ビルドツール      | Vite                     | ^7.2.2  |
| ルーティング      | Vue Router               | ^4.6.3  |
| HTTP クライアント | Axios                    | ^1.13.2 |
| CSS         | Tailwind CSS             | ^4.0.0  |
| 決済          | Stripe.js                | ^8.6.0  |
| テスト         | Vitest + Testing Library | ^4.0.14 |

⚙️ バックエンド
| カテゴリ    | 技術              | バージョン   |
| ------- | --------------- | ------- |
| フレームワーク | Laravel         | ^12.0   |
| 言語      | PHP             | ^8.2    |
| 認証      | Laravel Sanctum | ^4.0    |
| 決済      | Stripe PHP SDK  | ^19.1   |
| テスト     | PHPUnit         | ^11.5.3 |
| モック     | Mockery         | ^1.6    |

🗄️ データベース
| 技術         | バージョン |
| ---------- | ----- |
| PostgreSQL | 15    |

🐳 インフラ / DevOps
| カテゴリ     | 技術                      |
| -------- | ----------------------- |
| コンテナ     | Docker / Docker Compose |
| Web サーバー | Nginx                   |
| デプロイ     | ???                     |

## 🧠 技術選定理由
🖥️ フロントエンド：Vue.js

Vue.js を選択した理由は、拡張しやすさに大きな魅力を感じたためです。

テンプレート・ロジック・スタイルを コンポーネント単位で分離できるため、
機能追加や修正時に 既存コードへの影響を最小限に抑えられる

初期実装をシンプルに保ちつつ、後から仕様変更や機能追加を行いやすい

小規模から段階的に成長させる開発スタイルと相性が良い

本プロジェクトでは、
まずは最低限の投稿機能のみを実装し、将来的な仕様変更にも柔軟に対応できる構成を目指しており、
その方針に最も適したフレームワークとして Vue.js を採用しました。

⚙️ バックエンド：Laravel

Laravel は、安全性と設計のしやすさを重視して選定しました。

認証・バリデーション・CSRF 対策など、
セキュリティを考慮した機能が標準で充実している

金銭のやり取りが発生する SNS においても、
安心して設計・実装を進められる基盤が整っている

MVC 構造により 責務分離が明確で、コードの見通しが良い 

個人開発であっても、将来的な機能拡張や保守を見据えた実装が可能な点を大きな利点と感じ、Laravel を採用しました。

---

## ER図
<img width="606" height="987" alt="image" src="https://github.com/user-attachments/assets/d2932ed9-7087-4130-b13b-a37e1703327d" />

