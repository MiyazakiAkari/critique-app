import { createRouter, createWebHistory } from 'vue-router'
import LoginPage from '../pages/LoginPage.vue'
import RegisterPage from '../pages/RegisterPage.vue'

const routes = [
  { path: '/login', component: LoginPage },
  { path: '/register', component: RegisterPage },
  { path: "/:pathMatch(.*)*", redirect: "/login" }
]

export const router = createRouter({
  history: createWebHistory(),
  routes,
})
