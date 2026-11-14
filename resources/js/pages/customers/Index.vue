<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { index, show } from '@/routes/contacts';

type ContactRow = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    vehicles: number;
    openDeals: number;
    owner: string;
    lastActivity: string | null;
};

type Paginator = {
    data: ContactRow[];
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

const props = defineProps<{
    contacts: Paginator;
    filters: {
        search: string;
        filter: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Contacts',
                href: index(),
            },
        ],
    },
});

const search = ref(props.filters.search);

watch(
    () => props.filters.search,
    (value) => {
        search.value = value;
    },
);

const chips = [
    { value: 'all', label: 'All', test: 'contact-filter-all' },
    { value: 'mine', label: 'Mine', test: 'contact-filter-mine' },
    { value: 'open', label: 'Has open deal', test: 'contact-filter-open' },
];

function queryFor(filter: string, term: string): Record<string, string> {
    const query: Record<string, string> = {};

    if (filter !== 'all') {
        query.filter = filter;
    }

    if (term.trim() !== '') {
        query.search = term.trim();
    }

    return query;
}

function apply(): void {
    router.get(
        index.url({
            query: queryFor(props.filters.filter, search.value),
        }),
        {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function rangeLabel(): string {
    if (
        props.contacts.total === 0 ||
        props.contacts.from === null ||
        props.contacts.to === null
    ) {
        return `0 of ${props.contacts.total}`;
    }

    return `${props.contacts.from}–${props.contacts.to} of ${props.contacts.total}`;
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <Head title="Contacts" />

        <Heading title="Contacts" />

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <Button
                    v-for="chip in chips"
                    :key="chip.value"
                    variant="outline"
                    size="sm"
                    class="rounded-full"
                    :class="
                        filters.filter === chip.value
                            ? 'bg-muted text-foreground'
                            : 'text-muted-foreground'
                    "
                    as-child
                >
                    <Link
                        :href="
                            index.url({ query: queryFor(chip.value, search) })
                        "
                        :data-test="chip.test"
                    >
                        {{ chip.label }}
                    </Link>
                </Button>
            </div>

            <form @submit.prevent="apply">
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Search"
                    class="w-56 shadow-none"
                    data-test="contacts-search"
                    @change="apply"
                />
            </form>
        </div>

        <div class="overflow-hidden rounded-xl border bg-card">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b text-left text-muted-foreground">
                        <tr>
                            <th class="priority-1 px-4 py-3 font-medium">
                                Name
                            </th>
                            <th class="priority-2 px-4 py-3 font-medium">
                                Email
                            </th>
                            <th class="priority-4 px-4 py-3 font-medium">
                                Phone
                            </th>
                            <th class="priority-4 px-4 py-3 font-medium">
                                Vehicles
                            </th>
                            <th class="priority-2 px-4 py-3 font-medium">
                                Open deals
                            </th>
                            <th class="priority-5 px-4 py-3 font-medium">
                                Owner
                            </th>
                            <th class="priority-3 px-4 py-3 font-medium">
                                Last activity
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="contacts.data.length === 0">
                            <td
                                colspan="7"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                No contacts yet.
                            </td>
                        </tr>
                        <tr
                            v-for="contact in contacts.data"
                            :key="contact.id"
                            class="border-b last:border-0"
                        >
                            <td class="priority-1 px-4 py-3">
                                <Link
                                    :href="show(contact.id)"
                                    class="font-medium hover:text-primary"
                                >
                                    {{ contact.name }}
                                </Link>
                            </td>
                            <td class="priority-2 px-4 py-3">
                                {{ contact.email }}
                            </td>
                            <td class="priority-4 px-4 py-3">
                                {{ contact.phone ?? '—' }}
                            </td>
                            <td class="priority-4 px-4 py-3 tabular-nums">
                                {{ contact.vehicles }}
                            </td>
                            <td class="priority-2 px-4 py-3 tabular-nums">
                                {{ contact.openDeals }}
                            </td>
                            <td class="priority-5 px-4 py-3">
                                {{ contact.owner }}
                            </td>
                            <td
                                class="priority-3 px-4 py-3 text-muted-foreground"
                            >
                                {{ contact.lastActivity ?? '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div
                class="flex items-center justify-between border-t px-4 py-3 text-sm text-muted-foreground"
            >
                <span data-test="contacts-range">{{ rangeLabel() }}</span>
                <div class="flex items-center gap-1">
                    <Button
                        v-if="contacts.prev_page_url"
                        variant="outline"
                        size="icon"
                        as-child
                    >
                        <Link
                            :href="contacts.prev_page_url"
                            preserve-scroll
                            aria-label="Previous page"
                            data-test="contacts-prev"
                        >
                            <ChevronLeft />
                        </Link>
                    </Button>
                    <Button
                        v-else
                        variant="outline"
                        size="icon"
                        disabled
                        aria-label="Previous page"
                    >
                        <ChevronLeft />
                    </Button>
                    <Button
                        v-if="contacts.next_page_url"
                        variant="outline"
                        size="icon"
                        as-child
                    >
                        <Link
                            :href="contacts.next_page_url"
                            preserve-scroll
                            aria-label="Next page"
                            data-test="contacts-next"
                        >
                            <ChevronRight />
                        </Link>
                    </Button>
                    <Button
                        v-else
                        variant="outline"
                        size="icon"
                        disabled
                        aria-label="Next page"
                    >
                        <ChevronRight />
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
