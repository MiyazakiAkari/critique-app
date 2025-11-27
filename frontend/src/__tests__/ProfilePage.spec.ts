import { describe, it, expect, beforeEach, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createMemoryHistory } from 'vue-router'
import ProfilePage from '../pages/ProfilePage.vue'
import api from '../utils/axios'

// API モックを作成
vi.mock('../utils/axios', () => ({
  default: {
    get: vi.fn(),
    put: vi.fn(),
    post: vi.fn(),
    delete: vi.fn(),
  },
}))

describe('ProfilePage.vue', () => {
  let router: any

  beforeEach(() => {
    // ルーターをモック
    router = createRouter({
      history: createMemoryHistory(),
      routes: [
        { path: '/profile/:username', component: ProfilePage },
        { path: '/home', component: { template: '<div>Home</div>' } },
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

  it('プロフィールページを表示する', async () => {
    const mockResponse = {
      data: {
        user: {
          id: 1,
          name: 'Test User',
          username: 'testuser',
          email: 'test@example.com',
          created_at: '2024-01-01T00:00:00.000Z',
        },
        profile: {
          id: 1,
          bio: 'Test bio',
          avatar_url: 'https://example.com/avatar.jpg',
          updated_at: '2024-01-01T00:00:00.000Z',
        },
      },
    }

    vi.mocked(api.get).mockResolvedValueOnce(mockResponse)

    await router.push('/profile/testuser')
    await router.isReady()

    const wrapper = mount(ProfilePage, {
      global: {
        plugins: [router],
      },
    })

    // ローディングが完了するまで待機
    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 100))

    expect(wrapper.text()).toContain('Test User')
    expect(wrapper.text()).toContain('@testuser')
    expect(wrapper.text()).toContain('Test bio')
  })

  it('自分のプロフィールの場合、編集ボタンが表示される', async () => {
    const mockResponse = {
      data: {
        user: {
          id: 1,
          name: 'Test User',
          username: 'testuser',
          email: 'test@example.com',
          created_at: '2024-01-01T00:00:00.000Z',
        },
        profile: {
          id: 1,
          bio: 'Test bio',
          avatar_url: null,
          updated_at: '2024-01-01T00:00:00.000Z',
        },
      },
    }

    vi.mocked(api.get).mockResolvedValueOnce(mockResponse)

    await router.push('/profile/testuser')
    await router.isReady()

    const wrapper = mount(ProfilePage, {
      global: {
        plugins: [router],
      },
    })

    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 100))

    const buttons = wrapper.findAll('button')
    const editButton = buttons.find(btn => btn.text().includes('プロフィールを編集'))
    expect(editButton).toBeDefined()
  })

  it('他のユーザーのプロフィールの場合、編集ボタンが表示されない', async () => {
    const mockResponse = {
      data: {
        user: {
          id: 2,
          name: 'Other User',
          username: 'otheruser',
          email: 'other@example.com',
          created_at: '2024-01-01T00:00:00.000Z',
        },
        profile: {
          id: 2,
          bio: 'Other bio',
          avatar_url: null,
          updated_at: '2024-01-01T00:00:00.000Z',
        },
      },
    }

    vi.mocked(api.get).mockResolvedValueOnce(mockResponse)

    await router.push('/profile/otheruser')
    await router.isReady()

    const wrapper = mount(ProfilePage, {
      global: {
        plugins: [router],
      },
    })

    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 100))

    const buttons = wrapper.findAll('button')
    const editButton = buttons.find(btn => btn.text().includes('プロフィールを編集'))
    expect(editButton).toBeUndefined()
  })

  it('プロフィールフェッチに失敗した時、エラーメッセージが表示される', async () => {
    vi.mocked(api.get).mockRejectedValueOnce({
      response: {
        data: {
          message: 'User not found',
        },
      },
    })

    await router.push('/profile/nonexistent')
    await router.isReady()

    const wrapper = mount(ProfilePage, {
      global: {
        plugins: [router],
      },
    })

    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 100))

    expect(wrapper.text()).toContain('User not found')
  })

  it('編集ボタンが押された時、編集モーダルが表示される', async () => {
    const mockResponse = {
      data: {
        user: {
          id: 1,
          name: 'Test User',
          username: 'testuser',
          email: 'test@example.com',
          created_at: '2024-01-01T00:00:00.000Z',
        },
        profile: {
          id: 1,
          bio: 'Test bio',
          avatar_url: null,
          updated_at: '2024-01-01T00:00:00.000Z',
        },
      },
    }

    vi.mocked(api.get).mockResolvedValueOnce(mockResponse)

    await router.push('/profile/testuser')
    await router.isReady()

    const wrapper = mount(ProfilePage, {
      global: {
        plugins: [router],
      },
    })

    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 100))

    // 編集ボタンをクリック
    const editButton = wrapper.find('button')
    await editButton.trigger('click')

    // モーダルが表示されているか確認
    expect(wrapper.html()).toContain('プロフィールを編集')
  })

  it('保存ボタンが押された時、プロフィールを更新する', async () => {
    const mockGetResponse = {
      data: {
        user: {
          id: 1,
          name: 'Test User',
          username: 'testuser',
          email: 'test@example.com',
          created_at: '2024-01-01T00:00:00.000Z',
        },
        profile: {
          id: 1,
          bio: 'Old bio',
          avatar_url: null,
          updated_at: '2024-01-01T00:00:00.000Z',
        },
      },
    }

    const mockPutResponse = {
      data: {
        message: 'プロフィールを更新しました',
        profile: {
          id: 1,
          bio: 'Updated bio',
          avatar_url: null,
          updated_at: '2024-01-01T01:00:00.000Z',
        },
      },
    }

    vi.mocked(api.get).mockResolvedValueOnce(mockGetResponse)
    vi.mocked(api.put).mockResolvedValueOnce(mockPutResponse)

    await router.push('/profile/testuser')
    await router.isReady()

    const wrapper = mount(ProfilePage, {
      global: {
        plugins: [router],
      },
    })

    await wrapper.vm.$nextTick()
    await new Promise(resolve => setTimeout(resolve, 100))

    // 編集モードに入る
    ;(wrapper.vm as any).isEditing = true
    ;(wrapper.vm as any).editForm.bio = 'Updated bio'
    await wrapper.vm.$nextTick()

    // 保存ボタンをクリック
    await (wrapper.vm as any).saveProfile()

    expect(api.put).toHaveBeenCalledWith('/profile', {
      bio: 'Updated bio',
      avatar_url: '',
    })
  })
})
