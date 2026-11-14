<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatPounds } from '@/lib/utils';
import { overview } from '@/routes';

type RepOption = {
    id: number;
    name: string;
};

type StageCount = {
    value: string;
    label: string;
    count: number;
};

type RecentDeal = {
    id: number;
    contact: string;
    vehicle: string | null;
    value: string;
    stage: string;
    stageLabel: string;
};

const props = defineProps<{
    canFilterReps: boolean;
    reps: RepOption[];
    selectedRep: number | null;
    metrics: {
        pipelineValue: string;
        openDeals: number;
        conversion: number;
        averageDaysToClose: number;
    };
    stages: StageCount[];
    inventory: {
        inStock: number;
        linkedToDeals: number;
        averagePrice: string;
    };
    recentDeals: RecentDeal[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Overview',
                href: overview(),
            },
        ],
    },
});

const stageColor: Record<string, string> = {
    enquiry: 'var(--stage-enquiry)',
    test_drive: 'var(--stage-test-drive)',
    negotiation: 'var(--stage-negotiation)',
    sold: 'var(--stage-sold)',
    lost: 'var(--stage-lost)',
};

const widestStage = computed(() =>
    Math.max(...props.stages.map((stage) => stage.count), 0),
);

function barWidth(count: number): string {
    if (widestStage.value === 0) {
        return '0%';
    }

    return `${(count / widestStage.value) * 100}%`;
}

function filterRep(rep: unknown): void {
    if (typeof rep !== 'string' || rep === '') {
        return;
    }

    router.get(
        overview.url({
            query: rep === 'all' ? {} : { rep },
        }),
        {},
        { preserveScroll: true, preserveState: true },
    );
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <Head title="Overview" />

        <div class="flex flex-wrap items-center justify-between gap-4">
            <Heading title="Overview" />

            <Select
                v-if="canFilterReps"
                :model-value="
                    selectedRep === null ? 'all' : String(selectedRep)
                "
                @update:model-value="filterRep"
            >
                <SelectTrigger class="w-48 shadow-none" data-test="rep-filter">
                    <SelectValue placeholder="All reps" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All reps</SelectItem>
                    <SelectItem
                        v-for="rep in reps"
                        :key="rep.id"
                        :value="String(rep.id)"
                    >
                        {{ rep.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Card class="gap-2 py-5 shadow-none">
                <CardContent class="grid gap-1">
                    <p class="text-sm text-muted-foreground">Pipeline value</p>
                    <p class="font-heading text-3xl tabular-nums">
                        {{ formatPounds(metrics.pipelineValue) }}
                    </p>
                </CardContent>
            </Card>
            <Card class="gap-2 py-5 shadow-none">
                <CardContent class="grid gap-1">
                    <p class="text-sm text-muted-foreground">Open deals</p>
                    <p class="font-heading text-3xl tabular-nums">
                        {{ metrics.openDeals }}
                    </p>
                </CardContent>
            </Card>
            <Card class="gap-2 py-5 shadow-none">
                <CardContent class="grid gap-1">
                    <p class="text-sm text-muted-foreground">Conversion</p>
                    <p class="font-heading text-3xl tabular-nums">
                        {{ metrics.conversion }}%
                    </p>
                </CardContent>
            </Card>
            <Card class="gap-2 py-5 shadow-none">
                <CardContent class="grid gap-1">
                    <p class="text-sm text-muted-foreground">
                        Avg. days to close
                    </p>
                    <p class="font-heading text-3xl tabular-nums">
                        {{ metrics.averageDaysToClose }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card class="shadow-none">
                <CardHeader>
                    <CardTitle class="font-heading text-lg font-medium">
                        Deals by stage
                    </CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4">
                    <div
                        v-for="stage in stages"
                        :key="stage.value"
                        class="grid grid-cols-[7.5rem_1fr_2rem] items-center gap-3 text-sm"
                    >
                        <span class="flex items-center gap-2">
                            <span
                                class="inline-block size-1.5 rounded-full"
                                :style="{ background: stageColor[stage.value] }"
                            />
                            {{ stage.label }}
                        </span>
                        <span
                            class="h-1.5 overflow-hidden rounded-full bg-muted"
                        >
                            <span
                                class="block h-full rounded-full"
                                :style="{
                                    width: barWidth(stage.count),
                                    background: stageColor[stage.value],
                                }"
                            />
                        </span>
                        <span class="text-right tabular-nums">{{
                            stage.count
                        }}</span>
                    </div>
                </CardContent>
            </Card>

            <Card class="shadow-none">
                <CardHeader>
                    <CardTitle class="font-heading text-lg font-medium">
                        Inventory
                    </CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-muted-foreground">In stock</span>
                        <span class="tabular-nums">{{
                            inventory.inStock
                        }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-muted-foreground"
                            >Linked to deals</span
                        >
                        <span class="tabular-nums">{{
                            inventory.linkedToDeals
                        }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-muted-foreground">Avg. price</span>
                        <span class="tabular-nums">
                            {{ formatPounds(inventory.averagePrice) }}
                        </span>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card class="gap-0 py-0 shadow-none">
            <CardHeader class="pt-6">
                <CardTitle class="font-heading text-lg font-medium">
                    Recent deals
                </CardTitle>
            </CardHeader>
            <CardContent class="px-0">
                <table class="w-full text-sm">
                    <thead class="border-y text-left text-muted-foreground">
                        <tr>
                            <th
                                class="priority-1 px-4 py-3 font-medium sm:px-6"
                            >
                                Contact
                            </th>
                            <th
                                class="priority-4 px-4 py-3 font-medium sm:px-6"
                            >
                                Vehicle
                            </th>
                            <th
                                class="priority-2 px-4 py-3 font-medium sm:px-6"
                            >
                                Value
                            </th>
                            <th
                                class="priority-2 px-4 py-3 font-medium sm:px-6"
                            >
                                Stage
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="recentDeals.length === 0">
                            <td
                                colspan="4"
                                class="px-6 py-8 text-center text-muted-foreground"
                            >
                                No deals yet.
                            </td>
                        </tr>
                        <tr
                            v-for="deal in recentDeals"
                            :key="deal.id"
                            class="border-b last:border-0"
                        >
                            <td class="priority-1 px-4 py-3 sm:px-6">
                                {{ deal.contact }}
                            </td>
                            <td class="priority-4 px-4 py-3 sm:px-6">
                                {{ deal.vehicle ?? '—' }}
                            </td>
                            <td
                                class="priority-2 px-4 py-3 tabular-nums sm:px-6"
                            >
                                {{ formatPounds(deal.value) }}
                            </td>
                            <td class="priority-2 px-4 py-3 sm:px-6">
                                <span class="inline-flex items-center gap-2">
                                    <span
                                        class="inline-block size-1.5 rounded-full"
                                        :style="{
                                            background: stageColor[deal.stage],
                                        }"
                                    />
                                    {{ deal.stageLabel }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>
    </div>
</template>
