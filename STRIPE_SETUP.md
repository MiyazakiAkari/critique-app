# Stripe決済機能 - セットアップガイド

このアプリケーションには、投稿作成者が添削への謝礼金を設定し、ベスト添削に選ばれた人に報酬を支払う機能が実装されています。

## 機能概要

1. **謝礼金設定**: 投稿作成時に任意の金額を謝礼金として設定
2. **Stripe決済**: Stripeを使って謝礼金を支払い
3. **ベスト添削選択**: 投稿作成者がベスト添削を選択し、謝礼金を支払い
4. **視覚的表示**: 謝礼金の額に応じて目立つ色でバッジ表示

## セットアップ手順

### 1. Stripeアカウントの作成

1. [Stripe](https://stripe.com/)にアクセスしてアカウントを作成
2. ダッシュボードから「開発者」→「APIキー」にアクセス
3. 公開可能キーとシークレットキーを取得

### 2. 環境変数の設定

#### バックエンド (`backend/.env`)

```bash
STRIPE_SECRET_KEY=sk_test_xxxxxxxxxxxxx
STRIPE_PUBLISHABLE_KEY=pk_test_xxxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxx
```

#### フロントエンド (`frontend/.env`)

```bash
VITE_STRIPE_PUBLISHABLE_KEY=pk_test_xxxxxxxxxxxxx
```

### 3. データベースマイグレーション

新しい環境でセットアップする場合は以下を実行:

```bash
docker compose exec php php artisan migrate
```

## 使用方法

### 投稿作成時

1. 投稿フォームで通常通りテキストと画像を入力
2. 「💰」アイコンをクリックして謝礼金設定パネルを開く
3. 謝礼金額を入力（プリセットボタンで簡単設定も可能）
4. 投稿ボタンをクリック
5. Stripe決済フォームが表示されるので、カード情報を入力して支払い

### ベスト添削の選択

1. 自分の投稿に複数の添削が付いた場合、ベスト添削選択UIが表示
2. 最も役に立った添削を選択
3. 「ベスト添削に選択」ボタンをクリック
4. 選択された添削者に謝礼金が支払われます（Stripe Connect設定が必要）

## 謝礼金バッジの色分け

- **¥100-999**: 緑から青のグラデーション
- **¥1,000-4,999**: 黄色からオレンジのグラデーション
- **¥5,000-9,999**: オレンジから赤のグラデーション
- **¥10,000以上**: 紫からピンクのグラデーション（アニメーション付き）

## 注意事項

### テスト環境

- 開発中はStripeのテストモードを使用してください
- テストカード番号: `4242 4242 4242 4242`
- 有効期限: 未来の任意の日付
- CVC: 任意の3桁の数字

### 本番環境への移行

1. Stripeダッシュボードでアカウントを有効化
2. 本番用のAPIキーを取得
3. 環境変数を本番用のキーに更新
4. Stripe Connectを設定して、添削者への送金を有効化

### Stripe Connectの設定（オプション）

添削者が実際に謝礼金を受け取るには、Stripe Connectの設定が必要です:

1. Stripeダッシュボードで「Connect」を有効化
2. 添削者のStripe Connectアカウント登録フローを実装
3. `BestCritiqueController.php`のTODOコメント部分を実装

## トラブルシューティング

### 決済が失敗する

- Stripeキーが正しく設定されているか確認
- テストモードのキーを使用しているか確認
- ブラウザのコンソールでエラーメッセージを確認

### 謝礼金が表示されない

- マイグレーションが正しく実行されているか確認
- PostControllerで謝礼金フィールドが返されているか確認

## 関連ファイル

### バックエンド
- `database/migrations/2026_01_01_055805_add_reward_to_posts_table.php`
- `app/Http/Controllers/Api/StripeController.php`
- `app/Http/Controllers/Api/BestCritiqueController.php`
- `app/Models/Post.php`
- `routes/api.php`

### フロントエンド
- `src/components/RewardBadge.vue`
- `src/components/RewardAmountSelector.vue`
- `src/components/StripePaymentForm.vue`
- `src/components/BestCritiqueSelector.vue`
- `src/pages/HomePage.vue`
