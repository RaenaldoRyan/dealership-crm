<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import UserController from '@/actions/App/Core/Http/Controllers/UserController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/users';

type RoleOption = {
    value: string;
    label: string;
};

type UserForm = {
    id: number;
    name: string;
    email: string;
    role: string;
};

defineProps<{
    user: UserForm;
    roles: RoleOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Users',
                href: index(),
            },
            {
                title: 'Edit user',
                href: index(),
            },
        ],
    },
});

const fieldClass =
    'border-input placeholder:text-muted-foreground dark:bg-input/30 h-9 w-full rounded-md border bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]';
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Head title="Edit user" />

        <Heading title="Edit user" :description="user.name" />

        <Form
            v-bind="UserController.update.form(user.id)"
            class="max-w-xl space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    name="name"
                    :default-value="user.name"
                    required
                    autocomplete="name"
                    data-test="user-name"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    :default-value="user.email"
                    required
                    autocomplete="email"
                    data-test="user-email"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="role">Role</Label>
                <select
                    id="role"
                    name="role"
                    required
                    :class="fieldClass"
                    data-test="user-role"
                >
                    <option
                        v-for="role in roles"
                        :key="role.value"
                        :value="role.value"
                        :selected="role.value === user.role"
                    >
                        {{ role.label }}
                    </option>
                </select>
                <InputError :message="errors.role" />
            </div>

            <Button type="submit" :disabled="processing" data-test="save-user">
                Save user
            </Button>
        </Form>
    </div>
</template>
