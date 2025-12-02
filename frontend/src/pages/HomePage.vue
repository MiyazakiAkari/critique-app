<template>
  <div class="min-h-screen bg-gray-50 flex justify-center">
    <!-- 左サイドバー -->
    <aside class="w-64 bg-white border-r border-gray-200 fixed left-0 h-full xl:left-auto xl:relative">
      <div class="p-4">
        <h1 class="text-2xl font-bold text-blue-600 mb-8">Critique</h1>
        
        <nav class="space-y-2">
          <a href="#" class="flex items-center space-x-4 px-4 py-3 rounded-full hover:bg-gray-100 text-gray-800 font-semibold">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
            <span>ホーム</span>
          </a>
          
          <a href="#" class="flex items-center space-x-4 px-4 py-3 rounded-full hover:bg-gray-100 text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <span>検索</span>
          </a>
          
          <a href="#" class="flex items-center space-x-4 px-4 py-3 rounded-full hover:bg-gray-100 text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
            <span>通知</span>
          </a>
          
          <a @click="goToProfile" class="flex items-center space-x-4 px-4 py-3 rounded-full hover:bg-gray-100 text-gray-600 cursor-pointer">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            <span>プロフィール</span>
          </a>
        </nav>
        
        <button class="w-full mt-6 bg-blue-500 text-white py-3 rounded-full font-semibold hover:bg-blue-600 transition">
          投稿する
        </button>
      </div>
    </aside>

    <!-- メインコンテンツ -->
    <main class="flex-1 max-w-2xl border-x border-gray-200 bg-white">
      <!-- ヘッダータブ -->
      <div class="sticky top-0 bg-white border-b border-gray-200 z-10">
        <div class="flex">
          <button 
            @click="handleTabChange('recommended')" 
            :class="[
              'flex-1 py-4 font-semibold hover:bg-gray-50 transition relative',
              activeTab === 'recommended' ? 'text-gray-900' : 'text-gray-500'
            ]"
          >
            おすすめ
            <div v-if="activeTab === 'recommended'" class="absolute bottom-0 left-0 right-0 h-1 bg-blue-500 rounded-full"></div>
          </button>
          
          <button 
            @click="handleTabChange('following')" 
            :class="[
              'flex-1 py-4 font-semibold hover:bg-gray-50 transition relative',
              activeTab === 'following' ? 'text-gray-900' : 'text-gray-500'
            ]"
          >
            フォロー中
            <div v-if="activeTab === 'following'" class="absolute bottom-0 left-0 right-0 h-1 bg-blue-500 rounded-full"></div>
          </button>
        </div>
      </div>

      <!-- 投稿フォーム -->
      <div class="border-b border-gray-200 p-4">
        <div class="flex space-x-3">
          <div class="w-12 h-12 bg-gray-300 rounded-full flex-shrink-0"></div>
          <div class="flex-1">
            <textarea 
              v-model="newPostContent"
              placeholder="いまどうしてる？" 
              class="w-full text-xl outline-none resize-none"
              rows="3"
            ></textarea>
            <div class="flex justify-between items-center mt-3">
              <div class="flex space-x-2 text-blue-500">
                <button class="p-2 hover:bg-blue-50 rounded-full">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                  </svg>
                </button>
              </div>
              <button 
                @click="createPost"
                :disabled="!newPostContent.trim() || posting"
                class="bg-blue-500 text-white px-4 py-2 rounded-full font-semibold hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                {{ posting ? '投稿中...' : '投稿する' }}
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- タイムライン -->
      <div>
        <div v-if="loading" class="p-8 text-center">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
        </div>

        <div v-else-if="error" class="p-8 text-center text-red-600">
          {{ error }}
        </div>

        <div v-else-if="displayPosts.length === 0" class="p-8 text-center text-gray-500">
          <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
          </svg>
          <p class="text-lg font-semibold mb-2">まだ何もありません</p>
          <p class="text-sm">
            {{ activeTab === 'following' ? 'フォローしているユーザーがまだ投稿していません' : '投稿がまだありません。最初の投稿をしてみましょう！' }}
          </p>
        </div>

        <div v-else v-for="post in displayPosts" :key="post.id" class="border-b border-gray-200 p-4 hover:bg-gray-50 cursor-pointer transition">
          <div class="flex space-x-3">
            <div class="w-12 h-12 bg-gray-300 rounded-full flex-shrink-0"></div>
            <div class="flex-1">
              <div class="flex items-center space-x-2">
                <span class="font-semibold text-gray-900">{{ post.user.name }}</span>
                <span class="text-gray-500">@{{ post.user.username }}</span>
                <span class="text-gray-500">·</span>
                <span class="text-gray-500">{{ formatRelativeTime(post.created_at) }}</span>
              </div>
              <p class="mt-1 text-gray-800">{{ post.content }}</p>
              
              <div class="flex justify-between mt-3 max-w-md text-gray-500">
                <button class="flex items-center space-x-2 hover:text-blue-500 group">
                  <svg class="w-5 h-5 group-hover:bg-blue-50 rounded-full p-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                  </svg>
                  <span class="text-sm">0</span>
                </button>
                
                <button class="flex items-center space-x-2 hover:text-green-500 group">
                  <svg class="w-5 h-5 group-hover:bg-green-50 rounded-full p-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                  </svg>
                  <span class="text-sm">0</span>
                </button>
                
                <button class="flex items-center space-x-2 hover:text-red-500 group">
                  <svg class="w-5 h-5 group-hover:bg-red-50 rounded-full p-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                  </svg>
                  <span class="text-sm">0</span>
                </button>
                
                <button class="flex items-center space-x-2 hover:text-blue-500 group">
                  <svg class="w-5 h-5 group-hover:bg-blue-50 rounded-full p-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- 右サイドバーは現在非表示 -->
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../utils/axios';

