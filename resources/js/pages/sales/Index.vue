<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    index,
    show,
    update,
} from '@/actions/App/Sales/Http/Controllers/DealController';
import Heading from '@/components/Heading.vue';
import { Card, CardContent } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatPounds } from '@/lib/utils';

type StageOption = {
    value: string;
    label: string;
};

type DealRow = {
    id: number;
    value: string;
    stage: string;
    contact: {
        name: string;
    };
    vehicle: {
        name: string;
    } | null;
    owner: {
        name: string;
    };
};

const props = defineProps<{
    deals: DealRow[];
    stages: StageOption[];
    metrics: {
        pipelineValue: string;
        openDeals: number;
        conversion: number;
        averageDaysToClose: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Pipeline',
                href: index(),
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

function stageLabel(stage: string, stages: StageOption[]): string {
    return stages.find((option) => option.value === stage)?.label ?? stage;
}

function dealsFor(stage: string): DealRow[] {
    return props.deals.filter((deal) => deal.stage === stage);
}

function initials(name: string): string {
    return name
        .split(/\s+/)
        .filter((part) => part !== '')
        .slice(0, 2)
        .map((part) => part.charAt(0).toUpperCase())
        .join('');
}

const metricItems = computed(() => [
    {
        label: 'Pipeline value',
        value: formatPounds(props.metrics.pipelineValue),
        test: 'pipeline-value',
    },
    {
        label: 'Open deals',
        value: String(props.metrics.openDeals),
        test: 'pipeline-open-deals',
    },
    {
        label: 'Conversion',
        value: `${props.metrics.conversion}%`,
        test: 'pipeline-conversion',
    },
    {
        label: 'Avg. days to close',
        value: String(props.metrics.averageDaysToClose),
        test: 'pipeline-average-days',
    },
]);

function changeStage(dealId: number, stage: unknown): void {
    if (typeof stage !== 'string' || stage === '') {
        return;
    }

    router.patch(update.url(dealId), { stage }, { preserveScroll: true });
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <Head title="Pipeline" />

        <Heading title="Pipeline" />

        <div class="flex flex-wrap" data-test="pipeline-metrics">
            <div
                v-for="(metric, index) in metricItems"
                :key="metric.label"
                class="grid gap-1 py-1 pr-8"
                :class="index > 0 ? 'border-l pl-8' : ''"
            >
                <p class="text-sm text-muted-foreground">{{ metric.label }}</p>
                <p
                    class="font-heading text-3xl tabular-nums"
                    :data-test="metric.test"
                >
                    {{ metric.value }}
                </p>
            </div>
        </div>

        <p v-if="deals.length === 0" class="text-sm text-muted-foreground">
            No deals yet.
        </p>

        <div class="grid gap-4 xl:grid-cols-5">
            <section
                v-for="stage in stages"
                :key="stage.value"
                class="flex min-w-0 flex-col gap-3"
            >
                <header class="flex items-center justify-between gap-3 px-1">
                    <span class="text-sm font-medium">{{ stage.label }}</span>
                    <span class="flex items-center gap-2 text-sm tabular-nums">
                        <span
                            class="inline-block size-1.5 rounded-full"
                            :style="{ background: stageColor[stage.value] }"
                        />
                        <span :data-test="`stage-count-${stage.value}`">
                            {{ dealsFor(stage.value).length }}
                        </span>
                    </span>
                </header>

                <Card
                    v-for="deal in dealsFor(stage.value)"
                    :key="deal.id"
                    class="gap-3 py-4 shadow-none"
                >
                    <CardContent class="grid gap-3 px-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <Link
                                    :href="show(deal.id)"
                                    class="truncate font-medium hover:text-primary"
                                >
                                    {{ deal.contact.name }}
                                </Link>
                                <p
                                    class="truncate text-sm text-muted-foreground"
                                >
                                    {{ deal.vehicle?.name ?? 'No vehicle' }}
                                </p>
                            </div>
                            <p class="shrink-0 text-sm tabular-nums">
                                {{ formatPounds(deal.value) }}
                            </p>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-2">
                                <span
                                    class="inline-block size-1.5 shrink-0 rounded-full"
                                    :style="{
                                        background: stageColor[deal.stage],
                                    }"
                                />
                                <Select
                                    :model-value="deal.stage"
                                    @update:model-value="
                                        changeStage(deal.id, $event)
                                    "
                                >
                                    <SelectTrigger
                                        size="sm"
                                        class="h-8 w-auto max-w-full border-transparent bg-transparent px-2 shadow-none"
                                        :data-test="`deal-stage-${deal.id}`"
                                        :aria-label="
                                            stageLabel(deal.stage, stages)
                                        "
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem
                                            v-for="option in stages"
                                            :key="option.value"
                                            :value="option.value"
                                        >
                                            {{ option.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <span class="text-xs text-muted-foreground">
                                {{ initials(deal.owner.name) }}
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </section>
        </div>
    </div>
</template>
