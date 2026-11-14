<script setup lang="ts">
type Props = {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
};

withDefaults(defineProps<Props>(), {
    variant: 'default',
});
</script>

<template>
    <header :class="variant === 'small' ? '' : 'mb-2 space-y-1'">
        <h1
            v-if="variant === 'default'"
            class="font-heading text-3xl font-medium tracking-tight"
        >
            {{ title }}
        </h1>
        <h2 v-else class="mb-0.5 text-base font-medium">
            {{ title }}
        </h2>
        <p v-if="description" class="text-sm text-muted-foreground">
            {{ description }}
        </p>
    </header>
</template>
