FROM node:20-alpine

# 作業ディレクトリ
WORKDIR /app

# パッケージをコピー
COPY package*.json ./

# npm を確実に使えるようにする（corepack問題回避）
RUN corepack enable

# 依存関係をインストール
RUN npm install

# プロジェクト全体をコピー
COPY . .

# Vite の開発サーバー起動
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
