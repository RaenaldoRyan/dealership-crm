<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import NotesCard from '@/components/NotesCard.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatPounds } from '@/lib/utils';
import { edit, index, show } from '@/routes/contacts';
import { store as storeContactNote } from '@/routes/contacts/notes';
import { create as createDeal, show as showDeal } from '@/routes/deals';
import {
    create as createVehicle,
    show as showVehicle,
} from '@/routes/vehicles';

type VehicleRow = {
    id: number;
    year: number;
    make: string;
    model: string;
    registration: string;
    price: string;
};

type DealRow = {
    id: number;
    stage: string;
    stageLabel: string;
    vehicle: string | null;
    value: string;
};

type NoteRow = {
    id: number;
    body: string;
    author: string;
    time: string;
};

type ActivityRow = {
    id: number;
    description: string;
    time: string;
};

type ContactDetail = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    owner: {
        name: string;
    };
    vehicles: VehicleRow[];
    deals: DealRow[];
    notes: NoteRow[];
    activity: ActivityRow[];
};

const props = defineProps<{
    contact: ContactDetail;
}>();

const stageColor: Record<string, string> = {
    enquiry: 'var(--stage-enquiry)',
    test_drive: 'var(--stage-test-drive)',
    negotiation: 'var(--stage-negotiation)',
    sold: 'var(--stage-sold)',
    lost: 'var(--stage-lost)',
};

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Contacts',
                href: index(),
            },
            {
                title: props.contact.name,
                href: show(props.contact.id),
            },
        ],
    });
});

function dealSummary(deal: DealRow): string {
    const parts = [deal.stageLabel];

    if (deal.vehicle !== null) {
        parts.push(deal.vehicle);
    }

    parts.push(formatPounds(deal.value));

    return parts.join(', ');
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <Head :title="contact.name" />

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-1">
                <h1
                    class="font-heading text-3xl font-medium tracking-tight"
                    data-test="contact-name"
                >
                    {{ contact.name }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ contact.email }}
                    <template v-if="contact.phone">
                        | {{ contact.phone }}
                    </template>
                </p>
                <p class="text-sm text-muted-foreground">
                    Owner: {{ contact.owner.name }}
                </p>
            </div>

            <div class="flex items-center gap-2">
                <Button variant="ghost" class="rounded-full" as-child>
                    <Link :href="edit(contact.id)" data-test="edit-contact">
                        Edit
                    </Link>
                </Button>
                <Button as-child>
                    <Link
                        :href="
                            createDeal.url({ query: { contact: contact.id } })
                        "
                        data-test="new-deal"
                    >
                        New deal
                    </Link>
                </Button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Vehicles</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <p
                        v-if="contact.vehicles.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        No vehicles yet.
                    </p>
                    <ul v-else class="divide-y text-sm">
                        <li
                            v-for="vehicle in contact.vehicles"
                            :key="vehicle.id"
                            class="py-3 first:pt-0"
                        >
                            <Link
                                :href="showVehicle(vehicle.id)"
                                class="hover:text-primary"
                            >
                                {{ vehicle.year }} {{ vehicle.make }}
                                {{ vehicle.model }}, {{ vehicle.registration }},
                                {{ formatPounds(vehicle.price) }}
                            </Link>
                        </li>
                    </ul>
                    <Link
                        :href="
                            createVehicle.url({
                                query: { contact: contact.id },
                            })
                        "
                        class="inline-flex text-sm font-medium text-primary"
                        data-test="add-vehicle"
                    >
                        Add vehicle
                    </Link>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Deals</CardTitle>
                </CardHeader>
                <CardContent>
                    <p
                        v-if="contact.deals.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        No deals yet.
                    </p>
                    <ul v-else class="divide-y text-sm">
                        <li
                            v-for="deal in contact.deals"
                            :key="deal.id"
                            class="flex items-center gap-3 py-3 first:pt-0"
                        >
                            <span
                                class="size-2 shrink-0 rounded-full"
                                :style="{
                                    backgroundColor: stageColor[deal.stage],
                                }"
                            />
                            <Link
                                :href="showDeal(deal.id)"
                                class="hover:text-primary"
                            >
                                {{ dealSummary(deal) }}
                            </Link>
                        </li>
                    </ul>
                </CardContent>
            </Card>
        </div>

        <NotesCard
            :notes="contact.notes"
            :action="storeContactNote.url(contact.id)"
        />

        <Card>
            <CardHeader>
                <CardTitle>Activity</CardTitle>
            </CardHeader>
            <CardContent>
                <p
                    v-if="contact.activity.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    No activity yet.
                </p>
                <ul v-else class="divide-y text-sm" data-test="activity">
                    <li
                        v-for="entry in contact.activity"
                        :key="entry.id"
                        class="grid grid-cols-[7rem_1fr] gap-4 py-3 first:pt-0"
                    >
                        <span class="text-muted-foreground">{{
                            entry.time
                        }}</span>
                        <span>{{ entry.description }}</span>
                    </li>
                </ul>
            </CardContent>
        </Card>
    </div>
</template>
