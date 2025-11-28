<template>
  <div class="min-h-screen bg-gray-50 flex justify-center">
    <!-- 左サイドバー -->
    <aside class="w-64 bg-white border-r border-gray-200 fixed left-0 h-full xl:left-auto xl:relative">
      <div class="p-4">
        <h1 class="text-2xl font-bold text-blue-600 mb-8 cursor-pointer" @click="router.push('/home')">Critique</h1>
        
        <nav class="space-y-2">
          <a @click="router.push('/home')" class="flex items-center space-x-4 px-4 py-3 rounded-full hover:bg-gray-100 text-gray-600 cursor-pointer">
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
          
          <a href="#" class="flex items-center space-x-4 px-4 py-3 rounded-full hover:bg-gray-100 text-gray-800 font-semibold">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
            </svg>
            <span>プロフィール</span>
          </a>
        </nav>
      </div>
    </aside>

    <!-- メインコンテンツ -->
    <main class="flex-1 max-w-2xl border-x border-gray-200 bg-white">
      <div v-if="loading" class="p-8 text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500 mx-auto"></div>
      </div>

      <div v-else-if="error" class="p-8 text-center text-red-600">
        {{ error }}
      </div>

      <div v-else>
        <!-- ヘッダー -->
        <div class="sticky top-0 bg-white border-b border-gray-200 z-10 px-4 py-3">
          <div class="flex items-center space-x-4">
            <button @click="router.push('/home')" class="p-2 hover:bg-gray-100 rounded-full">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
              </svg>
            </button>
            <div>
              <h1 class="text-xl font-bold">{{ user?.name }}</h1>
              <p class="text-sm text-gray-500">@{{ user?.username }}</p>
            </div>
          </div>
        </div>

        <!-- カバー画像エリア -->
        <div class="h-48 bg-gradient-to-r from-blue-400 to-purple-500"></div>

        <!-- プロフィール情報 -->
        <div class="px-4 pb-4">
          <div class="flex justify-between items-start -mt-16 mb-4">
            <div class="w-32 h-32 bg-gray-300 rounded-full border-4 border-white overflow-hidden">
              <img v-if="profile?.avatar_url" :src="profile.avatar_url" alt="Avatar" class="w-full h-full object-cover" />
            </div>
            
            <button 
              v-if="isOwnProfile"
              @click="isEditing = true"
              class="mt-20 px-4 py-2 border border-gray-300 rounded-full font-semibold hover:bg-gray-50"
            >
              プロフィールを編集
            </button>
            
            <button 
              v-else
              @click="handleFollow"
              :disabled="followLoading"
              :class="[
                'mt-20 px-4 py-2 rounded-full font-semibold transition-colors',
                followStatus.is_following 
                  ? 'bg-white border border-gray-300 text-gray-900 hover:bg-red-50 hover:text-red-600 hover:border-red-300' 
                  : 'bg-blue-500 text-white hover:bg-blue-600',
                followLoading ? 'opacity-50 cursor-not-allowed' : ''
              ]"
            >
              {{ followLoading ? '処理中...' : (followStatus.is_following ? 'フォロー中' : 'フォローする') }}
            </button>
          </div>

          <div class="mt-4">
            <h2 class="text-2xl font-bold">{{ user?.name }}</h2>
            <p class="text-gray-500">@{{ user?.username }}</p>
            
            <p v-if="profile?.bio" class="mt-3 text-gray-800">{{ profile.bio }}</p>
            <p v-else class="mt-3 text-gray-400 italic">自己紹介がまだ設定されていません</p>
            
            <div class="flex items-center space-x-2 mt-3 text-gray-500">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
              </svg>
              <span class="text-sm">{{ formatDate(user?.created_at) }} に登録</span>
            </div>

            <div class="flex space-x-6 mt-3">
              <div>
                <span class="font-bold">{{ followStatus.followings_count }}</span>
                <span class="text-gray-500 ml-1">フォロー中</span>
              </div>
              <div>
                <span class="font-bold">{{ followStatus.followers_count }}</span>
                <span class="text-gray-500 ml-1">フォロワー</span>
              </div>
            </div>
          </div>
        </div>

        <!-- タブ -->
        <div class="border-b border-gray-200">
          <div class="flex">
            <button class="flex-1 py-4 font-semibold text-gray-900 relative">
              投稿
              <div class="absolute bottom-0 left-0 right-0 h-1 bg-blue-500 rounded-full"></div>
            </button>
            <button class="flex-1 py-4 font-semibold text-gray-500">
              いいね
            </button>
          </div>
        </div>

        <!-- 投稿一覧 -->
        <div class="p-8 text-center text-gray-500">
          まだ投稿がありません
        </div>
      </div>
    </main>

    <!-- 編集モーダル -->
    <div v-if="isEditing" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
          <div class="flex items-center space-x-4">
            <button @click="cancelEdit" class="p-2 hover:bg-gray-100 rounded-full">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
            <h2 class="text-xl font-bold">プロフィールを編集</h2>
          </div>
          <button 
            @click="saveProfile" 
            :disabled="saving"
            class="bg-blue-500 text-white px-4 py-2 rounded-full font-semibold hover:bg-blue-600 disabled:opacity-50"
          >
            {{ saving ? '保存中...' : '保存' }}
          </button>
        </div>

        <div class="p-4">
          <!-- カバー画像 -->
          <div class="h-48 bg-gradient-to-r from-blue-400 to-purple-500 rounded-t-xl relative">
            <button class="absolute inset-0 m-auto w-10 h-10 bg-black bg-opacity-50 hover:bg-opacity-70 rounded-full flex items-center justify-center">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
            </button>
          </div>

          <!-- アバター -->
          <div class="flex justify-between items-start -mt-16 mb-4 px-4">
            <div class="relative">
              <div class="w-32 h-32 bg-gray-300 rounded-full border-4 border-white overflow-hidden">
                <img v-if="editForm.avatar_url" :src="editForm.avatar_url" alt="Avatar" class="w-full h-full object-cover" />
              </div>
              <label class="absolute inset-0 m-auto w-10 h-10 bg-black bg-opacity-50 hover:bg-opacity-70 rounded-full flex items-center justify-center cursor-pointer">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <input type="file" accept="image/*" @change="handleAvatarUpload" class="hidden" />
              </label>
            </div>
          </div>

          <!-- フォーム -->
          <div class="space-y-10 mt-8 px-4">
            <div>
              <label class="block text-sm font-semibold text-gray-700 mb-1">自己紹介</label>
              <textarea 
                v-model="editForm.bio" 
                rows="10" 
                maxlength="500"
                placeholder="自己紹介を入力してください"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none"
              ></textarea>
              <div class="text-right text-sm text-gray-500 mt-1">
                {{ editForm.bio?.length || 0 }} / 500
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import api from '../utils/axios';

