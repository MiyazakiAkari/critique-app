<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 p-4">
    <div class="w-full max-w-md bg-white shadow-lg rounded-xl p-8">
      <h1 class="text-2xl font-bold text-center mb-6">ログイン</h1>

      <form @submit.prevent="login" class="space-y-4">

        <input
          v-model="email"
          type="email"
          placeholder="メールアドレス"
          class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-300 focus:outline-none"
        />

        <input
          v-model="password"
          type="password"
          placeholder="パスワード"
          class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-300 focus:outline-none"
        />

        <button
          type="submit"
          class="w-full bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition"
        >
          ログイン
        </button>
      </form>

      <p class="text-red-600 text-center mt-4">{{ error }}</p>
    </div>
  </div>
</template>

  
  <script setup lang="ts">
  import axios from '../utils/axios';
  import { ref } from 'vue';
  
  const email = ref('');
  const password = ref('');
  const error = ref('');
  
  const login = async () => {
    try {
      const res = await axios.post('/login', {
        email: email.value,
        password: password.value,
      });
  
      // Sanctum の API は token を返す
      localStorage.setItem('token', res.data.token);
  
      alert('ログイン成功');
    } catch (e: any) {
      error.value = "ログインに失敗しました";
    }
  };
  </script>
  
  <style>
  .form-wrapper {
    max-width: 400px;
    margin: 0 auto;
  }
  </style>
  