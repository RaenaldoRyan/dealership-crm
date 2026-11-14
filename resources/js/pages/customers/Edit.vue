<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import ContactController from '@/actions/App/Customers/Http/Controllers/ContactController';
import { store as storeVehicle } from '@/actions/App/Inventory/Http/Controllers/VehicleController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/contacts';

type ContactForm = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    owner: {
        name: string;
    };
};

type VehicleRow = {
    id: number;
    make: string;
    model: string;
    year: number;
    registration: string;
    price: string;
};

defineProps<{
    contact: ContactForm;
    vehicles: VehicleRow[];
}>();

function formatPrice(price: string): string {
    return Number(price).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Contacts',
                href: index(),
            },
            {
                title: 'Edit contact',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Head title="Edit contact" />

        <Heading
            title="Edit contact"
            :description="`Owned by ${contact.owner.name}`"
        />

        <p class="text-sm text-muted-foreground">
            Owner
            <span class="text-foreground">{{ contact.owner.name }}</span>
        </p>

        <Form
            v-bind="ContactController.update.form(contact.id)"
            class="max-w-xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    name="name"
                    :default-value="contact.name"
                    required
                    autocomplete="name"
                    data-test="contact-name"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    :default-value="contact.email"
                    required
                    autocomplete="email"
                    data-test="contact-email"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="phone">Phone</Label>
                <Input
                    id="phone"
                    type="tel"
                    name="phone"
                    :default-value="contact.phone ?? ''"
                    autocomplete="tel"
                    data-test="contact-phone"
                />
                <InputError :message="errors.phone" />
            </div>

            <Button
                type="submit"
                :disabled="processing"
                data-test="save-contact"
            >
                Save contact
            </Button>
        </Form>

        <section class="flex flex-col gap-4">
            <Heading
                title="Vehicles"
                description="Cars recorded for this contact"
            />

            <div
                class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-sidebar-border/70 text-left text-muted-foreground dark:border-sidebar-border"
                    >
                        <tr>
                            <th class="px-4 py-3 font-medium">Make</th>
                            <th class="px-4 py-3 font-medium">Model</th>
                            <th class="px-4 py-3 font-medium">Year</th>
                            <th class="px-4 py-3 font-medium">Registration</th>
                            <th class="px-4 py-3 font-medium">Price</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="vehicles.length === 0">
                            <td
                                colspan="5"
                                class="px-4 py-8 text-center text-muted-foreground"
                            >
                                No vehicles yet.
                            </td>
                        </tr>
                        <tr
                            v-for="vehicle in vehicles"
                            :key="vehicle.id"
                            class="border-b border-sidebar-border/70 last:border-0 dark:border-sidebar-border"
                        >
                            <td class="px-4 py-3">{{ vehicle.make }}</td>
                            <td class="px-4 py-3">{{ vehicle.model }}</td>
                            <td class="px-4 py-3">{{ vehicle.year }}</td>
                            <td class="px-4 py-3">
                                {{ vehicle.registration }}
                            </td>
                            <td class="px-4 py-3">
                                {{ formatPrice(vehicle.price) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Form
                v-bind="storeVehicle.form(contact.id)"
                reset-on-success
                class="max-w-xl space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="make">Make</Label>
                        <Input
                            id="make"
                            name="make"
                            required
                            data-test="vehicle-make"
                        />
                        <InputError :message="errors.make" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="model">Model</Label>
                        <Input
                            id="model"
                            name="model"
                            required
                            data-test="vehicle-model"
                        />
                        <InputError :message="errors.model" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="year">Year</Label>
                        <Input
                            id="year"
                            name="year"
                            type="number"
                            min="1900"
                            required
                            data-test="vehicle-year"
                        />
                        <InputError :message="errors.year" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="registration">Registration</Label>
                        <Input
                            id="registration"
                            name="registration"
                            required
                            data-test="vehicle-registration"
                        />
                        <InputError :message="errors.registration" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="price">Price</Label>
                        <Input
                            id="price"
                            name="price"
                            type="number"
                            min="0"
                            step="0.01"
                            required
                            data-test="vehicle-price"
                        />
                        <InputError :message="errors.price" />
                    </div>
                </div>

                <Button
                    type="submit"
                    :disabled="processing"
                    data-test="add-vehicle"
                >
                    Add vehicle
                </Button>
            </Form>
        </section>
    </div>
</template>
