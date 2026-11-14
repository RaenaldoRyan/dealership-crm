<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatPounds } from '@/lib/utils';
import { exportMethod, index } from '@/routes/reports';

type RepRow = {
    id: number;
    name: string;
    sold: number;
    value: string;
    conversion: number;
};

type StageCount = {
    value: string;
    label: string;
    count: number;
};

const props = defineProps<{
    range: string;
    reps: RepRow[];
    stages: StageCount[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Reports',
                href: index(),
            },
        ],
    },
});

const ranges = [
    { value: '30', label: '30 days', test: 'report-range-30' },
    { value: '90', label: '90 days', test: 'report-range-90' },
    { value: 'all', label: 'All', test: 'report-range-all' },
];

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

const exportHref = computed(() =>
    exportMethod.url({
        query: { range: props.range },
    }),
);
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <Head title="Reports" />

        <Heading title="Reports" description="Sales for the selected period" />

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                <Button
                    v-for="option in ranges"
                    :key="option.value"
                    variant="outline"
                    size="sm"
                    class="rounded-full"
                    :class="
                        range === option.value
                            ? 'bg-muted text-foreground'
                            : 'text-muted-foreground'
                    "
                    as-child
                >
                    <Link
                        :href="index.url({ query: { range: option.value } })"
                        :data-test="option.test"
                    >
                        {{ option.label }}
                    </Link>
                </Button>
            </div>

            <Button variant="outline" as-child>
                <a :href="exportHref" data-test="export-report">Export CSV</a>
            </Button>
        </div>

        <div class="overflow-hidden rounded-xl border bg-card">
            <table class="w-full text-sm">
                <thead class="border-b text-left text-muted-foreground">
                    <tr>
                        <th class="priority-1 px-4 py-3 font-medium">Rep</th>
                        <th class="priority-1 px-4 py-3 text-right font-medium">
                            Sold
                        </th>
                        <th class="priority-2 px-4 py-3 text-right font-medium">
                            Value
                        </th>
                        <th class="priority-3 px-4 py-3 text-right font-medium">
                            Conversion
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="reps.length === 0">
                        <td
                            colspan="4"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No reps yet.
                        </td>
                    </tr>
                    <tr
                        v-for="rep in reps"
                        :key="rep.id"
                        class="border-b last:border-b-0"
                        data-test="report-rep"
                    >
                        <td class="priority-1 px-4 py-3">{{ rep.name }}</td>
                        <td
                            class="priority-1 px-4 py-3 text-right tabular-nums"
                        >
                            {{ rep.sold }}
                        </td>
                        <td
                            class="priority-2 px-4 py-3 text-right tabular-nums"
                        >
                            {{ formatPounds(rep.value) }}
                        </td>
                        <td class="priority-3 px-4 py-3">
                            <span class="flex items-center justify-end gap-3">
                                <span
                                    class="h-1.5 w-16 overflow-hidden rounded-full bg-muted"
                                >
                                    <span
                                        class="block h-full rounded-full bg-primary"
                                        :style="{
                                            width: `${rep.conversion}%`,
                                        }"
                                    />
                                </span>
                                <span class="w-10 text-right tabular-nums">
                                    {{ rep.conversion }}%
                                </span>
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

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
                    <span class="h-1.5 overflow-hidden rounded-full bg-muted">
                        <span
                            class="block h-full rounded-full"
                            :style="{
                                width: barWidth(stage.count),
                                background: stageColor[stage.value],
                            }"
                        />
                    </span>
                    <span
                        class="text-right tabular-nums"
                        :data-test="`report-stage-${stage.value}`"
                    >
                        {{ stage.count }}
                    </span>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
