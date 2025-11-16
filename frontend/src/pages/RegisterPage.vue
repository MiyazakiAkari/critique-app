<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100">
      <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold mb-6 text-center">新規登録</h1>
  
        <form @submit.prevent="register" class="flex flex-col gap-4">
          <input
            v-model="name"
            type="text"
            placeholder="名前"
            class="input"
          />
          <input
            v-model="username"
            type="text"
            placeholder="ユーザーID"
            class="input"
          />
          <input
            v-model="email"
            type="email"
            placeholder="メールアドレス"
            class="input"
          />
          <input
            v-model="password"
            type="password"
            placeholder="パスワード"
            class="input"
          />
          <input
            v-model="password_confirmation"
            type="password"
            placeholder="パスワード確認"
            class="input"
          />
  
          <button type="submit" class="btn-primary w-full">
            登録
          </button>
        </form>
  
        <p v-if="error" class="text-red-500 text-sm mt-4 text-center">
          {{ error }}
        </p>
      </div>
    </div>
  </template>
  
  <script setup lang="ts">
  import api from "../utils/axios";
  import { ref } from "vue";
  import { useRouter } from "vue-router";
  
  const router = useRouter();
  
  const name = ref("");
  const username = ref("");
  const email = ref("");
  const password = ref("");
  const password_confirmation = ref("");
  const error = ref<string | null>(null);
  
  const register = async () => {
    error.value = null;
  
    try {
      // CSRF Cookie（Sanctum）取得
      await api.get("/sanctum/csrf-cookie");
  
      // 登録
      await api.post("/register", {
        name: name.value,
        username: username.value,
        email: email.value,
        password: password.value,
        password_confirmation: password_confirmation.value,
      });
  
      alert("登録が完了しました！ログインしてください。");
      router.push("/login");
  
    } catch (e: any) {
      if (e.response?.data?.errors) {
        error.value = Object.values(e.response.data.errors).flat().join(" / ");
      } else {
        error.value = "登録に失敗しました。";
      }
    }
  };
  </script>
  
  <style scoped>
  </style>
  