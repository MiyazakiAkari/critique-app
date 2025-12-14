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
            
            <!-- 画像プレビュー -->
            <div v-if="imagePreview" class="relative mt-3 rounded-2xl overflow-hidden border border-gray-200">
              <img :src="imagePreview" alt="Preview" class="w-full max-h-96 object-cover" />
              <button 
                @click="removeImage"
                class="absolute top-2 right-2 bg-gray-900 bg-opacity-75 text-white rounded-full p-2 hover:bg-opacity-90"
                aria-label="画像を削除"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
            
            <div class="flex justify-between items-center mt-3">
              <div class="flex space-x-2 text-blue-500">
                <input 
                  type="file" 
                  id="image-upload" 
                  accept="image/*" 
                  class="hidden"
                  @change="handleImageSelect"
                />
                <label for="image-upload" class="p-2 hover:bg-blue-50 rounded-full cursor-pointer" aria-label="画像を選択">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                  </svg>
                </label>
              </div>
              <button 
                @click="createPost"
                :disabled="!newPostContent.trim() || !selectedImage || posting"
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
              <div class="flex items-center space-x-2 justify-between">
                <div class="flex items-center space-x-2">
                  <span class="font-semibold text-gray-900">{{ post.user.name }}</span>
                  <span class="text-gray-500">@{{ post.user.username }}</span>
                  <span class="text-gray-500">·</span>
                  <span class="text-gray-500">{{ formatRelativeTime(post.created_at) }}</span>
                </div>
                
                <!-- 投稿三点リーダーメニュー -->
                <div v-if="authUser && authUser.id === post.user.id" class="relative">
                  <button 
                    @click.stop="togglePostMenu(post.id)"
                    class="text-gray-400 hover:text-blue-500 hover:bg-blue-50 rounded-full p-1 transition"
                    title="メニュー"
                    aria-label="投稿メニューを開く"
                    :aria-expanded="openPostMenuId === post.id"
                  >
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"></path>
                    </svg>
                  </button>
                  
                  <!-- ドロップダウンメニュー -->
                  <Transition name="dropdown">
                    <div 
                      v-if="openPostMenuId === post.id"
                      class="absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-20"
                      role="menu"
                      @click.stop
                      @keydown.esc="togglePostMenu(post.id)"
                    >
                      <button 
                        @click="deletePost(post.id)"
                        @keydown.enter="deletePost(post.id)"
                        class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center space-x-2"
                        role="menuitem"
                      >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        <span>投稿を削除</span>
                      </button>
                    </div>
                  </Transition>
                </div>
              </div>
              <p class="mt-1 text-gray-800 whitespace-pre-wrap">{{ post.content }}</p>
              
              <!-- 投稿画像 -->
              <div v-if="post.image_url" class="mt-3 rounded-2xl overflow-hidden border border-gray-200 cursor-pointer" @click="openImageModal(post.image_url)">
                <img :src="post.image_url" alt="投稿画像" class="w-full max-h-96 object-cover hover:opacity-95 transition" />
              </div>
              
              <div class="flex justify-between mt-3 max-w-md text-gray-500">
                <button 
                  @click="togglePostExpansion(post.id)"
                  class="flex items-center space-x-2 hover:text-blue-500 group"
                >
                  <svg class="w-5 h-5 group-hover:bg-blue-50 rounded-full p-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                  </svg>
                  <span class="text-sm">{{ critiquesMap[post.id]?.length || 0 }}</span>
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
              
              <!-- 添削エリア（展開時） -->
              <div v-if="expandedPosts.has(post.id)" class="mt-4 border-t border-gray-200 pt-4">
                <!-- 添削一覧 -->
                <div v-if="critiquesMap[post.id]?.length" class="space-y-3 mb-4">
                  <div 
                    v-for="critique in critiquesMap[post.id]" 
                    :key="critique.id"
                    class="flex space-x-2 pl-2 border-l-2 border-blue-200"
                  >
                    <div class="w-8 h-8 bg-gray-300 rounded-full flex-shrink-0"></div>
                    <div class="flex-1">
                      <div class="flex items-center space-x-2">
                        <span class="font-semibold text-sm text-gray-900">{{ critique.user.name }}</span>
                        <span class="text-gray-500 text-sm">@{{ critique.user.username }}</span>
                        <span class="text-gray-500 text-sm">·</span>
                        <span class="text-gray-500 text-sm">{{ formatRelativeTime(critique.created_at) }}</span>
                        
                        <!-- 三点リーダーメニュー -->
                        <div v-if="authUser && authUser.id === critique.user.id" class="ml-auto relative">
                          <button 
                            @click.stop="toggleCritiqueMenu(critique.id)"
                            class="text-gray-400 hover:text-blue-500 hover:bg-blue-50 rounded-full p-1 transition"
                            title="メニュー"
                            aria-label="添削メニューを開く"
                            :aria-expanded="openCritiqueMenuId === critique.id"
                          >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                              <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"></path>
                            </svg>
                          </button>
                          
                          <!-- ドロップダウンメニュー -->
                          <Transition name="dropdown">
                            <div 
                              v-if="openCritiqueMenuId === critique.id"
                              class="absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-20"
                              role="menu"
                              @click.stop
                              @keydown.esc="toggleCritiqueMenu(critique.id)"
                            >
                              <button 
                                @click="deleteCritique(post.id, critique.id)"
                                @keydown.enter="deleteCritique(post.id, critique.id)"
                                class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center space-x-2"
                                role="menuitem"
                              >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                                <span>添削を削除</span>
                              </button>
                            </div>
                          </Transition>
                        </div>
                      </div>
                      <p class="mt-1 text-sm text-gray-800 whitespace-pre-wrap">{{ critique.content }}</p>
                    </div>
                  </div>
                </div>
                
                <div v-else class="text-center text-gray-400 text-sm mb-4 py-4">
                  まだ添削がありません
                </div>
                
                <!-- 添削フォーム -->
                <div v-if="authUser" class="flex space-x-2">
                  <div class="w-8 h-8 bg-gray-300 rounded-full flex-shrink-0"></div>
                  <div class="flex-1">
                    <textarea 
                      v-model="critiqueContent[post.id]"
                      placeholder="添削を入力..."
                      class="w-full border border-gray-300 rounded-lg p-2 text-sm outline-none focus:border-blue-500 resize-none"
                      rows="10"
                      maxlength="1000"
                    ></textarea>
                    <div class="flex justify-between items-center mt-2">
                      <span class="text-xs text-gray-500">
                        {{ critiqueContent[post.id]?.length || 0 }} / 1000
                      </span>
                      <button 
                        @click="createCritique(post.id)"
                        :disabled="!critiqueContent[post.id]?.trim() || submittingCritique[post.id]"
                        class="bg-blue-500 text-white px-3 py-1 rounded-full text-sm font-semibold hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        {{ submittingCritique[post.id] ? '送信中...' : '添削する' }}
                      </button>
                    </div>
                  </div>
                </div>
                
                <div v-else class="text-center text-gray-500 text-sm py-4">
                  添削するにはログインしてください
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- 右サイドバーは現在非表示 -->
    
    <!-- 画像拡大モーダル -->
    <Teleport to="body">
      <Transition name="modal">
        <div 
          v-if="showImageModal" 
          role="dialog"
          aria-modal="true"
          aria-label="画像拡大表示"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-90 p-4"
          @click="closeImageModal"
        >
          <button 
            class="absolute top-4 right-4 text-white hover:text-gray-300 transition"
            @click="closeImageModal"
            aria-label="モーダルを閉じる"
          >
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
          <img 
            v-if="modalImageUrl"
            :src="modalImageUrl" 
            alt="拡大画像" 
            class="max-w-full max-h-full object-contain"
            @click.stop
          />
        </div>
      </Transition>
    </Teleport>

    <!-- 削除確認モーダル -->
    <Teleport to="body">
      <Transition name="modal">
        <div 
          v-if="showDeleteConfirm" 
          role="dialog"
          aria-modal="true"
          aria-labelledby="delete-modal-title"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
          @click="cancelDelete"
        >
          <div 
            class="bg-white rounded-2xl shadow-xl max-w-sm w-full p-6 transform transition-all"
            @click.stop
          >
            <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 rounded-full">
              <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
              </svg>
            </div>
            <h3 id="delete-modal-title" class="text-lg font-semibold text-gray-900 text-center mb-2">
              {{ deleteTarget?.type === 'post' ? '投稿を削除しますか？' : '添削を削除しますか？' }}
            </h3>
            <p class="text-sm text-gray-600 text-center mb-6">
              この操作は取り消せません。本当に削除してもよろしいですか？
            </p>
            <div class="flex space-x-3">
              <button 
                @click="cancelDelete"
                class="flex-1 px-4 py-2.5 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition focus:outline-none focus:ring-2 focus:ring-gray-300"
              >
                キャンセル
              </button>
              <button 
                @click="confirmDelete"
                class="flex-1 px-4 py-2.5 bg-red-600 text-white rounded-lg font-medium hover:bg-red-700 transition focus:outline-none focus:ring-2 focus:ring-red-500"
              >
                削除
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../utils/axios';

