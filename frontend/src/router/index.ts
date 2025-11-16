import { createRouter, createWebHistory } from "vue-router";
import LoginPage from "../pages/LoginPage.vue";
import RegisterPage from "../pages/RegisterPage.vue";

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: "/login", component: LoginPage },
    { path: "/register", component: RegisterPage },
    { path: "/:pathMatch(.*)*", redirect: "/login" } // 不明ページはログインへ
  ],
});

export default router;
