<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import VehicleForm from '@/pages/inventory/VehicleForm.vue';
import Heading from '@/components/Heading.vue';
import { create, index } from '@/routes/vehicles';

type Option = {
    value: string;
    label: string;
};

type ContactOption = {
    id: number;
    name: string;
};

defineProps<{
    contacts: ContactOption[];
    fuels: Option[];
    transmissions: Option[];
    contactId: number | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Inventory',
                href: index(),
            },
            {
                title: 'Add vehicle',
                href: create(),
            },
        ],
    },
});
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <Head title="Add vehicle" />

        <Heading title="Add vehicle" />

        <VehicleForm
            :contacts="contacts"
            :fuels="fuels"
            :transmissions="transmissions"
            :contact-id="contactId"
            submit-label="Save vehicle"
            :cancel-href="index()"
        />
    </div>
</template>
