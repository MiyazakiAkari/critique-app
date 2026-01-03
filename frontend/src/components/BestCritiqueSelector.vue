<template>
  <div class="bg-white rounded-lg shadow-md p-6">
    <h3 class="text-lg font-bold mb-4">ベスト添削を選択</h3>
    
    <div v-if="!bestCritiqueSelected" class="space-y-4">
      <p class="text-gray-700">
        添削の中から最も役に立った添削を選択してください。
        <br/>
        選択された添削者に<span class="font-bold text-blue-600">¥{{ rewardAmount.toLocaleString() }}</span>の謝礼金が支払われます。
      </p>

      <div class="space-y-3">
        <div
          v-for="critique in critiques"
          :key="critique.id"
          class="border border-gray-200 rounded-lg p-4 hover:border-blue-400 transition cursor-pointer"
          :class="{ 'border-blue-500 bg-blue-50': selectedCritiqueId === critique.id }"
          @click="selectedCritiqueId = critique.id"
        >
          <div class="flex items-start gap-3">
            <input
              type="radio"
              :id="`critique-${critique.id}`"
              :value="critique.id"
              v-model="selectedCritiqueId"
              class="mt-1"
            />
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                <img
                  v-if="critique.user.profile?.avatar_url"
                  :src="critique.user.profile.avatar_url"
                  :alt="critique.user.username"
                  class="w-8 h-8 rounded-full"
                />
                <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center" v-else>
                  <span class="text-gray-500 text-sm">{{ critique.user?.username?.[0]?.toUpperCase() ?? '?' }}</span>
                </div>
                <span class="font-medium">@{{ critique.user.username }}</span>
              </div>
              <p class="text-gray-700 text-sm">{{ critique.content }}</p>
              <p class="text-xs text-gray-500 mt-2">
                {{ formatDate(critique.created_at) }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <button
        @click="selectBestCritique"
        :disabled="!selectedCritiqueId || isProcessing"
        class="w-full py-3 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition"
      >
        <span v-if="isProcessing">処理中...</span>
        <span v-else>ベスト添削に選択</span>
      </button>

      <p v-if="errorMessage" class="text-red-500 text-sm text-center">
        {{ errorMessage }}
      </p>
    </div>

    <div v-else class="text-center py-8">
      <svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
      </svg>
      <h4 class="text-xl font-bold text-gray-900 mb-2">ベスト添削を選択しました</h4>
      <p class="text-gray-600">謝礼金の支払い処理が完了しました</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';

interface User {
  id: number;
  username: string;
  profile?: {
    avatar_url?: string;
  };
}

interface Critique {
  id: number;
  content: string;
  created_at: string;
  user: User;
}

const props = defineProps<{
  postId: number;
  critiques: Critique[];
  rewardAmount: number;
}>();

const emit = defineEmits<{
  'critique-selected': [critiqueId: number];
  'selection-error': [error: string];
}>();

const selectedCritiqueId = ref<number | null>(null);
const isProcessing = ref(false);
const bestCritiqueSelected = ref(false);
const errorMessage = ref('');

const formatDate = (dateString: string) => {
  const date = new Date(dateString);
  return new Intl.DateTimeFormat('ja-JP', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(date);
};

const selectBestCritique = async () => {
  if (!selectedCritiqueId.value) return;

  isProcessing.value = true;
  errorMessage.value = '';

  try {
    await axios.post(`/api/posts/${props.postId}/best-critique`, {
      critique_id: selectedCritiqueId.value,
    });

    bestCritiqueSelected.value = true;
    emit('critique-selected', selectedCritiqueId.value);
  } catch (error: any) {
    console.error('ベスト添削選択エラー:', error);
    
    // エラータイプに応じたメッセージを設定
    if (error.code === 'ECONNABORTED' || error.message?.includes('timeout')) {
      errorMessage.value = 'リクエストがタイムアウトしました。もう一度お試しください。';
    } else if (!error.response) {
      // ネットワークエラー（サーバーに到達できない）
      errorMessage.value = 'ネットワークエラーが発生しました。接続を確認してください。';
    } else if (error.response.status === 401) {
      errorMessage.value = '認証エラーが発生しました。再ログインしてください。';
    } else if (error.response.status === 403) {
      errorMessage.value = 'この操作を行う権限がありません。';
    } else if (error.response.status === 404) {
      errorMessage.value = '対象の投稿または添削が見つかりませんでした。';
    } else if (error.response.status === 422) {
      errorMessage.value = error.response.data?.message || error.response.data?.error || '入力内容に問題があります。';
    } else if (error.response.status >= 500) {
      errorMessage.value = 'サーバーエラーが発生しました。しばらくしてからお試しください。';
    } else {
      errorMessage.value = error.response.data?.error || error.response.data?.message || 'ベスト添削の選択に失敗しました';
    }
    
    emit('selection-error', errorMessage.value);
  } finally {
    isProcessing.value = false;
  }
};
</script>
