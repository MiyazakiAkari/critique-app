<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 p-4">
    <div class="w-full max-w-md bg-white shadow-lg rounded-xl p-8">
      <h1 class="text-2xl font-bold text-center mb-6">新規登録</h1>

      <form @submit.prevent="register" class="space-y-4">

        <input
          v-model="name"
          placeholder="名前"
          class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-300 focus:outline-none"
        />

        <input
          v-model="username"
          placeholder="ユーザーID"
          class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-300 focus:outline-none"
        />

        <input
          v-model="email"
          placeholder="メールアドレス"
          class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-300 focus:outline-none"
        />

        <input
          v-model="password"
          type="password"
          placeholder="パスワード"
          class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-300 focus:outline-none"
        />

        <input
          v-model="password_confirmation"
          type="password"
          placeholder="パスワード確認"
          class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-300 focus:outline-none"
        />

        <button
          type="submit"
          class="w-full bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition"
        >
          登録
        </button>
      </form>

      <p class="text-red-600 text-center mt-4">{{ error }}</p>
    </div>
  </div>
</template>
  
  <script setup lang="ts">
  import axios from '../utils/axios';
  import { ref } from 'vue';
  
  const name = ref('');
  const username = ref('');
  const email = ref('');
  const password = ref('');
  const password_confirmation = ref('');
  const error = ref('');
  
  const register = async () => {
    try {
      const res = await axios.post('/register', {
        name: name.value,
        username: username.value,
        email: email.value,
        password: password.value,
        password_confirmation: password_confirmation.value,
      });
  
      console.log(res.data);
      alert("登録が完了しました！");
    } catch (e: any) {
      error.value = JSON.stringify(e.response.data);
    }
  };
  </script>
  
  <style>
  .form-wrapper {
    max-width: 400px;
    margin: 0 auto;
  }
  </style>
  