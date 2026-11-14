<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import {
    create,
    edit,
    index,
} from '@/actions/App/Customers/Http/Controllers/ContactController';

type ContactRow = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    owner: {
        name: string;
    };
};

defineProps<{
    contacts: ContactRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Contacts',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Head title="Contacts" />

        <div class="flex items-center justify-between gap-4">
            <Heading
                title="Contacts"
                description="People your team is working with"
            />

            <Button as-child>
                <Link :href="create()" data-test="create-contact">
                    New contact
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
                        <th class="px-4 py-3 font-medium">Phone</th>
                        <th class="px-4 py-3 font-medium">Owner</th>
                        <th class="px-4 py-3 font-medium">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="contacts.length === 0">
                        <td
                            colspan="5"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No contacts yet.
                        </td>
                    </tr>
                    <tr
                        v-for="contact in contacts"
                        :key="contact.id"
                        class="border-b border-sidebar-border/70 last:border-0 dark:border-sidebar-border"
                    >
                        <td class="px-4 py-3">{{ contact.name }}</td>
                        <td class="px-4 py-3">{{ contact.email }}</td>
                        <td class="px-4 py-3">{{ contact.phone ?? '—' }}</td>
                        <td class="px-4 py-3">{{ contact.owner.name }}</td>
                        <td class="px-4 py-3 text-right">
                            <TextLink :href="edit(contact.id)">Edit</TextLink>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
