<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    create,
    index,
    update,
} from '@/actions/App/Sales/Http/Controllers/DealController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

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

defineProps<{
    deals: DealRow[];
    stages: StageOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Deals',
                href: index(),
            },
        ],
    },
});

const stageClasses: Record<string, string> = {
    enquiry:
        'border-transparent bg-sky-100 text-sky-800 dark:bg-sky-950 dark:text-sky-200',
    test_drive:
        'border-transparent bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
    negotiation:
        'border-transparent bg-violet-100 text-violet-900 dark:bg-violet-950 dark:text-violet-200',
    sold: 'border-transparent bg-emerald-100 text-emerald-900 dark:bg-emerald-950 dark:text-emerald-200',
    lost: 'border-transparent bg-rose-100 text-rose-900 dark:bg-rose-950 dark:text-rose-200',
};

function stageLabel(stage: string, stages: StageOption[]): string {
    return stages.find((option) => option.value === stage)?.label ?? stage;
}

function formatValue(value: string): string {
    return Number(value).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

function changeStage(dealId: number, event: Event): void {
    const stage = (event.target as HTMLSelectElement).value;

    router.patch(update.url(dealId), { stage }, { preserveScroll: true });
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Head title="Deals" />

        <div class="flex items-center justify-between gap-4">
            <Heading
                title="Deals"
                description="Opportunities your team is working"
            />

            <Button as-child>
                <Link :href="create()" data-test="create-deal">New deal</Link>
            </Button>
        </div>

        <div
            class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <table class="w-full text-sm">
                <thead
                    class="border-b border-sidebar-border/70 text-left text-muted-foreground dark:border-sidebar-border"
                >
                    <tr>
                        <th class="px-4 py-3 font-medium">Contact</th>
                        <th class="px-4 py-3 font-medium">Vehicle</th>
                        <th class="px-4 py-3 font-medium">Owner</th>
                        <th class="px-4 py-3 font-medium">Value</th>
                        <th class="px-4 py-3 font-medium">Stage</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="deals.length === 0">
                        <td
                            colspan="5"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No deals yet.
                        </td>
                    </tr>
                    <tr
                        v-for="deal in deals"
                        :key="deal.id"
                        class="border-b border-sidebar-border/70 last:border-0 dark:border-sidebar-border"
                    >
                        <td class="px-4 py-3">{{ deal.contact.name }}</td>
                        <td class="px-4 py-3">
                            {{ deal.vehicle?.name ?? '—' }}
                        </td>
                        <td class="px-4 py-3">{{ deal.owner.name }}</td>
                        <td class="px-4 py-3">{{ formatValue(deal.value) }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <Badge
                                    variant="outline"
                                    :class="stageClasses[deal.stage]"
                                >
                                    {{ stageLabel(deal.stage, stages) }}
                                </Badge>
                                <select
                                    class="h-9 rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                                    :value="deal.stage"
                                    :data-test="`deal-stage-${deal.id}`"
                                    @change="changeStage(deal.id, $event)"
                                >
                                    <option
                                        v-for="stage in stages"
                                        :key="stage.value"
                                        :value="stage.value"
                                    >
                                        {{ stage.label }}
                                    </option>
                                </select>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