interface Post {
  id: number;
  content: string;
  created_at: string;
  user: {
    id: number;
    name: string;
    username: string;
  };
}
const router = useRouter();
const activeTab = ref<'recommended' | 'following'>('recommended');
// 投稿データ
const recommendedPosts = ref<Post[]>([]);
const followingPosts = ref<Post[]>([]);
const loading = ref(false);
const error = ref('');

// 新規投稿フォーム
const newPostContent = ref('');
const posting = ref(false);

// プロフィールページへ遷移
const goToProfile = () => {
  const authUser = JSON.parse(localStorage.getItem('auth_user') || '{}');
  if (authUser.username) {
    router.push(`/profile/${authUser.username}`);
  }
};

// おすすめ投稿を取得
const fetchRecommendedPosts = async () => {
  try {
    loading.value = true;
    error.value = '';
    const response = await api.get('/posts/recommended');
    recommendedPosts.value = response.data.posts;
  } catch (e: any) {
    console.error('Failed to fetch recommended posts:', e);
    console.error('Error response:', e.response?.data);
    error.value = e.response?.data?.message || 'おすすめ投稿の取得に失敗しました';
  } finally {
    loading.value = false;
  }
};

// フォロー中のタイムラインを取得
const fetchTimeline = async () => {
  try {
    loading.value = true;
    error.value = '';
    
    // 認証トークンの確認
    const token = localStorage.getItem('auth_token');
    console.log('Token exists:', !!token);
    console.log('Authorization header:', api.defaults.headers.common.Authorization);
    
    const response = await api.get('/posts/timeline');
    followingPosts.value = response.data.posts;
  } catch (e: any) {
    console.error('Failed to fetch timeline:', e);
    console.error('Error response:', e.response?.data);
    console.error('Status code:', e.response?.status);
    error.value = e.response?.data?.message || 'タイムラインの取得に失敗しました';
  } finally {
    loading.value = false;
  }
};

// 新規投稿を作成
const createPost = async () => {
  if (!newPostContent.value.trim()) return;
  
  try {
    posting.value = true;
    const response = await api.post('/posts', {
      content: newPostContent.value,
    });
    
    // 投稿をリストの先頭に追加
    const newPost = response.data.post;
    recommendedPosts.value.unshift(newPost);
    followingPosts.value.unshift(newPost);
    
    newPostContent.value = '';
  } catch (e: any) {
    console.error('Failed to create post:', e);
    console.error('Error response:', e.response?.data);
    const errorMessage = e.response?.data?.message || '投稿に失敗しました';
    error.value = errorMessage;
  } finally {
    posting.value = false;
  }
};

// 表示する投稿を計算
const displayPosts = computed(() => {
  return activeTab.value === 'recommended' ? recommendedPosts.value : followingPosts.value;
});

// 相対時間を表示
const formatRelativeTime = (dateString: string) => {
  if (!dateString) return '';
  
  const date = new Date(dateString);
  if (isNaN(date.getTime())) {
    return dateString; // fallback to original string
  }
  
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);
  
  if (diffMins < 1) return 'たった今';
  if (diffMins < 60) return `${diffMins}分前`;
  if (diffHours < 24) return `${diffHours}時間前`;
  if (diffDays < 7) return `${diffDays}日前`;
  
  return date.toLocaleDateString('ja-JP', { month: 'long', day: 'numeric' });
};

// タブ切り替え時にデータを取得
const handleTabChange = async (tab: 'recommended' | 'following') => {
  activeTab.value = tab;
  if (tab === 'recommended') {
    await fetchRecommendedPosts();
  } else if (tab === 'following') {
    await fetchTimeline();
  }
};

// 初回マウント時にデータを取得
onMounted(async () => {
  await fetchRecommendedPosts();
});
</script>

<style scoped>
/* カスタムスタイルが必要な場合はここに追加 */
</style>
