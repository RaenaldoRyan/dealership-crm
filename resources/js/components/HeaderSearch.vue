<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Input } from '@/components/ui/input';
import {
    CommandDialog,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import { search } from '@/routes';

type Result = {
    id: number;
    label: string;
    detail: string;
    href: string;
};

type Results = {
    contacts: Result[];
    deals: Result[];
    vehicles: Result[];
};

const open = ref(false);
const query = ref('');
const results = ref<Results>({ contacts: [], deals: [], vehicles: [] });
const pending = ref(false);

const shortcut = computed(() =>
    /Mac|iPhone|iPad/.test(navigator.userAgent) ? '⌘K' : 'Ctrl K',
);

const hasResults = computed(
    () =>
        results.value.contacts.length > 0 ||
        results.value.deals.length > 0 ||
        results.value.vehicles.length > 0,
);

let requestId = 0;

watchDebounced(
    query,
    async (value) => {
        const term = value.trim();
        const current = ++requestId;

        if (term === '') {
            results.value = { contacts: [], deals: [], vehicles: [] };
            pending.value = false;

            return;
        }

        pending.value = true;

        const response = await fetch(search.url({ query: { q: term } }), {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (current !== requestId) {
            return;
        }

        pending.value = false;
        results.value = response.ok
            ? ((await response.json()) as Results)
            : { contacts: [], deals: [], vehicles: [] };
    },
    { debounce: 200 },
);

function onKeydown(event: KeyboardEvent): void {
    if (event.key.toLowerCase() === 'k' && (event.metaKey || event.ctrlKey)) {
        event.preventDefault();
        open.value = true;
    }
}

function visit(href: string): void {
    open.value = false;
    query.value = '';
    router.visit(href);
}

onMounted(() => window.addEventListener('keydown', onKeydown));
onUnmounted(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <label class="relative hidden w-64 sm:block">
        <span class="sr-only">Search</span>
        <Search
            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
        />
        <Input
            type="search"
            placeholder="Search"
            readonly
            class="cursor-pointer bg-card pr-16 pl-9 shadow-none"
            data-test="header-search"
            @click="open = true"
            @keydown.enter.prevent="open = true"
        />
        <span
            class="pointer-events-none absolute top-1/2 right-2 -translate-y-1/2 rounded-md border px-1.5 py-0.5 text-[10px] text-muted-foreground"
        >
            {{ shortcut }}
        </span>
    </label>

    <CommandDialog v-model:open="open">
        <CommandInput
            v-model="query"
            placeholder="Search contacts, deals, and registrations"
            data-test="command-search"
        />
        <CommandList>
            <CommandEmpty v-if="query.trim() !== '' && !pending && !hasResults">
                No results.
            </CommandEmpty>
            <CommandGroup v-if="results.contacts.length > 0" heading="Contacts">
                <CommandItem
                    v-for="item in results.contacts"
                    :key="`contact-${item.id}`"
                    :value="item.href"
                    data-test="search-result"
                    @select="visit(item.href)"
                >
                    <span class="min-w-0 flex-1 truncate">{{
                        item.label
                    }}</span>
                    <span class="truncate text-muted-foreground">{{
                        item.detail
                    }}</span>
                </CommandItem>
            </CommandGroup>
            <CommandGroup v-if="results.deals.length > 0" heading="Deals">
                <CommandItem
                    v-for="item in results.deals"
                    :key="`deal-${item.id}`"
                    :value="item.href"
                    data-test="search-result"
                    @select="visit(item.href)"
                >
                    <span class="min-w-0 flex-1 truncate">{{
                        item.label
                    }}</span>
                    <span class="text-muted-foreground">{{ item.detail }}</span>
                </CommandItem>
            </CommandGroup>
            <CommandGroup v-if="results.vehicles.length > 0" heading="Vehicles">
                <CommandItem
                    v-for="item in results.vehicles"
                    :key="`vehicle-${item.id}`"
                    :value="item.href"
                    data-test="search-result"
                    @select="visit(item.href)"
                >
                    <span class="min-w-0 flex-1 truncate">{{
                        item.label
                    }}</span>
                    <span class="truncate text-muted-foreground">{{
                        item.detail
                    }}</span>
                </CommandItem>
            </CommandGroup>
        </CommandList>
    </CommandDialog>
</template>
