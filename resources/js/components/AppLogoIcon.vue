<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="16 12 32 40"
        :class="className"
        v-bind="$attrs"
    >
        <path
            d="M22 48V22a6 6 0 0 1 6-6h8a10 10 0 0 1 0 20h-6c-4 0-8 4-8 12z"
            fill="#34d399"
        />
        <path
            d="M30 24h6a4 4 0 0 1 0 8h-6z"
            fill="var(--logo-cutout, #0b1020)"
        />
    </svg>
</template>
