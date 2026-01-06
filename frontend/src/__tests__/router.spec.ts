import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { createRouter, createMemoryHistory } from 'vue-router'
import WelcomePage from '../pages/WelcomePage.vue'
import LoginPage from '../pages/LoginPage.vue'
import RegisterPage from '../pages/RegisterPage.vue'
import HomePage from '../pages/HomePage.vue'
import ProfilePage from '../pages/ProfilePage.vue'
import SearchPage from '../pages/SearchPage.vue'

// auth.ts をモック
vi.mock('../utils/auth', async () => {
  const vue = await import('vue')
  const _mockIsLoggedInRef = vue.ref(false)
  const _mockAuthUserRef = vue.ref(null)
  const mockClearAuth = vi.fn(() => {
    _mockIsLoggedInRef.value = false
    _mockAuthUserRef.value = null
    if (typeof localStorage !== 'undefined') {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
    }
  })

  return {
    isLoggedIn: vue.readonly(_mockIsLoggedInRef),
    authUser: vue.readonly(_mockAuthUserRef),
    clearAuth: mockClearAuth,
    // テスト用に内部refを公開
    __mockIsLoggedInRef: _mockIsLoggedInRef,
    __mockAuthUserRef: _mockAuthUserRef,
    __mockClearAuth: mockClearAuth,
  }
})

// モックされた auth をインポート
import { isLoggedIn, authUser, clearAuth } from '../utils/auth'
// モック内で定義された変数は vi.mock() の戻り値から自動的に利用可能
const { __mockIsLoggedInRef, __mockAuthUserRef, __mockClearAuth } = await vi.importMock<{
  __mockIsLoggedInRef: { value: boolean }
  __mockAuthUserRef: { value: { id: number; name: string; username: string } | null }
  __mockClearAuth: ReturnType<typeof vi.fn>
}>('../utils/auth')

