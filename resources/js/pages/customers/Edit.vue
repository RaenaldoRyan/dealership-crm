<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { watchEffect } from 'vue';
import ContactController from '@/actions/App/Customers/Http/Controllers/ContactController';
import { storeForContact as storeVehicle } from '@/actions/App/Inventory/Http/Controllers/VehicleController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatPounds } from '@/lib/utils';
import { edit, index, show } from '@/routes/contacts';

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

const props = defineProps<{
    contact: ContactForm;
    vehicles: VehicleRow[];
}>();

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
            {
                title: 'Edit',
                href: edit(props.contact.id),
            },
        ],
    });
});
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-6">
        <Head :title="contact.name" />

        <header class="space-y-1">
            <h1 class="font-heading text-3xl font-medium tracking-tight">
                {{ contact.name }}
            </h1>
            <p class="text-sm text-muted-foreground">
                {{ contact.email }}
                <span v-if="contact.phone"> · {{ contact.phone }}</span>
            </p>
            <p class="text-sm text-muted-foreground">
                Owner
                <span class="text-foreground">{{ contact.owner.name }}</span>
            </p>
        </header>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card class="shadow-none">
                <CardHeader>
                    <CardTitle class="font-heading text-lg font-medium">
                        Contact
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="ContactController.update.form(contact.id)"
                        class="space-y-6"
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
                </CardContent>
            </Card>

            <Card class="shadow-none">
                <CardHeader>
                    <CardTitle class="font-heading text-lg font-medium">
                        Vehicles
                    </CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4">
                    <p
                        v-if="vehicles.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        No vehicles yet.
                    </p>
                    <ul v-else class="divide-y border-y">
                        <li
                            v-for="vehicle in vehicles"
                            :key="vehicle.id"
                            class="flex items-baseline justify-between gap-4 py-3 text-sm"
                        >
                            <span>
                                {{ vehicle.year }} {{ vehicle.make }}
                                {{ vehicle.model }},
                                {{ vehicle.registration }}
                            </span>
                            <span class="shrink-0 tabular-nums">
                                {{ formatPounds(vehicle.price) }}
                            </span>
                        </li>
                    </ul>

                    <Form
                        v-bind="storeVehicle.form(contact.id)"
                        reset-on-success
                        class="space-y-4 border-t pt-4"
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
                </CardContent>
            </Card>
        </div>
    </div>
</template>
