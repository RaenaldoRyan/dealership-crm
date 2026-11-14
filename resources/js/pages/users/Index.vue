<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import UserController from '@/actions/App/Core/Http/Controllers/UserController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { create, edit, index } from '@/routes/users';

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: string;
};

defineProps<{
    users: UserRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Users',
                href: index(),
            },
        ],
    },
});

const page = usePage();
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Head title="Users" />

        <div class="flex items-center justify-between gap-4">
            <Heading
                title="Users"
                description="People who can sign in"
            />

            <Button as-child>
                <Link :href="create()" data-test="create-user">
                    New user
                </Link>
            </Button>
        </div>

        <div
            class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <table class="w-full text-sm">
                <thead
                    class="border-b border-sidebar-border/70 text-left text-muted-foreground dark:border-sidebar-border"
                >
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Email</th>
                        <th class="px-4 py-3 font-medium">Role</th>
                        <th class="px-4 py-3 font-medium">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="users.length === 0">
                        <td
                            colspan="4"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No users yet.
                        </td>
                    </tr>
                    <tr
                        v-for="user in users"
                        :key="user.id"
                        class="border-b border-sidebar-border/70 last:border-b-0 dark:border-sidebar-border"
                    >
                        <td class="px-4 py-3">{{ user.name }}</td>
                        <td class="px-4 py-3">{{ user.email }}</td>
                        <td class="px-4 py-3 capitalize">{{ user.role }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <Button variant="ghost" as-child>
                                    <Link
                                        :href="edit(user.id)"
                                        :data-test="`edit-user-${user.id}`"
                                    >
                                        Edit
                                    </Link>
                                </Button>
                                <Form
                                    v-if="user.id !== page.props.auth.user.id"
                                    v-bind="UserController.destroy.form(user.id)"
                                >
                                    <Button
                                        type="submit"
                                        variant="ghost"
                                        :data-test="`delete-user-${user.id}`"
                                    >
                                        Remove
                                    </Button>
                                </Form>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
