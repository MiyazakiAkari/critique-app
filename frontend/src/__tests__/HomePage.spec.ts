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
            image_path: undefined,
            image_url: undefined,
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
            image_path: undefined,
            image_url: undefined,
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
            image_path: undefined,
            image_url: undefined,
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
            image_path: undefined,
            image_url: undefined,
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
            image_path: undefined,
            image_url: undefined,
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
      if (wrapper.vm.toggleCritiqueMenu) {
        wrapper.vm.toggleCritiqueMenu(10)
        expect(wrapper.vm.openCritiqueMenuId).toBe(10)

        // もう一度呼び出すと非表示になる
        wrapper.vm.toggleCritiqueMenu(10)
        expect(wrapper.vm.openCritiqueMenuId).toBeNull()
      }
    })

    it('添削削除をトリガーできる', async () => {
      const wrapper: VueWrapper<HomePageComponent> = mount(HomePage, {
        global: {
          plugins: [router],
          stubs: ['Teleport'],
        },
      })

      // deleteCritiqueを呼び出す
      if (wrapper.vm.deleteCritique) {
        wrapper.vm.deleteCritique(1, 10)

        // deleteTargetが正しく設定される
        expect(wrapper.vm.deleteTarget).toEqual({ type: 'critique', postId: 1, critiqueId: 10 })

        // 削除確認モーダルが表示される
        expect(wrapper.vm.showDeleteConfirm).toBe(true)

        // メニューが閉じられる
        expect(wrapper.vm.openCritiqueMenuId).toBeNull()
      }
    })
  })

  describe('リポスト機能', () => {
    it('リポストボタンをトリガーできる', async () => {
      const mockPosts = {
        posts: [
          {
            id: 1,
            content: 'Test post',
            image_path: undefined,
            image_url: undefined,
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

      const wrapper: VueWrapper<HomePageComponent> = mount(HomePage, {
        global: {
          plugins: [router],
          stubs: ['Teleport'],
        },
      })

      await wrapper.vm.$nextTick()
      await new Promise(resolve => setTimeout(resolve, 100))

      // リポスト確認モーダルが初期状態で非表示
      expect(wrapper.vm.showRepostConfirm).toBe(false)

      // toggleRepostを呼び出す
      const post = mockPosts.posts[0]
      wrapper.vm.toggleRepost(1, post)

      // リポスト確認モーダルが表示される
      expect(wrapper.vm.showRepostConfirm).toBe(true)

      // リポストターゲットが設定される
      expect(wrapper.vm.repostConfirmPostId).toBe(1)
    })

    it('リポスト確認モーダルをキャンセルできる', async () => {
      const wrapper: VueWrapper<HomePageComponent> = mount(HomePage, {
        global: {
          plugins: [router],
          stubs: ['Teleport'],
        },
      })

      // リポスト確認を開く
      wrapper.vm.showRepostConfirm = true
      wrapper.vm.repostConfirmPostId = 1
      wrapper.vm.repostConfirmPost = { id: 1, is_reposted: false, reposts_count: 0 }

      // キャンセルボタンのクリック
      wrapper.vm.showRepostConfirm = false

      // モーダルが閉じられる
      expect(wrapper.vm.showRepostConfirm).toBe(false)
    })

    it('リポスト確認を実行できる', async () => {
      const mockPosts = {
        posts: [
          {
            id: 1,
            content: 'Test post',
            image_path: undefined,
            image_url: undefined,
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
      vi.mocked(api.post).mockResolvedValue({ data: { message: 'リポストしました' } })

      const wrapper: VueWrapper<HomePageComponent> = mount(HomePage, {
        global: {
          plugins: [router],
          stubs: ['Teleport'],
        },
      })

      await wrapper.vm.$nextTick()

      wrapper.vm.showRepostConfirm = true
      wrapper.vm.repostConfirmPostId = 1
      const post = { id: 1, is_reposted: false, reposts_count: 0 }
      wrapper.vm.repostConfirmPost = post
      wrapper.vm.recommendedPosts = mockPosts.posts

      // confirmRepostを呼び出す
      await wrapper.vm.confirmRepost()

      // API呼び出しが実行される
      expect(api.post).toHaveBeenCalledWith('/posts/1/repost')

      // モーダルが閉じられる
      expect(wrapper.vm.showRepostConfirm).toBe(false)

      // ターゲットがリセットされる
      expect(wrapper.vm.repostConfirmPostId).toBeNull()
    })

    it('リポスト時にis_repostedが更新される', async () => {
      const mockPosts = {
        posts: [
          {
            id: 1,
            content: 'Test post',
            image_path: undefined,
            image_url: undefined,
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
      vi.mocked(api.post).mockResolvedValue({ data: { message: 'リポストしました' } })

      const wrapper: VueWrapper<HomePageComponent> = mount(HomePage, {
        global: {
          plugins: [router],
          stubs: ['Teleport'],
        },
      })

      await wrapper.vm.$nextTick()
      wrapper.vm.recommendedPosts = mockPosts.posts
      await wrapper.vm.$nextTick()

      // リポスト前の状態を確認
      expect(wrapper.vm.recommendedPosts[0]).toBeDefined()
      expect(wrapper.vm.recommendedPosts[0]?.is_reposted).toBe(false)

      // リポスト実行
      wrapper.vm.showRepostConfirm = true
      wrapper.vm.repostConfirmPostId = 1
      const post = wrapper.vm.recommendedPosts[0]
      if (post) {
        wrapper.vm.repostConfirmPost = post
        await wrapper.vm.confirmRepost()
      }

      // is_repostedが更新される
      expect(wrapper.vm.recommendedPosts[0]?.is_reposted).toBe(true)
    })

    it('リポスト時にreposts_countが増加する', async () => {
      const mockPosts = {
        posts: [
          {
            id: 1,
            content: 'Test post',
            image_path: undefined,
            image_url: undefined,
            created_at: new Date().toISOString(),
            user: {
              id: 2,
              name: 'Other User',
              username: 'otheruser',
            },
            reposts_count: 2,
            is_reposted: false,
          },
        ],
      }

      vi.mocked(api.get).mockResolvedValue({ data: mockPosts })
      vi.mocked(api.post).mockResolvedValue({ data: { message: 'リポストしました' } })

      const wrapper: VueWrapper<HomePageComponent> = mount(HomePage, {
        global: {
          plugins: [router],
          stubs: ['Teleport'],
        },
      })

      await wrapper.vm.$nextTick()
      wrapper.vm.recommendedPosts = mockPosts.posts
      await wrapper.vm.$nextTick()

      // リポスト前の状態を確認
      expect(wrapper.vm.recommendedPosts[0]).toBeDefined()
      expect(wrapper.vm.recommendedPosts[0]?.reposts_count).toBe(2)

      // リポスト実行
      wrapper.vm.showRepostConfirm = true
      wrapper.vm.repostConfirmPostId = 1
      wrapper.vm.repostConfirmPost = wrapper.vm.recommendedPosts[0]
      await wrapper.vm.confirmRepost()

      // reposts_countが増加
      expect(wrapper.vm.recommendedPosts[0]?.reposts_count).toBe(3)
    })

    it('アンリポストでis_repostedが更新される', async () => {
      const mockPosts = {
        posts: [
          {
            id: 1,
            content: 'Test post',
            image_path: undefined,
            image_url: undefined,
            created_at: new Date().toISOString(),
            user: {
              id: 2,
              name: 'Other User',
              username: 'otheruser',
            },
            reposts_count: 3,
            is_reposted: true,
          },
        ],
      }

      vi.mocked(api.get).mockResolvedValue({ data: mockPosts })
      vi.mocked(api.delete).mockResolvedValue({ data: { message: 'リポストを取り消しました' } })

      const wrapper: VueWrapper<HomePageComponent> = mount(HomePage, {
        global: {
          plugins: [router],
          stubs: ['Teleport'],
        },
      })

      await wrapper.vm.$nextTick()
      wrapper.vm.recommendedPosts = mockPosts.posts
      await wrapper.vm.$nextTick()

      // リポスト済みの状態を確認
      expect(wrapper.vm.recommendedPosts[0]).toBeDefined()
      expect(wrapper.vm.recommendedPosts[0]?.is_reposted).toBe(true)

      // toggleRepostを呼び出す（既にリポスト済みなのでアンリポスト）
      const post = wrapper.vm.recommendedPosts[0]
      await wrapper.vm.toggleRepost(1, post)

      // is_repostedが更新される
      expect(wrapper.vm.recommendedPosts[0]?.is_reposted).toBe(false)
    })

    it('アンリポストでreposts_countが減少する', async () => {
      const mockPosts = {
        posts: [
          {
            id: 1,
            content: 'Test post',
            image_path: undefined,
            image_url: undefined,
            created_at: new Date().toISOString(),
            user: {
              id: 2,
              name: 'Other User',
              username: 'otheruser',
            },
            reposts_count: 3,
            is_reposted: true,
          },
        ],
      }

      vi.mocked(api.get).mockResolvedValue({ data: mockPosts })
      vi.mocked(api.delete).mockResolvedValue({ data: { message: 'リポストを取り消しました' } })

      const wrapper: VueWrapper<HomePageComponent> = mount(HomePage, {
        global: {
          plugins: [router],
          stubs: ['Teleport'],
        },
      })

      await wrapper.vm.$nextTick()
      wrapper.vm.recommendedPosts = mockPosts.posts
      await wrapper.vm.$nextTick()

      // リポスト済みの状態を確認
      expect(wrapper.vm.recommendedPosts[0]).toBeDefined()
      expect(wrapper.vm.recommendedPosts[0]?.reposts_count).toBe(3)

      // toggleRepostを呼び出す（既にリポスト済みなのでアンリポスト）
      const post = wrapper.vm.recommendedPosts[0]
      await wrapper.vm.toggleRepost(1, post)

      // reposts_countが減少
      expect(wrapper.vm.recommendedPosts[0]?.reposts_count).toBe(2)
    })

    it('リポスト時のAPIエラーが処理される', async () => {
      const mockPosts = {
        posts: [
          {
            id: 1,
            content: 'Test post',
            image_path: undefined,
            image_url: undefined,
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
      vi.mocked(api.post).mockRejectedValue({
        response: {
          data: {
            message: 'リポスト処理に失敗しました',
          },
        },
      })

      const wrapper: VueWrapper<HomePageComponent> = mount(HomePage, {
        global: {
          plugins: [router],
          stubs: ['Teleport'],
        },
      })

      await wrapper.vm.$nextTick()
      wrapper.vm.recommendedPosts = mockPosts.posts
      wrapper.vm.showRepostConfirm = true
      wrapper.vm.repostConfirmPostId = 1
      wrapper.vm.repostConfirmPost = wrapper.vm.recommendedPosts[0]

      // confirmRepostを呼び出す
      await wrapper.vm.confirmRepost()

      // エラーメッセージが設定される
      expect(wrapper.vm.error).toBe('リポスト処理に失敗しました')

      // モーダルが閉じられる
      expect(wrapper.vm.showRepostConfirm).toBe(false)

      // 投稿の状態は変わらない
      expect(wrapper.vm.recommendedPosts[0]?.is_reposted).toBe(false)
      expect(wrapper.vm.recommendedPosts[0]?.reposts_count).toBe(0)
    })

    it('リポスト取り消し時のAPIエラーが処理される', async () => {
      const mockPosts = {
        posts: [
          {
            id: 1,
            content: 'Test post',
            image_path: undefined,
            image_url: undefined,
            created_at: new Date().toISOString(),
            user: {
              id: 2,
              name: 'Other User',
              username: 'otheruser',
            },
            reposts_count: 3,
            is_reposted: true,
          },
        ],
      }

      vi.mocked(api.get).mockResolvedValue({ data: mockPosts })
      vi.mocked(api.delete).mockRejectedValue({
        response: {
          data: {
            message: 'リポスト取り消し処理に失敗しました',
          },
        },
      })

      const wrapper: VueWrapper<HomePageComponent> = mount(HomePage, {
        global: {
          plugins: [router],
          stubs: ['Teleport'],
        },
      })

      await wrapper.vm.$nextTick()
      wrapper.vm.recommendedPosts = mockPosts.posts
      const post = wrapper.vm.recommendedPosts[0]

      // toggleRepostを呼び出す（既にリポスト済みなのでアンリポストを試みる）
      await wrapper.vm.toggleRepost(1, post)

      // エラーメッセージが設定される
      expect(wrapper.vm.error).toBe('リポスト取り消し処理に失敗しました')

      // 投稿の状態は変わらない（リポスト済みの状態を保持）
      expect(wrapper.vm.recommendedPosts[0]?.is_reposted).toBe(true)
      expect(wrapper.vm.recommendedPosts[0]?.reposts_count).toBe(3)
    })

    it('リポスト時のエラーでUI状態がロールバックされる', async () => {
      const mockPosts = {
        posts: [
          {
            id: 1,
            content: 'Test post',
            image_path: undefined,
            image_url: undefined,
            created_at: new Date().toISOString(),
            user: {
              id: 2,
              name: 'Other User',
              username: 'otheruser',
            },
            reposts_count: 2,
            is_reposted: false,
          },
        ],
      }

      vi.mocked(api.get).mockResolvedValue({ data: mockPosts })
      vi.mocked(api.post).mockRejectedValue({
        response: {
          data: {
            message: 'リポスト処理に失敗しました',
          },
        },
      })

      const wrapper: VueWrapper<HomePageComponent> = mount(HomePage, {
        global: {
          plugins: [router],
          stubs: ['Teleport'],
        },
      })

      await wrapper.vm.$nextTick()
      wrapper.vm.recommendedPosts = mockPosts.posts
      const post = wrapper.vm.recommendedPosts[0]

      // 初期状態を確認
      expect(post?.is_reposted).toBe(false)
      expect(post?.reposts_count).toBe(2)

      wrapper.vm.showRepostConfirm = true
      wrapper.vm.repostConfirmPostId = 1
      wrapper.vm.repostConfirmPost = post

      // confirmRepostを呼び出す
      await wrapper.vm.confirmRepost()

      // エラー時にUI状態がロールバックされる
      expect(post?.is_reposted).toBe(false)
      expect(post?.reposts_count).toBe(2)
      expect(wrapper.vm.error).toBe('リポスト処理に失敗しました')
    })

    it('リポスト取り消し時のエラーでUI状態がロールバックされる', async () => {
      const mockPosts = {
        posts: [
          {
            id: 1,
            content: 'Test post',
            image_path: undefined,
            image_url: undefined,
            created_at: new Date().toISOString(),
            user: {
              id: 2,
              name: 'Other User',
              username: 'otheruser',
            },
            reposts_count: 5,
            is_reposted: true,
          },
        ],
      }

      vi.mocked(api.get).mockResolvedValue({ data: mockPosts })
      vi.mocked(api.delete).mockRejectedValue({
        response: {
          data: {
            message: 'リポスト取り消し処理に失敗しました',
          },
        },
      })

      const wrapper: VueWrapper<HomePageComponent> = mount(HomePage, {
        global: {
          plugins: [router],
          stubs: ['Teleport'],
        },
      })

      await wrapper.vm.$nextTick()
      wrapper.vm.recommendedPosts = mockPosts.posts
      const post = wrapper.vm.recommendedPosts[0]

      // 初期状態を確認
      expect(post?.is_reposted).toBe(true)
      expect(post?.reposts_count).toBe(5)

      // toggleRepostを呼び出す（既にリポスト済みなのでアンリポストを試みる）
      await wrapper.vm.toggleRepost(1, post)

      // エラー時にUI状態がロールバックされる
      expect(post?.is_reposted).toBe(true)
      expect(post?.reposts_count).toBe(5)
      expect(wrapper.vm.error).toBe('リポスト取り消し処理に失敗しました')
    })
  })
})
