<template>
  <aside class="w-64 bg-white border-r border-gray-200 fixed left-0 h-full xl:left-auto xl:relative">
    <div class="p-4">
      <h1 
        class="text-2xl font-bold text-blue-600 mb-8 cursor-pointer" 
        @click="router.push('/home')"
      >
        Critique
      </h1>
      
      <nav class="space-y-2">
        <router-link 
          to="/home" 
          class="flex items-center space-x-4 px-4 py-3 rounded-full hover:bg-gray-100 transition"
          :class="isActive('home') ? 'text-gray-800 font-semibold' : 'text-gray-600'"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
          </svg>
          <span>ホーム</span>
        </router-link>
        
        <router-link 
          to="/search" 
          class="flex items-center space-x-4 px-4 py-3 rounded-full hover:bg-gray-100 transition"
          :class="isActive('search') ? 'text-gray-800 font-semibold' : 'text-gray-600'"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
          <span>検索</span>
        </router-link>
        
        <a 
          href="#" 
          class="flex items-center space-x-4 px-4 py-3 rounded-full hover:bg-gray-100 text-gray-600 transition"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
          </svg>
          <span>通知</span>
        </a>
        
        <a 
          @click="goToProfile" 
          class="flex items-center space-x-4 px-4 py-3 rounded-full hover:bg-gray-100 cursor-pointer transition"
          :class="isActive('profile') ? 'text-gray-800 font-semibold' : 'text-gray-600'"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
          </svg>
          <span>プロフィール</span>
        </a>
        
        <!-- ログアウト -->
        <a 
          v-if="isLoggedIn"
          @click="logout" 
          class="flex items-center space-x-4 px-4 py-3 rounded-full hover:bg-red-50 text-gray-600 hover:text-red-600 cursor-pointer transition"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
          </svg>
          <span>ログアウト</span>
        </a>
      </nav>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '../utils/axios'

const router = useRouter()
const route = useRoute()

const isLoggedIn = computed(() => !!localStorage.getItem('auth_token'))

const goToProfile = () => {
  const authUser = JSON.parse(localStorage.getItem('auth_user') || '{}')
  if (authUser.username) {
    router.push(`/profile/${authUser.username}`)
  } else {
    router.push('/login')
  }
}

const logout = async () => {
  try {
    await api.post('/logout')
  } catch (e) {
    // エラーでも続行
  } finally {
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
    router.push('/')
  }
}

const isActive = (page: string) => {
  if (page === 'home') {
    return route.path === '/home'
  } else if (page === 'search') {
    return route.path === '/search'
  } else if (page === 'profile') {
    return route.path.startsWith('/profile/')
  }
  return false
}
</script>
