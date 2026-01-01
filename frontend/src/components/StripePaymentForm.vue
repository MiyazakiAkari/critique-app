<template>
  <div class="bg-white rounded-lg shadow-md p-6">
    <h3 class="text-lg font-bold mb-4">謝礼金の支払い</h3>
    
    <div v-if="!paymentCompleted" class="mb-4">
      <p class="text-gray-700 mb-4">
        支払い金額: <span class="font-bold text-lg">¥{{ amount.toLocaleString() }}</span>
      </p>

      <div ref="cardElement" class="p-3 border border-gray-300 rounded-md mb-4"></div>

      <button
        @click="handlePayment"
        :disabled="isProcessing"
        class="w-full py-3 bg-blue-600 text-white rounded-md font-medium hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition"
      >
        <span v-if="isProcessing">処理中...</span>
        <span v-else>支払う</span>
      </button>

      <p v-if="errorMessage" class="text-red-500 text-sm mt-2">
        {{ errorMessage }}
      </p>
    </div>

    <div v-else class="text-center py-8">
      <svg class="w-16 h-16 text-green-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
      </svg>
      <h4 class="text-xl font-bold text-gray-900 mb-2">支払いが完了しました</h4>
      <p class="text-gray-600">謝礼金が設定されました</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { loadStripe, Stripe, StripeElements, StripeCardElement } from '@stripe/stripe-js';
import axios from 'axios';

const props = defineProps<{
  postId: number;
  amount: number;
  stripePublishableKey: string;
}>();

const emit = defineEmits<{
  'payment-completed': [paymentIntentId: string];
  'payment-error': [error: string];
}>();

const cardElement = ref<HTMLElement | null>(null);
const isProcessing = ref(false);
const paymentCompleted = ref(false);
const errorMessage = ref('');

let stripe: Stripe | null = null;
let elements: StripeElements | null = null;
let card: StripeCardElement | null = null;

onMounted(async () => {
  try {
    stripe = await loadStripe(props.stripePublishableKey);
    if (!stripe) {
      throw new Error('Stripeの読み込みに失敗しました');
    }

    elements = stripe.elements();
    card = elements.create('card', {
      style: {
        base: {
          fontSize: '16px',
          color: '#32325d',
          fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
          '::placeholder': {
            color: '#aab7c4',
          },
        },
        invalid: {
          color: '#fa755a',
          iconColor: '#fa755a',
        },
      },
    });

    if (cardElement.value) {
      card.mount(cardElement.value);
    }
  } catch (error) {
    console.error('Stripe初期化エラー:', error);
    errorMessage.value = 'Stripeの初期化に失敗しました';
  }
});

const handlePayment = async () => {
  if (!stripe || !card) {
    errorMessage.value = 'Stripeが初期化されていません';
    return;
  }

  isProcessing.value = true;
  errorMessage.value = '';

  try {
    // バックエンドからPaymentIntentを作成
    const { data } = await axios.post('/api/stripe/payment-intent', {
      post_id: props.postId,
      amount: props.amount,
    });

    // 決済を確認
    const { error, paymentIntent } = await stripe.confirmCardPayment(data.clientSecret, {
      payment_method: {
        card: card,
      },
    });

    if (error) {
      errorMessage.value = error.message || '決済に失敗しました';
      emit('payment-error', errorMessage.value);
    } else if (paymentIntent && paymentIntent.status === 'succeeded') {
      paymentCompleted.value = true;
      emit('payment-completed', paymentIntent.id);
    }
  } catch (error: any) {
    console.error('決済エラー:', error);
    errorMessage.value = error.response?.data?.error || '決済処理中にエラーが発生しました';
    emit('payment-error', errorMessage.value);
  } finally {
    isProcessing.value = false;
  }
};
</script>
