<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import DealController from '@/actions/App/Sales/Http/Controllers/DealController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create, index } from '@/routes/deals';

type StageOption = {
    value: string;
    label: string;
};

type VehicleOption = {
    id: number;
    label: string;
};

type ContactOption = {
    id: number;
    name: string;
    vehicles: VehicleOption[];
};

const props = defineProps<{
    contacts: ContactOption[];
    stages: StageOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Deals',
                href: index(),
            },
            {
                title: 'New deal',
                href: create(),
            },
        ],
    },
});

const contactId = ref('');
const vehicleId = ref('');

const vehicles = computed(() => {
    return (
        props.contacts.find((contact) => String(contact.id) === contactId.value)
            ?.vehicles ?? []
    );
});

watch(contactId, () => {
    vehicleId.value = '';
});

const fieldClass =
    'border-input placeholder:text-muted-foreground dark:bg-input/30 h-9 w-full rounded-md border bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Head title="New deal" />

        <Heading title="New deal" description="This deal will belong to you" />

        <p v-if="contacts.length === 0" class="text-sm text-muted-foreground">
            Add a contact before opening a deal.
        </p>

        <Form
            v-else
            v-bind="DealController.store.form()"
            class="max-w-xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="contact_id">Contact</Label>
                <select
                    id="contact_id"
                    v-model="contactId"
                    name="contact_id"
                    required
                    :class="fieldClass"
                    data-test="deal-contact"
                >
                    <option value="" disabled>Select a contact</option>
                    <option
                        v-for="contact in contacts"
                        :key="contact.id"
                        :value="String(contact.id)"
                    >
                        {{ contact.name }}
                    </option>
                </select>
                <InputError :message="errors.contact_id" />
            </div>

            <div class="grid gap-2">
                <Label for="vehicle_id">Vehicle</Label>
                <select
                    id="vehicle_id"
                    v-model="vehicleId"
                    name="vehicle_id"
                    :class="fieldClass"
                    data-test="deal-vehicle"
                >
                    <option value="">No vehicle</option>
                    <option
                        v-for="vehicle in vehicles"
                        :key="vehicle.id"
                        :value="String(vehicle.id)"
                    >
                        {{ vehicle.label }}
                    </option>
                </select>
                <InputError :message="errors.vehicle_id" />
            </div>

            <div class="grid gap-2">
                <Label for="value">Value</Label>
                <Input
                    id="value"
                    name="value"
                    type="number"
                    min="0"
                    step="0.01"
                    required
                    data-test="deal-value"
                />
                <InputError :message="errors.value" />
            </div>

            <div class="grid gap-2">
                <Label for="stage">Stage</Label>
                <select
                    id="stage"
                    name="stage"
                    required
                    :class="fieldClass"
                    data-test="deal-stage"
                >
                    <option
                        v-for="stage in stages"
                        :key="stage.value"
                        :value="stage.value"
                    >
                        {{ stage.label }}
                    </option>
                </select>
                <InputError :message="errors.stage" />
            </div>

            <Button type="submit" :disabled="processing" data-test="save-deal">
                Save deal
            </Button>
        </Form>
    </div>
</template>
