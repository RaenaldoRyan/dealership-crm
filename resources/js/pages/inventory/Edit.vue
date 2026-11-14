<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import Heading from '@/components/Heading.vue';
import VehicleForm from '@/pages/inventory/VehicleForm.vue';
import { index, show } from '@/routes/vehicles';

type Option = {
    value: string;
    label: string;
};

type ContactOption = {
    id: number;
    name: string;
};

type VehicleFormValues = {
    id: number;
    registration: string;
    make: string;
    model: string;
    year: number;
    colour: string | null;
    fuelType: string | null;
    transmission: string | null;
    mileage: number | null;
    vin: string | null;
    price: string;
    contactId: number | null;
};

const props = defineProps<{
    vehicle: VehicleFormValues;
    contacts: ContactOption[];
    fuels: Option[];
    transmissions: Option[];
}>();

watchEffect(() => {
    setLayoutProps({
        breadcrumbs: [
            {
                title: 'Inventory',
                href: index(),
            },
            {
                title: props.vehicle.registration,
                href: show(props.vehicle.id),
            },
        ],
    });
});
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <Head :title="`Edit ${vehicle.registration}`" />

        <Heading title="Edit vehicle" />

        <VehicleForm
            :vehicle="vehicle"
            :contacts="contacts"
            :fuels="fuels"
            :transmissions="transmissions"
            submit-label="Save vehicle"
            :cancel-href="show(vehicle.id)"
        />
    </div>
</template>
