<template>
  <div
    v-if="numericAmount > 0"
    :class="badgeClass"
    :style="badgeStyle"
    class="inline-flex items-center px-3 py-1 rounded-full font-bold text-sm shadow-md"
  >
    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
      <path d="M8.433 7.418c.155-.103.346-.196.567-.267v1.698a2.305 2.305 0 01-.567-.267C8.07 8.34 8 8.114 8 8c0-.114.07-.34.433-.582zM11 12.849v-1.698c.22.071.412.164.567.267.364.243.433.468.433.582 0 .114-.07.34-.433.582a2.305 2.305 0 01-.567.267z"/>
      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-13a1 1 0 10-2 0v.092a4.535 4.535 0 00-1.676.662C6.602 6.234 6 7.009 6 8c0 .99.602 1.765 1.324 2.246.48.32 1.054.545 1.676.662v1.941c-.391-.127-.68-.317-.843-.504a1 1 0 10-1.51 1.31c.562.649 1.413 1.076 2.353 1.253V15a1 1 0 102 0v-.092a4.535 4.535 0 001.676-.662C13.398 13.766 14 12.991 14 12c0-.99-.602-1.765-1.324-2.246A4.535 4.535 0 0011 9.092V7.151c.391.127.68.317.843.504a1 1 0 101.511-1.31c-.563-.649-1.413-1.076-2.354-1.253V5z" clip-rule="evenodd"/>
    </svg>
    <span>{{ formattedAmount }}</span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  amount: number | string;
}>();

const numericAmount = computed(() => {
  if (typeof props.amount === 'number') {
    return Math.max(0, props.amount);
  }

  if (typeof props.amount === 'string') {
    // 報酬額は常に正の数のため、マイナス記号は除外
    const sanitized = props.amount.replace(/[^0-9.]/g, '');
    const parsed = Number(sanitized);
    return Number.isFinite(parsed) ? Math.max(0, parsed) : 0;
  }

  return 0;
});

const formattedAmount = computed(() => {
  return `¥${numericAmount.value.toLocaleString('ja-JP')}`;
});

const badgeStyle = computed(() => {
  const amt = numericAmount.value;

  if (amt >= 10000) {
    return { backgroundImage: 'linear-gradient(90deg, #7e22ce, #db2777)' };
  } else if (amt >= 5000) {
    return { backgroundImage: 'linear-gradient(90deg, #f97316, #dc2626)' };
  } else if (amt >= 1000) {
    return { backgroundImage: 'linear-gradient(90deg, #facc15, #fb923c)' };
  } else if (amt > 0) {
    return { backgroundImage: 'linear-gradient(90deg, #4ade80, #3b82f6)' };
  }

  return {};
});

const badgeClass = computed(() => {
  const amt = numericAmount.value;

  if (amt >= 10000) {
    return 'text-white animate-pulse';
  } else if (amt >= 5000) {
    return 'text-white';
  } else if (amt >= 1000) {
    return 'text-gray-900';
  } else if (amt > 0) {
    return 'text-white';
  }

  return '';
});
</script>
