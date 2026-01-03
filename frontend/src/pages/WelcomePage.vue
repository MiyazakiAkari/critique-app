<template>
  <div class="min-h-screen bg-gray-50">
    <!-- ヘッダー -->
    <header class="bg-white border-b border-gray-200 sticky top-0 z-50">
      <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-blue-600">Critique</h1>
        <nav class="flex items-center space-x-4">
          <router-link 
            to="/home" 
            class="text-gray-600 hover:text-blue-600 font-medium transition"
          >
            投稿を見てみる
          </router-link>
          <router-link 
            to="/login" 
            class="text-gray-600 hover:text-blue-600 font-medium transition"
          >
            ログイン
          </router-link>
          <router-link 
            to="/register" 
            class="bg-blue-500 text-white px-4 py-2 rounded-full font-semibold hover:bg-blue-600 transition"
          >
            新規登録
          </router-link>
        </nav>
      </div>
    </header>

    <!-- メインコンテンツ -->
    <main class="max-w-6xl mx-auto px-4 py-16">
      <!-- ヒーローセクション -->
      <section class="text-center mb-20">
        <h2 class="text-4xl md:text-5xl font-bold text-gray-900 mb-6">
          あなたの作品を、<br class="md:hidden" />
          <span class="text-blue-600">もっと良く</span>する場所
        </h2>
        <p class="text-lg text-gray-600 mb-8 max-w-2xl mx-auto">
          Critiqueは、クリエイターが作品を投稿し、プロから的確なフィードバックを受けられるプラットフォームです。
        </p>
        <router-link 
          to="/register" 
          class="inline-block bg-blue-500 text-white px-8 py-4 rounded-full font-semibold text-lg hover:bg-blue-600 transition shadow-lg hover:shadow-xl"
        >
          無料で始める
        </router-link>
      </section>

      <!-- 機能説明セクション -->
      <section class="grid md:grid-cols-2 gap-8">
        <!-- 添削を受けたい人向け -->
        <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-200 hover:shadow-lg transition">
          <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-6">
            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
          </div>
          <h3 class="text-2xl font-bold text-gray-900 mb-4">添削を受けたい人は</h3>
          <ul class="space-y-3 text-gray-600">
            <li class="flex items-start">
              <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span>作品の画像を投稿して、フィードバックを募集</span>
            </li>
            <li class="flex items-start">
              <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span>謝礼金を設定して、質の高い添削を依頼</span>
            </li>
            <li class="flex items-start">
              <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span>最も役に立った添削を選んで、謝礼金を支払い</span>
            </li>
            <li class="flex items-start">
              <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span>プロの視点で、作品のクオリティを向上</span>
            </li>
          </ul>
        </div>

        <!-- 添削をしたい人向け -->
        <div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-200 hover:shadow-lg transition">
          <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-6">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
            </svg>
          </div>
          <h3 class="text-2xl font-bold text-gray-900 mb-4">添削をしたい人は</h3>
          <ul class="space-y-3 text-gray-600">
            <li class="flex items-start">
              <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span>投稿された作品に対して、添削コメントを投稿</span>
            </li>
            <li class="flex items-start">
              <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span>あなたの添削が選ばれると、謝礼金を獲得</span>
            </li>
            <li class="flex items-start">
              <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span>スキルを活かして、クリエイターをサポート</span>
            </li>
            <li class="flex items-start">
              <svg class="w-6 h-6 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
              <span>添削実績を積んで、信頼を構築</span>
            </li>
          </ul>
        </div>
      </section>

      <!-- CTA セクション -->
      <section class="text-center mt-20">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-2xl p-12 text-white">
          <h3 class="text-3xl font-bold mb-4">今すぐ始めよう</h3>
          <p class="text-blue-100 mb-8">無料で登録して、Critiqueコミュニティに参加しましょう。</p>
          <router-link 
            to="/register" 
            class="inline-block bg-white text-blue-600 px-8 py-4 rounded-full font-semibold text-lg hover:bg-gray-100 transition"
          >
            新規登録
          </router-link>
        </div>
      </section>
    </main>

    <!-- フッター -->
    <footer class="bg-white border-t border-gray-200 py-8 mt-16">
      <div class="max-w-6xl mx-auto px-4 text-center text-gray-500">
        <p>&copy; 2026 Critique. All rights reserved.</p>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
// Welcomeページはスタティックなのでスクリプトは最小限
</script>
