<template>
  <Teleport to="body">
    <Transition name="modal">
      <div 
        v-if="show" 
        role="dialog"
        aria-modal="true"
        aria-labelledby="payment-modal-title"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4"
        @click="handleCancel"
      >
        <div 
          class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 transform transition-all"
          @click.stop
        >
          <!-- ヘッダー -->
          <div class="flex items-center justify-between mb-4">
            <h3 id="payment-modal-title" class="text-lg font-bold text-gray-900">
              謝礼金の支払い
            </h3>
            <button 
              @click="handleCancel"
              class="text-gray-400 hover:text-gray-600"
              :disabled="isProcessing"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <!-- 決済完了前 -->
          <div v-if="!paymentCompleted">
            <p class="text-gray-600 mb-4">
              投稿と同時に謝礼金の支払いを行います。
            </p>
            
            <div class="bg-gray-50 rounded-lg p-4 mb-4">
              <div class="flex justify-between items-center">
                <span class="text-gray-600">支払い金額</span>
                <span class="text-2xl font-bold text-gray-900">¥{{ amount.toLocaleString() }}</span>
              </div>
            </div>

            <!-- カード入力エリア -->
            <div class="mb-4 space-y-3">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                  カード番号
                </label>
                <div 
                  ref="cardNumberElement" 
                  class="p-3 border border-gray-300 rounded-lg bg-white focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500 h-11"
                ></div>
              </div>
              <div class="grid grid-cols-2 gap-3">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    有効期限
                  </label>
                  <div 
                    ref="cardExpiryElement" 
                    class="p-3 border border-gray-300 rounded-lg bg-white focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500 h-11"
                  ></div>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">
                    セキュリティコード
                  </label>
                  <div 
                    ref="cardCvcElement" 
                    class="p-3 border border-gray-300 rounded-lg bg-white focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-blue-500 h-11"
                  ></div>
                </div>
              </div>
            </div>

            <p v-if="errorMessage" class="text-red-500 text-sm mb-4">
              {{ errorMessage }}
            </p>

            <div class="flex space-x-3">
              <button 
                @click="handleCancel"
                :disabled="isProcessing"
                class="flex-1 px-4 py-3 bg-gray-100 text-gray-700 rounded-lg font-medium hover:bg-gray-200 transition disabled:opacity-50"
              >
                キャンセル
              </button>
              <button 
                @click="handlePayment"
                :disabled="isProcessing || !cardReady"
                class="flex-1 px-4 py-3 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
              >
                <svg v-if="isProcessing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ isProcessing ? '処理中...' : '支払って投稿' }}
              </button>
            </div>

            <p class="text-xs text-gray-500 mt-4 text-center">
              決済はStripeにより安全に処理されます
            </p>
          </div>

          <!-- 決済完了後 -->
          <div v-else class="text-center py-4">
            <svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <h4 class="text-xl font-bold text-gray-900 mb-2">支払い完了</h4>
            <p class="text-gray-600">投稿を作成しています...</p>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted, nextTick } from 'vue';
import { loadStripe } from '@stripe/stripe-js';
import type { Stripe, StripeElements } from '@stripe/stripe-js';

const props = defineProps<{
  show: boolean;
  amount: number;
  stripePublishableKey: string;
}>();

const emit = defineEmits<{
  'payment-completed': [clientSecret: string, paymentIntentId: string];
  'payment-error': [error: string];
  'cancel': [];
}>();

const cardNumberElement = ref<HTMLElement | null>(null);
const cardExpiryElement = ref<HTMLElement | null>(null);
const cardCvcElement = ref<HTMLElement | null>(null);
const isProcessing = ref(false);
const paymentCompleted = ref(false);
const errorMessage = ref('');
const cardReady = ref(false);

// 各フィールドの完了状態を追跡
const cardNumberComplete = ref(false);
const cardExpiryComplete = ref(false);
const cardCvcComplete = ref(false);

let stripe: Stripe | null = null;
let elements: StripeElements | null = null;
let cardNumber: any = null;
let cardExpiry: any = null;
let cardCvc: any = null;

const updateCardReady = () => {
  cardReady.value = cardNumberComplete.value && cardExpiryComplete.value && cardCvcComplete.value;
};