const router = useRouter();
const route = useRoute();

const user = ref<any>(null);
const profile = ref<any>(null);
const loading = ref(true);
const error = ref('');
const isEditing = ref(false);
const saving = ref(false);
const followStatus = ref({
  is_following: false,
  followers_count: 0,
  followings_count: 0,
});
const followLoading = ref(false);

const editForm = ref({
  bio: '',
  avatar_url: '',
});

const isOwnProfile = computed(() => {
  const authUser = JSON.parse(localStorage.getItem('auth_user') || '{}');
  return user.value?.username === authUser.username;
});

const formatDate = (dateString: string) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('ja-JP', { year: 'numeric', month: 'long' });
};

const fetchProfile = async () => {
  try {
    loading.value = true;
    error.value = '';

    const username = route.params.username as string;
    
    let response;
    if (isOwnProfile.value) {
      response = await api.get('/profile/me');
    } else {
      response = await api.get(`/profile/${username}`);
    }

    user.value = response.data.user;
    profile.value = response.data.profile;
    
    // 編集フォームを初期化
    editForm.value = {
      bio: profile.value?.bio || '',
      avatar_url: profile.value?.avatar_url || '',
    };

    // フォロー状態を取得（自分のプロフィールの場合は不要）
    if (!isOwnProfile.value) {
      await fetchFollowStatus(username);
    }
  } catch (e: any) {
    console.error('Profile fetch error:', e);
    error.value = e.response?.data?.message || 'プロフィールの取得に失敗しました';
  } finally {
    loading.value = false;
  }
};

const fetchFollowStatus = async (username: string) => {
  try {
    const response = await api.get(`/users/${username}/follow-status`);
    followStatus.value = response.data;
  } catch (e) {
    console.error('Follow status fetch error:', e);
  }
};

const handleFollow = async () => {
  if (!user.value) return;

  try {
    followLoading.value = true;
    const username = user.value.username;

    if (followStatus.value.is_following) {
      await api.delete(`/users/${username}/follow`);
      followStatus.value.is_following = false;
      followStatus.value.followers_count--;
    } else {
      await api.post(`/users/${username}/follow`);
      followStatus.value.is_following = true;
      followStatus.value.followers_count++;
    }
  } catch (e: any) {
    console.error('Follow error:', e);
    alert(e.response?.data?.message || 'フォロー操作に失敗しました');
  } finally {
    followLoading.value = false;
  }
};

const handleAvatarUpload = async (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  
  if (!file) return;

  try {
    const formData = new FormData();
    formData.append('avatar', file);

    const response = await api.post('/profile/avatar', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });

    editForm.value.avatar_url = response.data.avatar_url;
  } catch (e: any) {
    console.error('Avatar upload error:', e);
    alert('アバターのアップロードに失敗しました');
  }
};

const saveProfile = async () => {
  try {
    saving.value = true;

    await api.put('/profile', {
      bio: editForm.value.bio,
      avatar_url: editForm.value.avatar_url,
    });

    profile.value = {
      ...profile.value,
      bio: editForm.value.bio,
      avatar_url: editForm.value.avatar_url,
    };

    isEditing.value = false;
  } catch (e: any) {
    console.error('Profile update error:', e);
    alert('プロフィールの更新に失敗しました');
  } finally {
    saving.value = false;
  }
};

const cancelEdit = () => {
  editForm.value = {
    bio: profile.value?.bio || '',
    avatar_url: profile.value?.avatar_url || '',
  };
  isEditing.value = false;
};

onMounted(() => {
  fetchProfile();
});
</script>

<style scoped>
</style>
