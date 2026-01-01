<template>
  <div class="bg-white rounded-lg shadow-md p-6">
    <h3 class="text-lg font-bold mb-4">謝礼金を設定</h3>
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-2">
        謝礼金額（円）
      </label>
      <input
        type="number"
        :value="localAmount"
        min="0"
        :max="MAX_REWARD_AMOUNT"
        step="100"
        class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent"
        placeholder="例: 1000"
        @input="handleInput"
      />
      <p class="text-xs text-gray-500 mt-1">
        最低100円から最大1000円まで設定できます。
      </p>
      <p class="text-sm text-gray-500 mt-1">
        報酬なしでも投稿できます。
      </p>
    </div>

    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-2">
        プレビュー
      </label>
      <div class="p-4 bg-gray-50 rounded-md">
        <RewardBadge :amount="localAmount" />
      </div>
    </div>

    <div class="flex gap-2">
      <button
        v-for="preset in presets"
        :key="preset"
        @click="setLocalAmount(preset)"
        class="px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded-md text-sm font-medium transition"
      >
        ¥{{ preset.toLocaleString() }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import RewardBadge from './RewardBadge.vue';

const props = defineProps<{
  modelValue: number;
}>();

const emit = defineEmits<{
  'update:modelValue': [value: number];
}>();

const MAX_REWARD_AMOUNT = 10000;

const clampRewardAmount = (value: number | undefined): number => {
  if (!Number.isFinite(value ?? NaN)) {
    return 0;
  }
  return Math.min(Math.max(Math.round(value as number), 0), MAX_REWARD_AMOUNT);
};

const presets = [500, 1000, 3000, 5000, MAX_REWARD_AMOUNT];
const localAmount = ref<number>(clampRewardAmount(props.modelValue));

const setLocalAmount = (value: number | null | undefined) => {
  const numericValue = typeof value === 'number' ? value : Number(value ?? 0);
  localAmount.value = clampRewardAmount(numericValue);
};

const handleInput = (event: Event) => {
  const target = event.target as HTMLInputElement;
  const nextValue = Number.isNaN(target.valueAsNumber)
    ? Number(target.value)
    : target.valueAsNumber;
  setLocalAmount(nextValue);
};

watch(localAmount, (newValue, oldValue) => {
  if (newValue === oldValue) return;
  emit('update:modelValue', newValue);
});

watch(
  () => props.modelValue,
  (newValue) => {
    const clamped = clampRewardAmount(newValue);
    if (clamped !== localAmount.value) {
      localAmount.value = clamped;
    }
  }
);
</script>
