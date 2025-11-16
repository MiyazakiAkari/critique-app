<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100">
      <div class="bg-white p-8 rounded shadow-md w-full max-w-md">
        <h1 class="text-2xl font-bold mb-6 text-center">ログイン</h1>
  
        <form @submit.prevent="login" class="flex flex-col gap-4">
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
  
          <button type="submit" class="btn-primary w-full">
            ログイン
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
  
  const email = ref("");
  const password = ref("");
  const error = ref<string | null>(null);
  
  const login = async () => {
    error.value = null;
  
    try {
      // 必須：Sanctum Cookie を取得
      await api.get("/sanctum/csrf-cookie");
  
      // ログイン
      await api.post("/login", {
        email: email.value,
        password: password.value,
      });
  
      // 認証ユーザー取得
      const userRes = await api.get("/user");
      console.log("ログイン成功:", userRes.data);
  
      router.push("/home");
  
    } catch (e: any) {
      error.value = "メールアドレスまたはパスワードが違います。";
    }
  };
  </script>
  
  <style scoped>
  </style>
  