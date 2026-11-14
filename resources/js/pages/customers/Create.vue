<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import ContactController from '@/actions/App/Customers/Http/Controllers/ContactController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create, index } from '@/routes/contacts';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Contacts',
                href: index(),
            },
            {
                title: 'New contact',
                href: create(),
            },
        ],
    },
});

const page = usePage();
const ownerName = computed(() => page.props.auth.user.name);
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Head title="New contact" />

        <Heading
            title="New contact"
            description="This contact will belong to you"
        />

        <p class="text-sm text-muted-foreground">
            Owner
            <span class="text-foreground">{{ ownerName }}</span>
        </p>

        <Form
            v-bind="ContactController.store.form()"
            class="max-w-xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    name="name"
                    required
                    autocomplete="name"
                    placeholder="Full name"
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
                    required
                    autocomplete="email"
                    placeholder="Email address"
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
                    autocomplete="tel"
                    placeholder="Phone number"
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
    </div>
</template>
