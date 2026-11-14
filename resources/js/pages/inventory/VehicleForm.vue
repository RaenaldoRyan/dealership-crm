<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { Bell } from '@lucide/vue';
import { computed, ref } from 'vue';
import VehicleController from '@/actions/App/Inventory/Http/Controllers/VehicleController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { BreadcrumbItem } from '@/types';

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
    make: string;
    model: string;
    year: number;
    colour: string | null;
    fuelType: string | null;
    transmission: string | null;
    mileage: number | null;
    vin: string | null;
    registration: string;
    price: string;
    contactId: number | null;
};

const props = defineProps<{
    vehicle?: VehicleFormValues | null;
    contacts: ContactOption[];
    fuels: Option[];
    transmissions: Option[];
    contactId?: number | null;
    submitLabel: string;
    cancelHref: BreadcrumbItem['href'];
}>();

const fuel = ref(props.vehicle?.fuelType ?? 'none');
const transmission = ref(props.vehicle?.transmission ?? 'none');
const contact = ref(
    props.vehicle?.contactId
        ? String(props.vehicle.contactId)
        : props.contactId
          ? String(props.contactId)
          : 'none',
);

const binding = computed(() =>
    props.vehicle
        ? VehicleController.update.form(props.vehicle.id)
        : VehicleController.store.form(),
);

function mileageDefault(mileage: number | null | undefined): string {
    if (mileage === null || mileage === undefined) {
        return '';
    }

    return new Intl.NumberFormat('en-GB').format(mileage);
}
</script>

