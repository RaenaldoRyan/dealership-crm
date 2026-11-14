<script setup lang="ts">
import { Head, Link, router, setLayoutProps } from '@inertiajs/vue3';
import { computed, watchEffect } from 'vue';
import NotesCard from '@/components/NotesCard.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatPounds } from '@/lib/utils';
import { show as showContact } from '@/routes/contacts';
import { index, show, sold, update } from '@/routes/deals';
import { store as storeDealNote } from '@/routes/deals/notes';
import { show as showVehicle } from '@/routes/vehicles';

type StageOption = {
    value: string;
    label: string;
};

type DealDetail = {
    id: number;
    value: string;
    stage: string;
    stageLabel: string;
    contact: {
        id: number;
        name: string;
        email: string;
        phone: string | null;
        owner: {
            name: string;
        };
    };
    vehicle: {
        id: number;
        make: string;
        model: string;
        year: number;
        registration: string;
        price: string;
    } | null;
    notes: {
        id: number;
        body: string;
        author: string;
        time: string;
    }[];
};

const props = defineProps<{
    deal: DealDetail;
    stages: StageOption[];
}>();

const stageColor: Record<string, string> = {
    enquiry: 'var(--stage-enquiry)',
    test_drive: 'var(--stage-test-drive)',
    negotiation: 'var(--stage-negotiation)',
    sold: 'var(--stage-sold)',
    lost: 'var(--stage-lost)',
};

const currentIndex = computed(() =>
    props.stages.findIndex((stage) => stage.value === props.deal.stage),
);

const title = computed(() => {
    const vehicle = props.deal.vehicle;

    if (vehicle === null) {
        return props.deal.contact.name;
    }

    return `${props.deal.contact.name}, ${vehicle.year} ${vehicle.make} ${vehicle.model}`;
});

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Pipeline',
                href: index(),
            },
            {
                title: `Deal #${props.deal.id}`,
                href: show(props.deal.id),
            },
        ],
    });
});

function changeStage(stage: unknown): void {
    if (typeof stage !== 'string' || stage === props.deal.stage) {
        return;
    }

    router.patch(
        update.url(props.deal.id),
        { stage },
        { preserveScroll: true },
    );
}

function markSold(): void {
    router.post(sold.url(props.deal.id));
}

function connectorClass(filled: boolean): string {
    if (!filled) {
        return 'bg-border';
    }

    return props.deal.stage === 'lost' ? 'bg-(--stage-lost)' : 'bg-primary';
}

function stepLabelClass(index: number): string {
    if (index === currentIndex.value && props.deal.stage === 'lost') {
        return 'font-medium text-foreground';
    }

    if (index === currentIndex.value) {
        return 'font-medium text-primary';
    }

    if (index < currentIndex.value) {
        return 'text-foreground';
    }

    return 'text-muted-foreground';
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <Head :title="title" />

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-2">
                <h1
                    class="font-heading text-3xl font-medium tracking-tight"
                    data-test="deal-title"
                >
                    {{ title }}
                </h1>
                <p
                    class="font-heading text-3xl font-medium tabular-nums"
                    data-test="deal-value"
                >
                    {{ formatPounds(deal.value) }}
                </p>
            </div>

            <Select :model-value="deal.stage" @update:model-value="changeStage">
                <SelectTrigger class="w-44" data-test="deal-stage">
                    <span
                        class="size-2 shrink-0 rounded-full"
                        :style="{ backgroundColor: stageColor[deal.stage] }"
                    />
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="option in stages"
                        :key="option.value"
                        :value="option.value"
                    >
                        <span
                            class="inline-block size-2 shrink-0 rounded-full"
                            :style="{
                                backgroundColor: stageColor[option.value],
                            }"
                        />
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <ol class="flex">
            <li
                v-for="(stage, index) in stages"
                :key="stage.value"
                class="relative flex flex-1 flex-col items-center gap-2"
            >
                <span
                    v-if="index > 0"
                    class="absolute top-1.5 left-0 h-0.5 w-1/2"
                    :class="connectorClass(index <= currentIndex)"
                />
                <span
                    v-if="index < stages.length - 1"
                    class="absolute top-1.5 left-1/2 h-0.5 w-1/2"
                    :class="connectorClass(index < currentIndex)"
                />
                <span
                    class="relative z-10 size-3 rounded-full border-2"
                    :class="
                        index <= currentIndex
                            ? deal.stage === 'lost'
                                ? 'border-(--stage-lost) bg-(--stage-lost)'
                                : 'border-primary bg-primary'
                            : 'border-border bg-background'
                    "
                />
                <span
                    class="text-center text-xs"
                    :class="stepLabelClass(index)"
                >
                    {{ stage.label }}
                </span>
            </li>
        </ol>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Customer</CardTitle>
                </CardHeader>
                <CardContent>
                    <dl class="grid gap-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Name</dt>
                            <dd>
                                <Link
                                    :href="showContact(deal.contact.id)"
                                    class="hover:text-primary"
                                >
                                    {{ deal.contact.name }}
                                </Link>
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Email</dt>
                            <dd>{{ deal.contact.email }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Phone</dt>
                            <dd>{{ deal.contact.phone ?? '—' }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Owner</dt>
                            <dd>{{ deal.contact.owner.name }}</dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Vehicle</CardTitle>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="deal.vehicle === null"
                        class="text-sm text-muted-foreground"
                    >
                        No vehicle on this deal.
                    </p>
                    <dl v-else class="grid gap-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Make</dt>
                            <dd>{{ deal.vehicle.make }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Model</dt>
                            <dd>{{ deal.vehicle.model }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Year</dt>
                            <dd>{{ deal.vehicle.year }}</dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Registration</dt>
                            <dd>
                                <Link
                                    :href="showVehicle(deal.vehicle.id)"
                                    class="hover:text-primary"
                                >
                                    {{ deal.vehicle.registration }}
                                </Link>
                            </dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-muted-foreground">Price</dt>
                            <dd>{{ formatPounds(deal.vehicle.price) }}</dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>
        </div>

        <NotesCard :notes="deal.notes" :action="storeDealNote.url(deal.id)" />

        <div v-if="deal.stage !== 'sold'" class="flex justify-end">
            <Button type="button" data-test="mark-sold" @click="markSold">
                Mark as sold
            </Button>
        </div>
    </div>
</template>
