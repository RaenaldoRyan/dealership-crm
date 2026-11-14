<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import DealController from '@/actions/App/Sales/Http/Controllers/DealController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
    contactId: number | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Pipeline',
                href: index(),
            },
            {
                title: 'New deal',
                href: create(),
            },
        ],
    },
});

const contactId = ref<string | undefined>(
    props.contactId === null ? undefined : String(props.contactId),
);
const vehicleId = ref('');
const stage = ref(props.stages[0]?.value ?? '');

const vehicles = computed(() => {
    return (
        props.contacts.find((contact) => String(contact.id) === contactId.value)
            ?.vehicles ?? []
    );
});

watch(contactId, () => {
    vehicleId.value = '';
});

function chooseVehicle(value: unknown): void {
    if (value === 'none' || typeof value !== 'string') {
        vehicleId.value = '';

        return;
    }

    vehicleId.value = value;
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <Head title="New deal" />

        <Heading title="New deal" />

        <p v-if="contacts.length === 0" class="text-sm text-muted-foreground">
            Add a contact before opening a deal.
        </p>

        <Form
            v-else
            v-bind="DealController.store.form()"
            class="max-w-xl space-y-6 rounded-xl border bg-card p-6 shadow-none"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="contact_id">Contact</Label>
                <Select v-model="contactId">
                    <SelectTrigger
                        id="contact_id"
                        class="w-full shadow-none"
                        data-test="deal-contact"
                    >
                        <SelectValue placeholder="Select a contact" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="contact in contacts"
                            :key="contact.id"
                            :value="String(contact.id)"
                        >
                            {{ contact.name }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <input
                    type="hidden"
                    name="contact_id"
                    :value="contactId ?? ''"
                />
                <InputError :message="errors.contact_id" />
            </div>

            <div class="grid gap-2">
                <Label for="vehicle_id">Vehicle</Label>
                <Select
                    :model-value="vehicleId === '' ? 'none' : vehicleId"
                    @update:model-value="chooseVehicle"
                >
                    <SelectTrigger
                        id="vehicle_id"
                        class="w-full shadow-none"
                        data-test="deal-vehicle"
                    >
                        <SelectValue placeholder="No vehicle" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="none">No vehicle</SelectItem>
                        <SelectItem
                            v-for="vehicle in vehicles"
                            :key="vehicle.id"
                            :value="String(vehicle.id)"
                        >
                            {{ vehicle.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <input type="hidden" name="vehicle_id" :value="vehicleId" />
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
                <Select v-model="stage">
                    <SelectTrigger
                        id="stage"
                        class="w-full shadow-none"
                        data-test="deal-stage"
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
                <input type="hidden" name="stage" :value="stage" />
                <InputError :message="errors.stage" />
            </div>

            <Button type="submit" :disabled="processing" data-test="save-deal">
                Save deal
            </Button>
        </Form>
    </div>
</template>
