import { createRouter, createWebHistory } from 'vue-router'
import WelcomePage from '../pages/WelcomePage.vue'
import LoginPage from '../pages/LoginPage.vue'
import RegisterPage from '../pages/RegisterPage.vue'
import HomePage from '../pages/HomePage.vue'
import ProfilePage from '../pages/ProfilePage.vue'
import SearchPage from '../pages/SearchPage.vue'
import { isLoggedIn, authUser, clearAuth } from '../utils/auth'

const routes = [
  { path: '/', component: WelcomePage },
  { path: '/login', component: LoginPage },
  { path: '/register', component: RegisterPage },
  { path: '/home', component: HomePage },
  { path: '/search', component: SearchPage },
  { path: '/profile/:username', component: ProfilePage },
  { path: "/:pathMatch(.*)*", redirect: "/" }
]

export const router = createRouter({
  history: createWebHistory(),
  routes,
})

// ナビゲーションガード：ログイン済みユーザーがルートにアクセスした場合は/homeにリダイレクト
router.beforeEach((to, _from, next) => {
  // リアクティブな認証状態を使用（トークンとユーザー情報の両方を検証）
  const authenticated = isLoggedIn.value && authUser.value !== null;

  // ログイン済みでログイン・登録ページにアクセスした場合はHomeにリダイレクト
  if (authenticated && (to.path === '/login' || to.path === '/register')) {
    next('/home');
    return;
  }

  // ログイン済みでWelcomeページにアクセスした場合はHomeにリダイレクト
  if (authenticated && to.path === '/') {
    next('/home');
    return;
  }

  // 未認証でログイン・登録ページに遷移する場合のみ認証情報をクリア
  if (!authenticated && (to.path === '/login' || to.path === '/register')) {
    clearAuth();
  }

  next();
});
