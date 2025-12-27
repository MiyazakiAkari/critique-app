import { createRouter, createWebHistory } from 'vue-router'
import LoginPage from '../pages/LoginPage.vue'
import RegisterPage from '../pages/RegisterPage.vue'
import HomePage from '../pages/HomePage.vue'
import ProfilePage from '../pages/ProfilePage.vue'
import SearchPage from '../pages/SearchPage.vue'

const routes = [
  { path: '/login', component: LoginPage },
  { path: '/register', component: RegisterPage },
  { path: '/home', component: HomePage },
  { path: '/search', component: SearchPage },
  { path: '/profile/:username', component: ProfilePage },
  { path: "/:pathMatch(.*)*", redirect: "/login" }
]

export const router = createRouter({
  history: createWebHistory(),
  routes,
})