interface Post {
  id: number;
  content: string;
  image_path?: string;
  image_url?: string;
  created_at: string;
  user: {
    id: number;
    name: string;
    username: string;
  };
}

interface Critique {
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
const selectedImage = ref<File | null>(null);
const imagePreview = ref<string | null>(null);
const posting = ref(false);

// 画像モーダル
const showImageModal = ref(false);
const modalImageUrl = ref<string | null>(null);

// 削除対象の型定義（Discriminated Union）
type DeleteTarget = 
  | { type: 'post'; postId: number }
  | { type: 'critique'; postId: number; critiqueId: number };

// 削除確認モーダル
const showDeleteConfirm = ref(false);
const deleteTarget = ref<DeleteTarget | null>(null);

// 添削機能
const critiquesMap = ref<Record<number, Critique[]>>({});
const expandedPosts = ref<Set<number>>(new Set());
const critiqueContent = ref<Record<number, string>>({});
const submittingCritique = ref<Record<number, boolean>>({});
const openCritiqueMenuId = ref<number | null>(null);
const openPostMenuId = ref<number | null>(null);
const authUser = ref<{ id: number; name: string; username: string } | null>(null);

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
  if (!newPostContent.value.trim() || !selectedImage.value) return;
  
  try {
    posting.value = true;
    
    const formData = new FormData();
    formData.append('content', newPostContent.value);
    if (selectedImage.value) {
      formData.append('image', selectedImage.value);
    }
    
    const response = await api.post('/posts', formData);
    
    // 投稿をリストの先頭に追加
    const newPost = response.data.post;
    recommendedPosts.value.unshift(newPost);
    followingPosts.value.unshift(newPost);
    
    newPostContent.value = '';
    selectedImage.value = null;
    imagePreview.value = null;
    const fileInput = document.getElementById('image-upload') as HTMLInputElement;
    if (fileInput) fileInput.value = '';
  } catch (e: any) {
    console.error('Failed to create post:', e);
    console.error('Error response:', e.response?.data);
    const errorMessage = e.response?.data?.message || '投稿に失敗しました';
    error.value = errorMessage;
  } finally {
    posting.value = false;
  }
};