const elementStyle = {
  base: {
    fontSize: '16px',
    color: '#32325d',
    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
    '::placeholder': {
      color: '#aab7c4',
    },
  },
  invalid: {
    color: '#fa755a',
    iconColor: '#fa755a',
  },
};

const initializeStripe = async () => {
  try {
    if (!stripe) {
      stripe = await loadStripe(props.stripePublishableKey);
      if (!stripe) {
        throw new Error('Stripeの読み込みに失敗しました');
      }
    }

    if (!elements) {
      elements = stripe.elements();
    }

    // カード番号フィールド
    if (!cardNumber) {
      cardNumber = elements.create('cardNumber', { style: elementStyle });
      cardNumber.on('change', (event: any) => {
        cardNumberComplete.value = event.complete;
        updateCardReady();
        if (event.error) {
          errorMessage.value = event.error.message || '';
        } else if (!cardExpiryComplete.value || !cardCvcComplete.value) {
          // 他のフィールドのエラーは保持
        } else {
          errorMessage.value = '';
        }
      });
    }

    // 有効期限フィールド
    if (!cardExpiry) {
      cardExpiry = elements.create('cardExpiry', { style: elementStyle });
      cardExpiry.on('change', (event: any) => {
        cardExpiryComplete.value = event.complete;
        updateCardReady();
        if (event.error) {
          errorMessage.value = event.error.message || '';
        }
      });
    }

    // CVCフィールド
    if (!cardCvc) {
      cardCvc = elements.create('cardCvc', { style: elementStyle });
      cardCvc.on('change', (event: any) => {
        cardCvcComplete.value = event.complete;
        updateCardReady();
        if (event.error) {
          errorMessage.value = event.error.message || '';
        }
      });
    }

    await nextTick();
    if (cardNumberElement.value && cardNumber) {
      cardNumber.mount(cardNumberElement.value);
    }
    if (cardExpiryElement.value && cardExpiry) {
      cardExpiry.mount(cardExpiryElement.value);
    }
    if (cardCvcElement.value && cardCvc) {
      cardCvc.mount(cardCvcElement.value);
    }
  } catch (error: any) {
    console.error('Stripe初期化エラー:', error);
    errorMessage.value = 'Stripeの初期化に失敗しました';
  }
};

const cleanupStripe = () => {
  if (cardNumber) {
    cardNumber.unmount();
  }
  if (cardExpiry) {
    cardExpiry.unmount();
  }
  if (cardCvc) {
    cardCvc.unmount();
  }
};

// モーダルが開いたらStripeを初期化
watch(() => props.show, async (newVal) => {
  if (newVal) {
    paymentCompleted.value = false;
    errorMessage.value = '';
    cardReady.value = false;
    cardNumberComplete.value = false;
    cardExpiryComplete.value = false;
    cardCvcComplete.value = false;
    await nextTick();
    await initializeStripe();
  } else {
    cleanupStripe();
  }
});

const handlePayment = async () => {
  if (!stripe || !cardNumber) {
    errorMessage.value = 'Stripeが初期化されていません';
    return;
  }

  isProcessing.value = true;
  errorMessage.value = '';

  try {
    // PaymentMethodを作成（後でサーバーサイドで使用）
    const { error, paymentMethod } = await stripe.createPaymentMethod({
      type: 'card',
      card: cardNumber,
    });

    if (error) {
      errorMessage.value = error.message || '決済情報の検証に失敗しました';
      emit('payment-error', errorMessage.value);
      return;
    }

    if (paymentMethod) {
      paymentCompleted.value = true;
      // paymentMethod.id を親に渡す（投稿作成時に使用）
      emit('payment-completed', paymentMethod.id, '');
    }
  } catch (error: any) {
    console.error('決済エラー:', error);
    errorMessage.value = error.message || '決済処理中にエラーが発生しました';
    emit('payment-error', errorMessage.value);
  } finally {
    isProcessing.value = false;
  }
};

const handleCancel = () => {
  if (!isProcessing.value) {
    emit('cancel');
  }
};

onMounted(() => {
  if (props.show) {
    initializeStripe();
  }
});

onUnmounted(() => {
  cleanupStripe();
});
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .bg-white,
.modal-leave-active .bg-white {
  transition: transform 0.2s ease;
}

.modal-enter-from .bg-white,
.modal-leave-to .bg-white {
  transform: scale(0.95);
}
</style>
