<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-100 p-4">
    <div class="w-full max-w-md bg-white shadow-lg rounded-xl p-8">
      <h1 class="text-2xl font-bold text-center mb-6">新規登録</h1>

      <form @submit.prevent="register" class="space-y-4">

        <input v-model="name" placeholder="名前" required
          class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-300 focus:outline-none" />

        <input v-model="username" type="text" placeholder="ユーザーID" required pattern="[a-zA-Z0-9_]+"
          title="英数字とアンダースコアのみ使用できます"
          class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-300 focus:outline-none" />

        <input v-model="email" type="email" placeholder="メールアドレス" required
          class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-300 focus:outline-none" />

        <input v-model="password" type="password" placeholder="パスワード" minlength="8" required
          class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-300 focus:outline-none" />

        <input v-model="password_confirmation" type="password" placeholder="パスワード確認" minlength="8" required
          class="w-full px-4 py-2 border rounded-lg focus:ring focus:ring-blue-300 focus:outline-none" />

        <button type="submit"
          class="w-full bg-blue-600 text-white py-2 rounded-lg font-semibold hover:bg-blue-700 transition">
          登録
        </button>
      </form>

      <p class="text-red-600 text-center mt-4">{{ error }}</p>
      
      <p class="text-center mt-4 text-gray-600">
        すでにアカウントをお持ちの方は
        <router-link to="/login" class="text-blue-600 hover:underline font-medium">ログイン</router-link>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import api from "../utils/axios";
import { setAuth } from "../utils/auth";
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
    const res = await api.post("/register", {
      name: name.value,
      username: username.value,
      email: email.value,
      password: password.value,
      password_confirmation: password_confirmation.value,
    });

    // トークンとユーザー情報を保存
    if (res.data.token && res.data.user) {
      setAuth(res.data.token, res.data.user);
    }

    // /home に遷移
    router.push("/home");

  } catch (e: any) {
    if (e.response?.data?.errors) {
      error.value = Object.values(e.response.data.errors).flat().join(", ");
    } else if (e.response?.data?.message) {
      error.value = e.response.data.message;
    } else {
      error.value = "登録に失敗しました";
    }
  }
};
</script>


<style></style>