// 画像選択ハンドラー
const handleImageSelect = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  
  if (file) {
    selectedImage.value = file;
    
    // プレビュー用のURLを作成
    const reader = new FileReader();
    reader.onload = (e) => {
      imagePreview.value = e.target?.result as string;
    };
    reader.readAsDataURL(file);
  }
};

// 画像削除ハンドラー
const removeImage = () => {
  if (imagePreview.value) {
    URL.revokeObjectURL(imagePreview.value);
  }
  selectedImage.value = null;
  imagePreview.value = null;
  const fileInput = document.getElementById('image-upload') as HTMLInputElement;
  if (fileInput) fileInput.value = '';
};

// Escapeキーでモーダルを閉じるハンドラー
const handleModalKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Escape') {
    closeImageModal();
  }
};
// 画像モーダル表示
const openImageModal = (imageUrl: string) => {
  modalImageUrl.value = imageUrl;
  showImageModal.value = true;
  document.addEventListener('keydown', handleModalKeydown);
};
// 画像モーダル閉じる
const closeImageModal = () => {
  showImageModal.value = false;
  modalImageUrl.value = null;
  document.removeEventListener('keydown', handleModalKeydown);
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

// 添削を取得
const fetchCritiques = async (postId: number) => {
  try {
    const response = await api.get(`/posts/${postId}/critiques`);
    critiquesMap.value[postId] = response.data;
  } catch (e: any) {
    console.error('Failed to fetch critiques:', e);
  }
};

// 投稿の展開/折りたたみをトグル
const togglePostExpansion = async (postId: number) => {
  const newSet = new Set(expandedPosts.value);
  if (newSet.has(postId)) {
    newSet.delete(postId);
  } else {
    newSet.add(postId);
    // まだ添削を取得していない場合は取得
    if (!critiquesMap.value[postId]) {
      await fetchCritiques(postId);
    }
  }
  expandedPosts.value = newSet;
};

// 添削を作成
const createCritique = async (postId: number) => {
  const content = critiqueContent.value[postId]?.trim();
  if (!content) return;
  
  try {
    submittingCritique.value[postId] = true;
    const response = await api.post(`/posts/${postId}/critiques`, { content });
    
    // 添削リストを更新
    if (!critiquesMap.value[postId]) {
      critiquesMap.value[postId] = [];
    }
    critiquesMap.value[postId].push(response.data);
    
    // フォームをクリア
    critiqueContent.value[postId] = '';
  } catch (e: any) {
    console.error('Failed to create critique:', e);
    error.value = e.response?.data?.message || '添削の投稿に失敗しました';
  } finally {
    submittingCritique.value[postId] = false;
  }
};

// 添削メニューのトグル
const toggleCritiqueMenu = (critiqueId: number) => {
  if (openCritiqueMenuId.value === critiqueId) {
    openCritiqueMenuId.value = null;
  } else {
    openCritiqueMenuId.value = critiqueId;
  }
};

// 添削を削除（モーダル表示）
const deleteCritique = (postId: number, critiqueId: number) => {
  openCritiqueMenuId.value = null; // メニューを閉じる
  deleteTarget.value = { type: 'critique', postId, critiqueId };
  showDeleteConfirm.value = true;
};

// 削除確認モーダルのキャンセル
const cancelDelete = () => {
  showDeleteConfirm.value = false;
  deleteTarget.value = null;
};

// 削除確認モーダルの確定
const confirmDelete = async () => {
  if (!deleteTarget.value) return;
  
  // ローカル変数に値を保存して型狭窄を保持
  const target = deleteTarget.value;
  
  try {
    if (target.type === 'post') {
      // 投稿削除
      const postId = target.postId;
      await api.delete(`/posts/${postId}`);
      
      // 投稿リストから削除
      recommendedPosts.value = recommendedPosts.value.filter(p => p.id !== postId);
      followingPosts.value = followingPosts.value.filter(p => p.id !== postId);
    } else if (target.type === 'critique') {
      // 添削削除
      const { postId, critiqueId } = target;
      await api.delete(`/posts/${postId}/critiques/${critiqueId}`);
      
      // 添削リストから削除
      if (critiquesMap.value[postId]) {
        critiquesMap.value[postId] = critiquesMap.value[postId].filter(
          c => c.id !== critiqueId
        );
      }
    }
    
    // モーダルを閉じる
    showDeleteConfirm.value = false;
    deleteTarget.value = null;
  } catch (e: any) {
    console.error('Failed to delete:', e);
    error.value = e.response?.data?.message || '削除に失敗しました';
    showDeleteConfirm.value = false;
    deleteTarget.value = null;
  }
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

// 投稿メニューのトグル
const togglePostMenu = (postId: number) => {
  if (openPostMenuId.value === postId) {
    openPostMenuId.value = null;
  } else {
    openPostMenuId.value = postId;
  }
};

// 投稿を削除（モーダル表示）
const deletePost = (postId: number) => {
  openPostMenuId.value = null; // メニューを閉じる
  deleteTarget.value = { type: 'post', postId };
  showDeleteConfirm.value = true;
};

// 外部クリックでメニューを閉じる
const closeMenuOnOutsideClick = (event: MouseEvent) => {
  const target = event.target as HTMLElement;
  if (!target.closest('.relative')) {
    openPostMenuId.value = null;
    openCritiqueMenuId.value = null;
  }
};

// 初回マウント時にデータを取得
onMounted(async () => {
  // 認証ユーザー情報を取得
  const userStr = localStorage.getItem('auth_user');
  if (userStr) {
    authUser.value = JSON.parse(userStr);
  }
  await fetchRecommendedPosts();
  
  // 外部クリックリスナーを登録
  document.addEventListener('click', closeMenuOnOutsideClick);
});

// クリーンアップ
onUnmounted(() => {
  document.removeEventListener('click', closeMenuOnOutsideClick);
});
</script>

<style scoped>
/* モーダルアニメーション */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

/* ドロップダウンメニューアニメーション */
.dropdown-enter-active {
  transition: all 0.15s ease-out;
}

.dropdown-leave-active {
  transition: all 0.1s ease-in;
}

.dropdown-enter-from {
  opacity: 0;
  transform: translateY(-8px) scale(0.95);
}

.dropdown-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
