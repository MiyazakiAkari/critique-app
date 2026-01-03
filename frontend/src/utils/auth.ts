import { ref, readonly } from 'vue'
import api from './axios'

// リアクティブな認証状態
const _isLoggedIn = ref(false)
const _user = ref<{ id: number; name: string; username: string } | null>(null)

// 初期化時にlocalStorageから状態を復元
const initAuth = () => {
  const token = localStorage.getItem('auth_token')
  const userStr = localStorage.getItem('auth_user')
  
  if (token && userStr) {
    try {
      _user.value = JSON.parse(userStr)
      _isLoggedIn.value = true
    } catch {
      clearAuth()
    }
  }
}

// 認証情報をクリア
export const clearAuth = () => {
  localStorage.removeItem('auth_token')
  localStorage.removeItem('auth_user')
  delete api.defaults.headers.common.Authorization
  _isLoggedIn.value = false
  _user.value = null
}

// ログイン成功時に呼び出す
export const setAuth = (token: string, user: { id: number; name: string; username: string }) => {
  localStorage.setItem('auth_token', token)
  localStorage.setItem('auth_user', JSON.stringify(user))
  api.defaults.headers.common.Authorization = `Bearer ${token}`
  _isLoggedIn.value = true
  _user.value = user
}

// 読み取り専用でエクスポート
export const isLoggedIn = readonly(_isLoggedIn)
export const authUser = readonly(_user)

// 初期化を実行
initAuth()

// 401レスポンス時に自動ログアウト
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      clearAuth()
      // 現在のパスがログインページでなければリダイレクト
      if (window.location.pathname !== '/login' && window.location.pathname !== '/register' && window.location.pathname !== '/') {
        window.location.href = '/login'
      }
    }
    return Promise.reject(error)
  }
)
