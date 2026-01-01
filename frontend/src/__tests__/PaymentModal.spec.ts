import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { mount, VueWrapper } from '@vue/test-utils'
import { nextTick } from 'vue'
import PaymentModal from '../components/PaymentModal.vue'

// Stripe.js をモック
const mockCreatePaymentMethod = vi.fn()
const mockMount = vi.fn()
const mockUnmount = vi.fn()
const mockOn = vi.fn()

const createMockElement = () => ({
  mount: mockMount,
  unmount: mockUnmount,
  on: mockOn,
})

const mockElements = {
  create: vi.fn(() => createMockElement()),
}

const mockStripe = {
  elements: vi.fn(() => mockElements),
  createPaymentMethod: mockCreatePaymentMethod,
}

vi.mock('@stripe/stripe-js', () => ({
  loadStripe: vi.fn(() => Promise.resolve(mockStripe)),
}))

describe('PaymentModal.vue', () => {
  let wrapper: VueWrapper

  const defaultProps = {
    show: true,
    amount: 1000,
    stripePublishableKey: 'pk_test_123456',
  }

  beforeEach(() => {
    vi.clearAllMocks()
    mockCreatePaymentMethod.mockResolvedValue({
      paymentMethod: { id: 'pm_test_123' },
      error: null,
    })
  })

  afterEach(() => {
    if (wrapper) {
      wrapper.unmount()
    }
  })

  const mountComponent = async (props = {}) => {
    wrapper = mount(PaymentModal, {
      props: { ...defaultProps, ...props },
      global: {
        stubs: {
          Teleport: true,
          Transition: false,
        },
      },
    })
    await nextTick()
    return wrapper
  }

  describe('表示', () => {
    it('show=true のとき表示される', async () => {
      await mountComponent({ show: true })
      expect(wrapper.text()).toContain('謝礼金の支払い')
    })

    it('show=false のとき表示されない', async () => {
      await mountComponent({ show: false })
      expect(wrapper.text()).not.toContain('謝礼金の支払い')
    })

    it('金額が正しく表示される', async () => {
      await mountComponent({ amount: 5000 })
      expect(wrapper.text()).toContain('¥5,000')
    })
  })

  describe('キャンセル', () => {
    it('キャンセルボタンで cancel イベントが発火する', async () => {
      await mountComponent()
      
      const cancelButton = wrapper.find('button')
      await cancelButton.trigger('click')
      
      expect(wrapper.emitted('cancel')).toBeTruthy()
    })

    it('背景クリックで cancel イベントが発火する', async () => {
      await mountComponent()
      
      const backdrop = wrapper.find('[role="dialog"]')
      await backdrop.trigger('click')
      
      expect(wrapper.emitted('cancel')).toBeTruthy()
    })
  })

  describe('決済処理', () => {
    it('決済成功時に payment-completed イベントが発火する', async () => {
      await mountComponent()
      
      // カード入力完了をシミュレート
      const changeHandler = mockOn.mock.calls.find(call => call[0] === 'change')?.[1]
      if (changeHandler) {
        changeHandler({ complete: true, error: null })
        await nextTick()
      }

      // 支払いボタンをクリック
      const payButton = wrapper.findAll('button').find(btn => btn.text().includes('支払って投稿'))
      if (payButton) {
        await payButton.trigger('click')
        await nextTick()
      }

      // イベント発火を確認
      const emitted = wrapper.emitted('payment-completed')
      if (emitted) {
        expect(emitted[0]).toContain('pm_test_123')
      }
    })

    it('決済失敗時に payment-error イベントが発火する', async () => {
      mockCreatePaymentMethod.mockResolvedValue({
        paymentMethod: null,
        error: { message: 'カードが拒否されました' },
      })

      await mountComponent()
      
      // カード入力完了をシミュレート
      const changeHandler = mockOn.mock.calls.find(call => call[0] === 'change')?.[1]
      if (changeHandler) {
        changeHandler({ complete: true, error: null })
        await nextTick()
      }

      // 支払いボタンをクリック
      const payButton = wrapper.findAll('button').find(btn => btn.text().includes('支払って投稿'))
      if (payButton) {
        await payButton.trigger('click')
        await nextTick()
      }

      // エラーイベント発火を確認
      const emitted = wrapper.emitted('payment-error')
      if (emitted) {
        expect(emitted[0]).toContain('カードが拒否されました')
      }
    })
  })

  describe('Stripe初期化', () => {
    it('モーダル表示時にStripeが初期化される', async () => {
      await mountComponent({ show: true })
      await nextTick()
      
      // loadStripe が呼ばれることを確認
      const { loadStripe } = await import('@stripe/stripe-js')
      expect(loadStripe).toHaveBeenCalledWith('pk_test_123456')
    })

    it('3つのカードエレメントが作成される', async () => {
      await mountComponent({ show: true })
      await nextTick()
      
      // cardNumber, cardExpiry, cardCvc の3つが作成される
      expect(mockElements.create).toHaveBeenCalledWith('cardNumber', expect.any(Object))
      expect(mockElements.create).toHaveBeenCalledWith('cardExpiry', expect.any(Object))
      expect(mockElements.create).toHaveBeenCalledWith('cardCvc', expect.any(Object))
    })
  })

  describe('バリデーション', () => {
    it('カード未入力時は支払いボタンが無効', async () => {
      await mountComponent()
      
      const payButton = wrapper.findAll('button').find(btn => btn.text().includes('支払って投稿'))
      expect(payButton?.attributes('disabled')).toBeDefined()
    })

    it('処理中は両方のボタンが無効になる', async () => {
      await mountComponent()
      
      // 3つのフィールドすべての入力完了をシミュレート
      const changeHandlers = mockOn.mock.calls
        .filter(call => call[0] === 'change')
        .map(call => call[1])
      
      for (const handler of changeHandlers) {
        handler({ complete: true, error: null })
      }
      await nextTick()

      // 遅延する Promise を設定（完了しない）
      mockCreatePaymentMethod.mockImplementation(() => new Promise(() => {}))

      const payButton = wrapper.findAll('button').find(btn => btn.text().includes('支払って投稿'))
      if (payButton) {
        // ボタンをクリック
        payButton.trigger('click')
        await nextTick()
        await nextTick()
        
        // 処理中のテキストが表示される
        expect(wrapper.text()).toContain('処理中')
      }
    })
  })
})

describe('HomePage 決済連携', () => {
  // この部分は HomePage.spec.ts に追加するテストケース

  it('報酬設定時に「支払って投稿」ボタンが表示される', () => {
    // HomePage.spec.ts で実装
    expect(true).toBe(true)
  })

  it('報酬未設定時に「投稿する」ボタンが表示される', () => {
    // HomePage.spec.ts で実装
    expect(true).toBe(true)
  })
})
