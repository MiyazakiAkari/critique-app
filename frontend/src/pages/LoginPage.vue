<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 p-4">
    <div class="w-full max-w-md bg-white shadow-lg rounded-xl p-8">
      <h1 class="text-2xl font-bold text-center mb-6">ログイン</h1>

      <form @submit.prevent="login" class="space-y-4">

        <input v-model="email" type="email" placeholder="メールアドレス"
          class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-300 focus:outline-none" />

        <input v-model="password" type="password" placeholder="パスワード"
          class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-300 focus:outline-none" />

        <button type="submit"
          class="w-full bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
          ログイン
        </button>
      </form>

      <p class="text-red-600 text-center mt-4">{{ error }}</p>
    </div>
  </div>
</template>


<script setup lang="ts">
import { ref } from "vue";
import api, { setAuthToken } from "../utils/axios";
import { useRouter } from "vue-router";

const router = useRouter();

const email = ref("");
const password = ref("");
const error = ref("");

const login = async () => {
  error.value = "";

  try {
    const loginResponse = await api.post('/login', {
      email: email.value,
      password: password.value,
    });

    const { token, user } = loginResponse.data;

    if (!token) {
      throw new Error("Token was not returned from the API");
    }

    setAuthToken(token);

    if (user) {
      localStorage.setItem("auth_user", JSON.stringify(user));
    }
    
    router.push('/home');

  } catch (e: any) {
    console.error("Login error:", e);
    console.error("Error response:", e.response?.data);
    error.value = e.response?.data?.message || "ログインに失敗しました";
  }
};
</script>


<style></style>