describe('router/index.ts', () => {
  let router: ReturnType<typeof createRouter>
  let localStorageMock: { [key: string]: string }

  beforeEach(() => {
    // localStorage をモック
    localStorageMock = {}
    Object.defineProperty(globalThis, 'localStorage', {
      value: {
        getItem: vi.fn((key: string) => localStorageMock[key] || null),
        setItem: vi.fn((key: string, value: string) => {
          localStorageMock[key] = value
        }),
        removeItem: vi.fn((key: string) => {
          delete localStorageMock[key]
        }),
        clear: vi.fn(() => {
          localStorageMock = {}
        }),
        length: 0,
        key: vi.fn(() => null),
      },
      writable: true,
      configurable: true,
    })

    // 認証状態をリセット
    __mockIsLoggedInRef.value = false
    __mockAuthUserRef.value = null
    __mockClearAuth.mockClear()

    // ルーターを作成（実際のルーターと同じ設定）
    const routes = [
      { path: '/', component: WelcomePage },
      { path: '/login', component: LoginPage },
      { path: '/register', component: RegisterPage },
      { path: '/home', component: HomePage },
      { path: '/search', component: SearchPage },
      { path: '/profile/:username', component: ProfilePage },
      { path: '/:pathMatch(.*)*', redirect: '/' },
    ]

    router = createRouter({
      history: createMemoryHistory(),
      routes,
    })

    // ナビゲーションガードを設定（実際のルーターと同じロジック）
    router.beforeEach((to, _from, next) => {
      // リアクティブな認証状態を使用（トークンとユーザー情報の両方を検証）
      const authenticated = isLoggedIn.value && authUser.value !== null

      // ログイン済みでログイン・登録ページにアクセスした場合はHomeにリダイレクト
      if (authenticated && (to.path === '/login' || to.path === '/register')) {
        next('/home')
        return
      }

      // ログイン済みでWelcomeページにアクセスした場合はHomeにリダイレクト
      if (authenticated && to.path === '/') {
        next('/home')
        return
      }

      // 未認証でログイン・登録ページに遷移する場合のみ認証情報をクリア
      if (!authenticated && (to.path === '/login' || to.path === '/register')) {
        clearAuth()
      }

      next()
    })
  })

  afterEach(() => {
    vi.clearAllMocks()
  })

  describe('認証済みユーザーのリダイレクト', () => {
    it('ログイン済みユーザーが / にアクセスした場合、/home にリダイレクトされる', async () => {
      // 認証状態を設定
      __mockIsLoggedInRef.value = true
      __mockAuthUserRef.value = { id: 1, name: 'Test User', username: 'testuser' }

      // / にナビゲート
      await router.push('/')

      // /home にリダイレクトされていることを確認
      expect(router.currentRoute.value.path).toBe('/home')
    })

    it('ログイン済みユーザーが /login にアクセスした場合、/home にリダイレクトされる', async () => {
      // 認証状態を設定
      __mockIsLoggedInRef.value = true
      __mockAuthUserRef.value = { id: 1, name: 'Test User', username: 'testuser' }

      // localStorage に認証情報を設定
      localStorage.setItem('auth_token', 'test-token')
      localStorage.setItem('auth_user', JSON.stringify({ id: 1, name: 'Test User', username: 'testuser' }))

      // /login にナビゲート
      await router.push('/login')

      // /home にリダイレクトされていることを確認
      expect(router.currentRoute.value.path).toBe('/home')

      // clearAuth が呼ばれていないことを確認（認証済みなのでクリアしない）
      expect(__mockClearAuth).not.toHaveBeenCalled()
    })

    it('ログイン済みユーザーが /register にアクセスした場合、/home にリダイレクトされる', async () => {
      // 認証状態を設定
      __mockIsLoggedInRef.value = true
      __mockAuthUserRef.value = { id: 1, name: 'Test User', username: 'testuser' }

      // localStorage に認証情報を設定
      localStorage.setItem('auth_token', 'test-token')
      localStorage.setItem('auth_user', JSON.stringify({ id: 1, name: 'Test User', username: 'testuser' }))

      // /register にナビゲート
      await router.push('/register')

      // /home にリダイレクトされていることを確認
      expect(router.currentRoute.value.path).toBe('/home')

      // clearAuth が呼ばれていないことを確認（認証済みなのでクリアしない）
      expect(__mockClearAuth).not.toHaveBeenCalled()
    })
  })

  describe('未認証ユーザーのナビゲーション', () => {
    it('未ログインユーザーが / にアクセスした場合、そのまま遷移できる', async () => {
      // 認証状態を未ログインに設定
      __mockIsLoggedInRef.value = false
      __mockAuthUserRef.value = null

      // / にナビゲート
      await router.push('/')

      // / にそのまま遷移していることを確認
      expect(router.currentRoute.value.path).toBe('/')
    })

    it('未ログインユーザーが /login にアクセスした場合、そのまま遷移できる', async () => {
      // 認証状態を未ログインに設定
      __mockIsLoggedInRef.value = false
      __mockAuthUserRef.value = null

      // /login にナビゲート
      await router.push('/login')

      // /login にそのまま遷移していることを確認
      expect(router.currentRoute.value.path).toBe('/login')

      // clearAuth が呼ばれていることを確認
      expect(__mockClearAuth).toHaveBeenCalled()
    })

    it('未ログインユーザーが /register にアクセスした場合、そのまま遷移できる', async () => {
      // 認証状態を未ログインに設定
      __mockIsLoggedInRef.value = false
      __mockAuthUserRef.value = null

      // /register にナビゲート
      await router.push('/register')

      // /register にそのまま遷移していることを確認
      expect(router.currentRoute.value.path).toBe('/register')

      // clearAuth が呼ばれていることを確認
      expect(__mockClearAuth).toHaveBeenCalled()
    })

    it('未ログインユーザーが /home にアクセスした場合、そのまま遷移できる', async () => {
      // 認証状態を未ログインに設定
      __mockIsLoggedInRef.value = false
      __mockAuthUserRef.value = null

      // /home にナビゲート
      await router.push('/home')

      // /home にそのまま遷移していることを確認
      expect(router.currentRoute.value.path).toBe('/home')
    })
  })

  describe('localStorage のクリア処理', () => {
    it('/login への遷移時に clearAuth が呼ばれる（未認証の場合）', async () => {
      // 未認証状態
      __mockIsLoggedInRef.value = false
      __mockAuthUserRef.value = null

      // /login にナビゲート
      await router.push('/login')

      // clearAuth が呼ばれていることを確認
      expect(__mockClearAuth).toHaveBeenCalled()
    })

    it('/register への遷移時に clearAuth が呼ばれる（未認証の場合）', async () => {
      // 未認証状態
      __mockIsLoggedInRef.value = false
      __mockAuthUserRef.value = null

      // /register にナビゲート
      await router.push('/register')

      // clearAuth が呼ばれていることを確認
      expect(__mockClearAuth).toHaveBeenCalled()
    })

    it('他のページへの遷移時は clearAuth が呼ばれない', async () => {
      // /home にナビゲート
      await router.push('/home')

      // clearAuth が呼ばれていないことを確認
      expect(__mockClearAuth).not.toHaveBeenCalled()
    })
  })

  describe('無限リダイレクトループの防止', () => {
    it('認証済みユーザーが /login にアクセスしても無限ループにならない', async () => {
      // 認証状態を設定
      __mockIsLoggedInRef.value = true
      __mockAuthUserRef.value = { id: 1, name: 'Test User', username: 'testuser' }

      // /login にナビゲート
      await router.push('/login')

      // /home にリダイレクトされ、clearAuth は呼ばれない
      expect(router.currentRoute.value.path).toBe('/home')
      expect(__mockClearAuth).not.toHaveBeenCalled()
    })

    it('認証済みユーザーが /register にアクセスしても無限ループにならない', async () => {
      // 認証状態を設定
      __mockIsLoggedInRef.value = true
      __mockAuthUserRef.value = { id: 1, name: 'Test User', username: 'testuser' }

      // /register にナビゲート
      await router.push('/register')

      // /home にリダイレクトされ、clearAuth は呼ばれない
      expect(router.currentRoute.value.path).toBe('/home')
      expect(__mockClearAuth).not.toHaveBeenCalled()
    })

    it('未認証ユーザーが /login にアクセスした場合、clearAuth が呼ばれてもループしない', async () => {
      // 未認証状態
      __mockIsLoggedInRef.value = false
      __mockAuthUserRef.value = null

      // /login にナビゲート
      await router.push('/login')

      // /login にそのまま遷移し、clearAuth が1回だけ呼ばれる
      expect(router.currentRoute.value.path).toBe('/login')
      expect(__mockClearAuth).toHaveBeenCalledTimes(1)
    })
  })

  describe('認証状態の検証', () => {
    it('isLoggedIn が true でも authUser が null の場合は未認証として扱われる', async () => {
      // isLoggedIn のみ true に設定
      __mockIsLoggedInRef.value = true
      __mockAuthUserRef.value = null

      // / にナビゲート
      await router.push('/')

      // リダイレクトされず、そのまま遷移していることを確認
      expect(router.currentRoute.value.path).toBe('/')
    })

    it('authUser が存在しても isLoggedIn が false の場合は未認証として扱われる', async () => {
      // authUser のみ設定
      __mockIsLoggedInRef.value = false
      __mockAuthUserRef.value = { id: 1, name: 'Test User', username: 'testuser' }

      // / にナビゲート
      await router.push('/')

      // リダイレクトされず、そのまま遷移していることを確認
      expect(router.currentRoute.value.path).toBe('/')
    })

    it('isLoggedIn と authUser の両方が設定されている場合のみ認証済みとして扱われる', async () => {
      // 両方を設定
      __mockIsLoggedInRef.value = true
      __mockAuthUserRef.value = { id: 1, name: 'Test User', username: 'testuser' }

      // / にナビゲート
      await router.push('/')

      // /home にリダイレクトされていることを確認
      expect(router.currentRoute.value.path).toBe('/home')
    })
  })

  describe('その他のルート', () => {
    it('/search にアクセスできる', async () => {
      await router.push('/search')
      expect(router.currentRoute.value.path).toBe('/search')
    })

    it('/profile/:username にアクセスできる', async () => {
      await router.push('/profile/testuser')
      expect(router.currentRoute.value.path).toBe('/profile/testuser')
      expect(router.currentRoute.value.params.username).toBe('testuser')
    })

    it('存在しないパスにアクセスした場合、/ にリダイレクトされる', async () => {
      await router.push('/nonexistent')
      expect(router.currentRoute.value.path).toBe('/')
    })
  })
})
