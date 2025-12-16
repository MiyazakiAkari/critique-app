import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount, VueWrapper } from '@vue/test-utils'
import { createRouter, createMemoryHistory } from 'vue-router'
import HomePage from '../pages/HomePage.vue'
import api from '../utils/axios'

type HomePageComponent = InstanceType<typeof HomePage>

// API モックを作成
vi.mock('../utils/axios', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    delete: vi.fn(),
  },
}))

describe('HomePage.vue', () => {
  let router: any

  beforeEach(() => {
    // ルーターをモック
    router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/home', component: HomePage },
        { path: '/profile/:username', component: { template: '<div>Profile</div>' } },
      ],
    })

    // localStorage をモック
    const mockUser = {
      id: 1,
      name: 'Test User',
      username: 'testuser',
      email: 'test@example.com',
    }
    localStorage.setItem('auth_user', JSON.stringify(mockUser))

    // API モックをリセット
    vi.clearAllMocks()
  })

  describe('投稿メニュー機能', () => {
    it('三点リーダーメニューを表示/非表示にできる', async () => {
      const mockPosts = {
        posts: [
          {
            id: 1,
            content: 'Test post',
            image_path: null,
            image_url: null,
            created_at: new Date().toISOString(),
            user: {
              id: 1,
              name: 'Test User',
              username: 'testuser',
            },
            reposts_count: 0,
            is_reposted: false,
          },
        ],
      }

      vi.mocked(api.get).mockResolvedValue({ data: mockPosts })

      const wrapper: VueWrapper<HomePageComponent> = mount(HomePage, {
        global: {
          plugins: [router],
          stubs: ['Teleport'],
        },
      })

      await wrapper.vm.$nextTick()
      await new Promise(resolve => setTimeout(resolve, 100))

      // 最初はメニューが表示されていない
      expect(wrapper.vm.openPostMenuId).toBeNull()

      // togglePostMenuを呼び出す
      await wrapper.vm.togglePostMenu(1)
      expect(wrapper.vm.openPostMenuId).toBe(1)

      // もう一度呼び出すと非表示になる
      await wrapper.vm.togglePostMenu(1)
      expect(wrapper.vm.openPostMenuId).toBeNull()
    })

    it('投稿削除をトリガーできる', async () => {
      const mockPosts = {
        posts: [
          {
            id: 1,
            content: 'Test post',
            image_path: null,
            image_url: null,
            created_at: new Date().toISOString(),
            user: {
              id: 1,
              name: 'Test User',
              username: 'testuser',
            },
            reposts_count: 0,
            is_reposted: false,
          },
        ],
      }

      vi.mocked(api.get).mockResolvedValue({ data: mockPosts })

      const wrapper: VueWrapper<HomePageComponent> = mount(HomePage, {
        global: {
          plugins: [router],
          stubs: ['Teleport'],
        },
      })

      await wrapper.vm.$nextTick()

      // deletePostを呼び出す
      wrapper.vm.deletePost(1)

      // deleteTargetが正しく設定される
      expect(wrapper.vm.deleteTarget).toEqual({ type: 'post', postId: 1 })

      // 削除確認モーダルが表示される
      expect(wrapper.vm.showDeleteConfirm).toBe(true)

      // メニューが閉じられる
      expect(wrapper.vm.openPostMenuId).toBeNull()
    })

    it('メニュー外のクリックでメニューが閉じる', async () => {
      const mockPosts = {
        posts: [
          {
            id: 1,
            content: 'Test post',
            image_path: null,
            image_url: null,
            created_at: new Date().toISOString(),
            user: {
              id: 1,
              name: 'Test User',
              username: 'testuser',
            },
            reposts_count: 0,
            is_reposted: false,
          },
        ],
      }

      vi.mocked(api.get).mockResolvedValue({ data: mockPosts })

      const wrapper: VueWrapper<HomePageComponent> = mount(HomePage, {
        global: {
          plugins: [router],
          stubs: ['Teleport'],
        },
      })

      // メニューを開く
      wrapper.vm.openPostMenuId = 1

      // 外部クリックをシミュレート
      const event = new MouseEvent('click')
      Object.defineProperty(event, 'target', {
        value: { closest: () => null },
        enumerable: true,
      })

      wrapper.vm.closeMenuOnOutsideClick(event)

      // メニューが閉じられる
      expect(wrapper.vm.openPostMenuId).toBeNull()
    })
  })

  describe('削除確認モーダル機能', () => {
    it('投稿を削除できる', async () => {
      const mockPosts = {
        posts: [
          {
            id: 1,
            content: 'Test post to delete',
            image_path: null,
            image_url: null,
            created_at: new Date().toISOString(),
            user: {
              id: 1,
              name: 'Test User',
              username: 'testuser',
            },
            reposts_count: 0,
            is_reposted: false,
          },
        ],
      }

      vi.mocked(api.get).mockResolvedValue({ data: mockPosts })
      vi.mocked(api.delete).mockResolvedValue({ data: { message: 'Post deleted' } })

      const wrapper: VueWrapper<HomePageComponent> = mount(HomePage, {
        global: {
          plugins: [router],
          stubs: ['Teleport'],
        },
      })

      await wrapper.vm.$nextTick()

      // 初期状態
      wrapper.vm.recommendedPosts = mockPosts.posts

      // deletePostを呼び出して削除確認モーダルを表示
      wrapper.vm.deletePost(1)

      expect(wrapper.vm.showDeleteConfirm).toBe(true)
      expect(wrapper.vm.deleteTarget?.type).toBe('post') // 投稿削除の場合

      // confirmDeleteを呼び出す
      await wrapper.vm.confirmDelete()

      // API呼び出しが正しく実行される
      expect(api.delete).toHaveBeenCalledWith('/posts/1')

      // 投稿リストから削除される
      expect(wrapper.vm.recommendedPosts).toHaveLength(0)

      // モーダルが閉じられる
      expect(wrapper.vm.showDeleteConfirm).toBe(false)
    })

    it('添削を削除できる', async () => {
      const mockPosts = {
        posts: [
          {
            id: 1,
            content: 'Test post',
            image_path: null,
            image_url: null,
            created_at: new Date().toISOString(),
            user: {
              id: 2,
              name: 'Other User',
              username: 'otheruser',
            },
            reposts_count: 0,
            is_reposted: false,
          },
        ],
      }

      vi.mocked(api.get).mockResolvedValue({ data: mockPosts })
      vi.mocked(api.delete).mockResolvedValue({ data: { message: 'Critique deleted' } })

      const wrapper: VueWrapper<HomePageComponent> = mount(HomePage, {
        global: {
          plugins: [router],
          stubs: ['Teleport'],
        },
      })

      await wrapper.vm.$nextTick()

      // 添削データを設定
      wrapper.vm.critiquesMap = {
        1: [
          {
            id: 10,
            content: 'Test critique',
            created_at: new Date().toISOString(),
            user: {
              id: 1,
              name: 'Test User',
              username: 'testuser',
            },
          },
        ],
      }

      // 添削削除を設定
      wrapper.vm.deleteTarget = { type: 'critique', postId: 1, critiqueId: 10 }
      wrapper.vm.showDeleteConfirm = true

      // confirmDeleteを呼び出す
      await wrapper.vm.confirmDelete()

      // API呼び出しが正しく実行される（添削削除）
      expect(api.delete).toHaveBeenCalledWith('/posts/1/critiques/10')

      // 添削リストから削除される
      expect(wrapper.vm.critiquesMap[1]).toHaveLength(0)

      // モーダルが閉じられる
      expect(wrapper.vm.showDeleteConfirm).toBe(false)
    })

    it('投稿削除と添削削除を区別できる', async () => {
      const wrapper: VueWrapper<HomePageComponent> = mount(HomePage, {
        global: {
          plugins: [router],
          stubs: ['Teleport'],
        },
      })

      // 投稿削除の場合
      wrapper.vm.deletePost(1)
      expect(wrapper.vm.deleteTarget?.type).toBe('post')

      // モーダルをリセット
      wrapper.vm.showDeleteConfirm = false

      // 添削削除の場合
      wrapper.vm.deleteCritique(1, 10)
      expect(wrapper.vm.deleteTarget?.type).toBe('critique')
    })

    it('削除がキャンセルできる', async () => {
      const wrapper: VueWrapper<HomePageComponent> = mount(HomePage, {
        global: {
          plugins: [router],
          stubs: ['Teleport'],
        },
      })

      // 削除を開始
      wrapper.vm.deletePost(1)
      expect(wrapper.vm.showDeleteConfirm).toBe(true)

      // cancelDeleteを呼び出す
      wrapper.vm.cancelDelete()

      // モーダルが閉じられる
      expect(wrapper.vm.showDeleteConfirm).toBe(false)

      // deleteTargetがリセットされる
      expect(wrapper.vm.deleteTarget).toBeNull()
    })

    it('削除時にエラーが発生した場合、エラーメッセージが表示される', async () => {
      vi.mocked(api.delete).mockRejectedValue({
        response: {
          data: {
            message: 'Unauthorized to delete this post',
          },
        },
      })

      const wrapper: VueWrapper<HomePageComponent> = mount(HomePage, {
        global: {
          plugins: [router],
          stubs: ['Teleport'],
        },
      })

      // 投稿削除を設定
      wrapper.vm.deleteTarget = { type: 'post', postId: 1 }
      wrapper.vm.showDeleteConfirm = true

      // confirmDeleteを呼び出す
      await wrapper.vm.confirmDelete()

      // エラーメッセージが設定される
      expect(wrapper.vm.error).toBe('Unauthorized to delete this post')

      // モーダルが閉じられる
      expect(wrapper.vm.showDeleteConfirm).toBe(false)
    })
  })

  describe('添削メニュー機能', () => {
    it('添削メニューを表示/非表示にできる', async () => {
      const wrapper: VueWrapper<HomePageComponent> = mount(HomePage, {
        global: {
          plugins: [router],
          stubs: ['Teleport'],
        },
      })

      expect(wrapper.vm.openCritiqueMenuId).toBeNull()

      // toggleCritiqueMenuを呼び出す
      wrapper.vm.toggleCritiqueMenu(10)
      expect(wrapper.vm.openCritiqueMenuId).toBe(10)

      // もう一度呼び出すと非表示になる
      wrapper.vm.toggleCritiqueMenu(10)
      expect(wrapper.vm.openCritiqueMenuId).toBeNull()
    })

    it('添削削除をトリガーできる', async () => {
      const wrapper: VueWrapper<HomePageComponent> = mount(HomePage, {
        global: {
          plugins: [router],
          stubs: ['Teleport'],
        },
      })

      // deleteCritiqueを呼び出す
      wrapper.vm.deleteCritique(1, 10)

      // deleteTargetが正しく設定される
      expect(wrapper.vm.deleteTarget).toEqual({ type: 'critique', postId: 1, critiqueId: 10 })

      // 削除確認モーダルが表示される
      expect(wrapper.vm.showDeleteConfirm).toBe(true)

      // メニューが閉じられる
      expect(wrapper.vm.openCritiqueMenuId).toBeNull()
    })
  })
})
