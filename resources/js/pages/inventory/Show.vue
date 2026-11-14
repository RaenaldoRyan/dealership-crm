<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatPounds } from '@/lib/utils';
import { edit, index } from '@/routes/vehicles';

type VehicleDetail = {
    id: number;
    registration: string;
    make: string;
    model: string;
    year: number;
    colour: string | null;
    fuelLabel: string | null;
    transmissionLabel: string | null;
    mileage: number | null;
    vin: string | null;
    price: string;
    status: string;
    statusLabel: string;
    customer: string | null;
    deal: { id: number; label: string } | null;
};

const props = defineProps<{
    vehicle: VehicleDetail;
    canUpdate: boolean;
}>();

const statusDot: Record<string, string> = {
    in_stock: 'var(--stage-enquiry)',
    reserved: 'var(--stage-negotiation)',
    sold: 'var(--stage-sold)',
};

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Inventory',
                href: index(),
            },
            {
                title: props.vehicle.registration,
                href: index(),
            },
        ],
    });
});

function formatMileage(value: number | null): string {
    if (value === null) {
        return '—';
    }

    return `${new Intl.NumberFormat('en-GB').format(value)} mi`;
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <Head :title="vehicle.registration" />

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="space-y-3">
                <Heading
                    :title="`${vehicle.year} ${vehicle.make} ${vehicle.model}`"
                    :description="vehicle.customer ?? 'Stock'"
                />
                <span
                    class="inline-flex rounded-md bg-chart-3 px-3 py-1.5 font-heading text-xl font-semibold tracking-[0.18em] text-foreground dark:bg-foreground dark:text-background"
                    data-test="vehicle-plate"
                >
                    {{ vehicle.registration }}
                </span>
            </div>
            <Button
                v-if="canUpdate"
                variant="outline"
                class="rounded-full"
                as-child
            >
                <Link :href="edit(vehicle.id)" data-test="edit-vehicle">
                    Edit
                </Link>
            </Button>
        </div>

        <Card class="max-w-3xl">
            <CardHeader>
                <CardTitle>Vehicle details</CardTitle>
            </CardHeader>
            <CardContent>
                <dl class="grid gap-4 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="text-muted-foreground">Status</dt>
                        <dd class="mt-1 flex items-center gap-2">
                            <span
                                class="size-2 rounded-full"
                                :style="{
                                    backgroundColor: statusDot[vehicle.status],
                                }"
                            />
                            {{ vehicle.statusLabel }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Price</dt>
                        <dd class="mt-1 tabular-nums">
                            {{ formatPounds(vehicle.price) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Fuel type</dt>
                        <dd class="mt-1">{{ vehicle.fuelLabel ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Transmission</dt>
                        <dd class="mt-1">
                            {{ vehicle.transmissionLabel ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Mileage</dt>
                        <dd class="mt-1">
                            {{ formatMileage(vehicle.mileage) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Colour</dt>
                        <dd class="mt-1">{{ vehicle.colour ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">VIN</dt>
                        <dd class="mt-1">{{ vehicle.vin ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Linked deal</dt>
                        <dd class="mt-1">{{ vehicle.deal?.label ?? '—' }}</dd>
                    </div>
                </dl>
            </CardContent>
        </Card>
    </div>
</template>
