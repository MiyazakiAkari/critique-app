<template>
  <div class="min-h-screen bg-gray-50 flex justify-center">
    <!-- 左サイドバー -->
    <SidebarMenu />

    <!-- メインコンテンツ -->
    <main class="flex-1 max-w-2xl border-x border-gray-200 bg-white">
      <!-- 検索ヘッダー -->
      <div class="sticky top-0 bg-white border-b border-gray-200 z-10 p-4">
        <div class="flex items-center bg-gray-100 rounded-full px-4 py-2">
          <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
          <input 
            v-model="keyword"
            @keyup.enter="performSearch"
            @input="handleInput"
            type="text"
            placeholder="ユーザー名または名前で検索" 
            class="flex-1 bg-gray-100 ml-2 outline-none text-sm"
          />
        </div>
      </div>

      <!-- 検索結果 -->
      <div v-if="hasSearched" class="divide-y border-gray-200">
        <!-- ローディング状態 -->
        <div v-if="isLoading" class="p-4 text-center text-gray-500">
          <div class="inline-block animate-spin">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
          </div>
        </div>

        <!-- エラーメッセージ -->
        <div v-else-if="errorMessage" class="p-4 text-center text-red-600">
          {{ errorMessage }}
        </div>

        <!-- 結果がない場合 -->
        <div v-else-if="searchResults.length === 0" class="p-8 text-center text-gray-500">
          <svg class="w-12 h-12 mx-auto mb-2 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
          </svg>
          <p class="text-lg font-semibold">ユーザーが見つかりません</p>
          <p class="text-sm">別のキーワードで試してみてください</p>
        </div>

        <!-- 検索結果リスト -->
        <div v-else>
          <div v-for="user in searchResults" :key="user.id" class="p-4 hover:bg-gray-50 cursor-pointer transition border-b border-gray-100 last:border-b-0" @click="goToUserProfile(user.username)">
            <div class="flex items-start space-x-3">
              <div class="w-12 h-12 bg-gradient-to-br from-blue-400 to-purple-500 rounded-full flex-shrink-0"></div>
              <div class="flex-1 min-w-0">
                <div class="flex items-center space-x-2">
                  <p class="font-bold text-gray-900 truncate">{{ user.name }}</p>
                  <p class="text-gray-500 text-sm truncate">@{{ user.username }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 初期状態 -->
      <div v-else class="p-8 text-center text-gray-500">
        <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
        <p class="text-lg font-semibold text-gray-600">ユーザーを検索</p>
        <p class="text-sm">ユーザー名または名前を入力してください</p>
      </div>
    </main>

    <!-- 右サイドバー（空） -->
    <aside class="w-80 bg-gray-50 border-l border-gray-200 hidden lg:block"></aside>
  </div>
</template>

<script setup lang="ts">
import { ref, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import api from '../utils/axios'
import SidebarMenu from '../components/SidebarMenu.vue'

interface User {
  id: number
  name: string
  username: string
}

const router = useRouter()
const keyword = ref('')
const searchResults = ref<User[]>([])
const isLoading = ref(false)
const errorMessage = ref('')
const hasSearched = ref(false)
const searchTimeout = ref<ReturnType<typeof setTimeout> | null>(null)

// コンポーネントアンマウント時にタイムアウトをクリア
onUnmounted(() => {
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
})

// Enterキーまたは入力で検索を実行
const handleInput = () => {
  // デバウンス処理：入力後300msで検索
  if (searchTimeout.value) {
    clearTimeout(searchTimeout.value)
  }
  
  if (!keyword.value.trim()) {
    hasSearched.value = false
    return
  }

  searchTimeout.value = setTimeout(() => {
    performSearch()
  }, 300)
}

const performSearch = async () => {
  if (!keyword.value.trim()) {
    return
  }

  isLoading.value = true
  errorMessage.value = ''
  hasSearched.value = true

  try {
    const response = await api.get('/users/search', {
      params: {
        keyword: keyword.value
      }
    })
    
    searchResults.value = response.data.users || []
  } catch (error: any) {
    // axios エラーレスポンスの処理
    if (error.response?.data?.message) {
      errorMessage.value = error.response.data.message
    } else if (error.response?.status === 422) {
      // バリデーションエラー
      const errors = error.response.data.errors
      if (errors && typeof errors === 'object') {
        const errorMessages = Object.values(errors).flat().join(', ')
        errorMessage.value = errorMessages as string
      } else {
        errorMessage.value = 'バリデーションエラーが発生しました'
      }
    } else if (error.response?.status) {
      // その他の HTTP エラー
      errorMessage.value = `エラー (${error.response.status}): サーバーエラーが発生しました`
    } else if (error instanceof Error) {
      // ネットワークエラーなど
      errorMessage.value = error.message
    } else {
      errorMessage.value = '検索中にエラーが発生しました'
    }
  } finally {
    isLoading.value = false
  }
}

const goToUserProfile = (username: string) => {
  router.push(`/profile/${username}`)
}
</script>

<style scoped>
.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
