<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatPounds } from '@/lib/utils';
import { index, show } from '@/routes/vehicles';

type VehicleRow = {
    id: number;
    registration: string;
    make: string;
    model: string;
    year: number;
    mileage: number | null;
    price: string;
    status: string;
    statusLabel: string;
    customer: string | null;
    deal: { id: number; label: string } | null;
};

type Paginator = {
    data: VehicleRow[];
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

type Option = {
    value: string;
    label: string;
};

const props = defineProps<{
    vehicles: Paginator;
    filters: {
        search: string;
        status: string;
    };
    statuses: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Inventory',
                href: index(),
            },
        ],
    },
});

const search = ref(props.filters.search);
const status = ref(props.filters.status === '' ? 'all' : props.filters.status);

const statusDot: Record<string, string> = {
    in_stock: 'var(--stage-enquiry)',
    reserved: 'var(--stage-negotiation)',
    sold: 'var(--stage-sold)',
};

watch(
    () => props.filters,
    (filters) => {
        search.value = filters.search;
        status.value = filters.status === '' ? 'all' : filters.status;
    },
);

function apply(): void {
    const query: Record<string, string> = {};

    if (search.value.trim() !== '') {
        query.search = search.value.trim();
    }

    if (status.value !== 'all') {
        query.status = status.value;
    }

    router.get(
        index.url({ query }),
        {},
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function chooseStatus(value: unknown): void {
    status.value = typeof value === 'string' ? value : 'all';
    apply();
}

function formatMileage(value: number | null): string {
    if (value === null) {
        return '—';
    }

    return new Intl.NumberFormat('en-GB').format(value);
}

function rangeLabel(): string {
    if (
        props.vehicles.total === 0 ||
        props.vehicles.from === null ||
        props.vehicles.to === null
    ) {
        return `0 of ${props.vehicles.total}`;
    }

    return `${props.vehicles.from}–${props.vehicles.to} of ${props.vehicles.total}`;
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <Head title="Inventory" />

        <Heading title="Inventory" description="Stock and customer vehicles" />

        <form class="flex flex-wrap items-center gap-3" @submit.prevent="apply">
            <Input
                v-model="search"
                type="search"
                placeholder="Search"
                class="w-full max-w-xs shadow-none"
                data-test="inventory-search"
                @change="apply"
            />
            <Select :model-value="status" @update:model-value="chooseStatus">
                <SelectTrigger
                    class="w-44 shadow-none"
                    data-test="inventory-status"
                >
                    <SelectValue placeholder="All statuses" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All statuses</SelectItem>
                    <SelectItem
                        v-for="option in statuses"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </form>

        <div class="overflow-hidden rounded-xl border bg-card">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b text-left text-muted-foreground">
                        <tr>
                            <th class="priority-1 px-4 py-3 font-medium">
                                Registration
                            </th>
                            <th class="priority-2 px-4 py-3 font-medium">
                                Vehicle
                            </th>
                            <th class="priority-4 px-4 py-3 font-medium">
                                Year
                            </th>
                            <th class="priority-5 px-4 py-3 font-medium">
                                Mileage
                            </th>
                            <th class="priority-2 px-4 py-3 font-medium">
                                Price
                            </th>
                            <th class="priority-3 px-4 py-3 font-medium">
                                Status
                            </th>
                            <th class="priority-4 px-4 py-3 font-medium">
                                Customer
                            </th>
                            <th class="priority-5 px-4 py-3 font-medium">
                                Deal
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="vehicles.data.length === 0">
                            <td
                                colspan="8"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                No vehicles yet.
                            </td>
                        </tr>
                        <tr
                            v-for="vehicle in vehicles.data"
                            :key="vehicle.id"
                            class="border-b last:border-b-0"
                        >
                            <td class="priority-1 px-4 py-3">
                                <Link
                                    :href="show(vehicle.id)"
                                    class="inline-flex rounded-md bg-chart-3 px-2 py-1 font-heading text-sm font-semibold tracking-wider text-foreground dark:bg-foreground dark:text-background"
                                    :data-test="`vehicle-plate-${vehicle.id}`"
                                >
                                    {{ vehicle.registration }}
                                </Link>
                            </td>
                            <td class="priority-2 px-4 py-3">
                                {{ vehicle.make }} {{ vehicle.model }}
                            </td>
                            <td class="priority-4 px-4 py-3 tabular-nums">
                                {{ vehicle.year }}
                            </td>
                            <td class="priority-5 px-4 py-3 tabular-nums">
                                {{ formatMileage(vehicle.mileage) }}
                            </td>
                            <td class="priority-2 px-4 py-3 tabular-nums">
                                {{ formatPounds(vehicle.price) }}
                            </td>
                            <td class="priority-3 px-4 py-3">
                                <span class="inline-flex items-center gap-2">
                                    <span
                                        class="size-2 rounded-full"
                                        :style="{
                                            backgroundColor:
                                                statusDot[vehicle.status],
                                        }"
                                    />
                                    {{ vehicle.statusLabel }}
                                </span>
                            </td>
                            <td class="priority-4 px-4 py-3">
                                {{ vehicle.customer ?? '—' }}
                            </td>
                            <td class="priority-5 px-4 py-3">
                                {{ vehicle.deal?.label ?? '—' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div
                class="flex items-center justify-between border-t px-4 py-3 text-sm text-muted-foreground"
            >
                <span data-test="inventory-range">{{ rangeLabel() }}</span>
                <div class="flex items-center gap-1">
                    <Button
                        v-if="vehicles.prev_page_url"
                        variant="outline"
                        size="icon"
                        as-child
                    >
                        <Link
                            :href="vehicles.prev_page_url"
                            preserve-scroll
                            aria-label="Previous page"
                            data-test="inventory-prev"
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
                        v-if="vehicles.next_page_url"
                        variant="outline"
                        size="icon"
                        as-child
                    >
                        <Link
                            :href="vehicles.next_page_url"
                            preserve-scroll
                            aria-label="Next page"
                            data-test="inventory-next"
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