<template>
    <Form
        v-bind="binding"
        class="mx-auto flex w-full max-w-5xl flex-col gap-6"
        v-slot="{ errors, processing }"
    >
        <Card>
            <CardHeader>
                <CardTitle>Registration lookup</CardTitle>
            </CardHeader>
            <CardContent class="space-y-3">
                <div class="flex flex-wrap items-center gap-4">
                    <Input
                        id="registration"
                        name="registration"
                        required
                        autocapitalize="characters"
                        autocomplete="off"
                        :default-value="vehicle?.registration ?? ''"
                        class="h-14 w-full max-w-56 bg-chart-3 text-center font-heading text-2xl font-semibold tracking-[0.18em] text-foreground uppercase shadow-none dark:bg-foreground dark:text-background"
                        data-test="inventory-registration"
                    />
                    <!-- TODO: DVLA VES lookup via LookupVehicleByRegistration -->
                    <Tooltip>
                        <TooltipTrigger as-child>
                            <span tabindex="0" class="inline-flex rounded-md">
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled
                                    class="pointer-events-none"
                                    data-test="registration-lookup"
                                >
                                    <Bell />
                                    Look up
                                </Button>
                            </span>
                        </TooltipTrigger>
                        <TooltipContent>DVLA lookup coming soon</TooltipContent>
                    </Tooltip>
                </div>
                <p class="text-sm text-muted-foreground">
                    We'll fill in the details from the DVLA
                </p>
                <InputError :message="errors.registration" />
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Vehicle details</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid gap-x-10 gap-y-4 md:grid-cols-2">
                    <div
                        class="grid grid-cols-[7.5rem_minmax(0,1fr)] items-start gap-3"
                    >
                        <Label for="make" class="pt-2">Make</Label>
                        <div class="grid gap-2">
                            <Input
                                id="make"
                                name="make"
                                required
                                :default-value="vehicle?.make"
                                data-test="inventory-make"
                            />
                            <InputError :message="errors.make" />
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-[7.5rem_minmax(0,1fr)] items-start gap-3"
                    >
                        <Label for="fuel_type" class="pt-2">Fuel type</Label>
                        <div class="grid gap-2">
                            <Select v-model="fuel">
                                <SelectTrigger
                                    id="fuel_type"
                                    class="w-full shadow-none"
                                    data-test="inventory-fuel"
                                >
                                    <SelectValue placeholder="Fuel type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">
                                        Not set
                                    </SelectItem>
                                    <SelectItem
                                        v-for="option in fuels"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <input
                                type="hidden"
                                name="fuel_type"
                                :value="fuel === 'none' ? '' : fuel"
                            />
                            <InputError :message="errors.fuel_type" />
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-[7.5rem_minmax(0,1fr)] items-start gap-3"
                    >
                        <Label for="model" class="pt-2">Model</Label>
                        <div class="grid gap-2">
                            <Input
                                id="model"
                                name="model"
                                required
                                :default-value="vehicle?.model"
                                data-test="inventory-model"
                            />
                            <InputError :message="errors.model" />
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-[7.5rem_minmax(0,1fr)] items-start gap-3"
                    >
                        <Label for="transmission" class="pt-2"
                            >Transmission</Label
                        >
                        <div class="grid gap-2">
                            <Select v-model="transmission">
                                <SelectTrigger
                                    id="transmission"
                                    class="w-full shadow-none"
                                    data-test="inventory-transmission"
                                >
                                    <SelectValue placeholder="Transmission" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">
                                        Not set
                                    </SelectItem>
                                    <SelectItem
                                        v-for="option in transmissions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <input
                                type="hidden"
                                name="transmission"
                                :value="
                                    transmission === 'none' ? '' : transmission
                                "
                            />
                            <InputError :message="errors.transmission" />
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-[7.5rem_minmax(0,1fr)] items-start gap-3"
                    >
                        <Label for="year" class="pt-2">Year</Label>
                        <div class="grid gap-2">
                            <Input
                                id="year"
                                name="year"
                                type="number"
                                min="1900"
                                required
                                :default-value="vehicle?.year"
                                data-test="inventory-year"
                            />
                            <InputError :message="errors.year" />
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-[7.5rem_minmax(0,1fr)] items-start gap-3"
                    >
                        <Label for="mileage" class="pt-2">Mileage</Label>
                        <div class="grid gap-2">
                            <Input
                                id="mileage"
                                name="mileage"
                                inputmode="numeric"
                                :default-value="
                                    mileageDefault(vehicle?.mileage)
                                "
                                data-test="inventory-mileage"
                            />
                            <InputError :message="errors.mileage" />
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-[7.5rem_minmax(0,1fr)] items-start gap-3"
                    >
                        <Label for="colour" class="pt-2">Colour</Label>
                        <div class="grid gap-2">
                            <Input
                                id="colour"
                                name="colour"
                                :default-value="vehicle?.colour ?? ''"
                                data-test="inventory-colour"
                            />
                            <InputError :message="errors.colour" />
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-[7.5rem_minmax(0,1fr)] items-start gap-3"
                    >
                        <Label for="vin" class="pt-2">VIN</Label>
                        <div class="grid gap-2">
                            <Input
                                id="vin"
                                name="vin"
                                maxlength="17"
                                autocapitalize="characters"
                                :default-value="vehicle?.vin ?? ''"
                                data-test="inventory-vin"
                            />
                            <InputError :message="errors.vin" />
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-[7.5rem_minmax(0,1fr)] items-start gap-3"
                    >
                        <Label for="contact_id" class="pt-2">Customer</Label>
                        <div class="grid gap-2">
                            <Select v-model="contact">
                                <SelectTrigger
                                    id="contact_id"
                                    class="w-full shadow-none"
                                    data-test="inventory-contact"
                                >
                                    <SelectValue
                                        placeholder="Customer (optional)"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">
                                        Customer (optional)
                                    </SelectItem>
                                    <SelectItem
                                        v-for="option in contacts"
                                        :key="option.id"
                                        :value="String(option.id)"
                                    >
                                        {{ option.name }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <input
                                type="hidden"
                                name="contact_id"
                                :value="contact === 'none' ? '' : contact"
                            />
                            <InputError :message="errors.contact_id" />
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-[7.5rem_minmax(0,1fr)] items-start gap-3"
                    >
                        <Label for="price" class="pt-2">Price</Label>
                        <div class="grid gap-2">
                            <div class="relative">
                                <span
                                    class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm text-muted-foreground"
                                    >£</span
                                >
                                <Input
                                    id="price"
                                    name="price"
                                    inputmode="decimal"
                                    required
                                    class="pl-7"
                                    :default-value="vehicle?.price"
                                    data-test="inventory-price"
                                />
                            </div>
                            <InputError :message="errors.price" />
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div class="flex justify-end gap-2">
            <Button variant="outline" class="rounded-full" as-child>
                <Link :href="cancelHref">Cancel</Link>
            </Button>
            <Button
                type="submit"
                :disabled="processing"
                data-test="save-vehicle"
            >
                {{ submitLabel }}
            </Button>
        </div>
    </Form>
</template>
