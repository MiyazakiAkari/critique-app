import { createRouter, createWebHistory } from 'vue-router'
import WelcomePage from '../pages/WelcomePage.vue'
import LoginPage from '../pages/LoginPage.vue'
import RegisterPage from '../pages/RegisterPage.vue'
import HomePage from '../pages/HomePage.vue'
import ProfilePage from '../pages/ProfilePage.vue'
import SearchPage from '../pages/SearchPage.vue'

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
  const token = localStorage.getItem('auth_token')
  const isLoggedIn = !!token
  
  // ログイン済みでWelcomeページにアクセスした場合はHomeにリダイレクト
  if (isLoggedIn && to.path === '/') {
    next('/home')
    return
  }
  
  // ログイン済みでログイン・登録ページにアクセスした場合もHomeにリダイレクト
  if (isLoggedIn && (to.path === '/login' || to.path === '/register')) {
    next('/home')
    return
  }
  
  next()
})
