<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import {
    store,
    update,
} from '@/actions/App/Core/Http/Controllers/UserController';
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
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { Switch } from '@/components/ui/switch';
import { index } from '@/routes/users';

type RoleOption = {
    value: string;
    label: string;
};

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: string;
    roleLabel: string;
    active: boolean;
    lastLogin: string;
};

type UserPanel = {
    mode: 'create' | 'edit';
    id: number | null;
    name: string;
    email: string;
    role: string;
    active: boolean;
};

const props = defineProps<{
    users: UserRow[];
    roles: RoleOption[];
    panel: UserPanel | null;
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

const role = ref('rep');
const active = ref(true);

watch(
    () => props.panel,
    (panel) => {
        role.value = panel?.role ?? 'rep';
        active.value = panel?.active ?? true;
    },
    { immediate: true },
);

const formBinding = computed(() =>
    props.panel?.mode === 'edit' && props.panel.id !== null
        ? update.form(props.panel.id)
        : store.form(),
);

function closePanel(open: boolean): void {
    if (open || props.panel === null) {
        return;
    }

    router.get(index.url(), {}, { preserveScroll: true, replace: true });
}

function editHref(id: number): string {
    return index.url({ query: { user: id } });
}
</script>

<template>
    <div class="flex h-full flex-1 flex-col gap-6 p-4 sm:p-6">
        <Head title="Users" />

        <Heading title="Users" description="Manage your sales team" />

        <div class="overflow-hidden rounded-xl border bg-card">
            <table class="w-full text-sm">
                <thead class="border-b text-left text-muted-foreground">
                    <tr>
                        <th class="priority-1 px-4 py-3 font-medium">Name</th>
                        <th class="priority-4 px-4 py-3 font-medium">Email</th>
                        <th class="priority-3 px-4 py-3 font-medium">Role</th>
                        <th class="priority-2 px-4 py-3 font-medium">Status</th>
                        <th class="priority-5 px-4 py-3 font-medium">
                            Last login
                        </th>
                        <th class="priority-1 px-4 py-3 font-medium">
                            <span class="sr-only">Actions</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="users.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No users yet.
                        </td>
                    </tr>
                    <tr
                        v-for="user in users"
                        :key="user.id"
                        class="border-b last:border-b-0"
                    >
                        <td class="priority-1 px-4 py-3">{{ user.name }}</td>
                        <td class="priority-4 px-4 py-3">{{ user.email }}</td>
                        <td class="priority-3 px-4 py-3">
                            <span class="inline-flex items-center gap-2">
                                {{ user.roleLabel }}
                                <span
                                    class="inline-block size-1.5 rounded-full bg-primary"
                                />
                            </span>
                        </td>
                        <td class="priority-2 px-4 py-3">
                            <span class="inline-flex items-center gap-2">
                                {{ user.active ? 'Active' : 'Inactive' }}
                                <span
                                    class="inline-block size-1.5 rounded-full"
                                    :class="
                                        user.active
                                            ? 'bg-primary'
                                            : 'bg-muted-foreground'
                                    "
                                />
                            </span>
                        </td>
                        <td class="priority-5 px-4 py-3 text-muted-foreground">
                            {{ user.lastLogin }}
                        </td>
                        <td class="priority-1 px-4 py-3 text-right">
                            <Button variant="ghost" as-child>
                                <Link
                                    :href="editHref(user.id)"
                                    :data-test="`edit-user-${user.id}`"
                                >
                                    Edit
                                </Link>
                            </Button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <Sheet :open="panel !== null" @update:open="closePanel">
            <SheetContent class="flex flex-col sm:max-w-md">
                <SheetHeader>
                    <SheetTitle>
                        {{ panel?.mode === 'edit' ? 'Edit user' : 'Add user' }}
                    </SheetTitle>
                    <SheetDescription v-if="panel?.mode !== 'edit'">
                        They sign in with the shared password: password
                    </SheetDescription>
                </SheetHeader>

                <Form
                    v-if="panel"
                    :key="`${panel.mode}-${panel.id ?? 'new'}`"
                    v-bind="formBinding"
                    class="flex flex-1 flex-col gap-6 px-4"
                    v-slot="{ errors, processing }"
                >
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            name="name"
                            :default-value="panel.name"
                            required
                            autocomplete="name"
                            placeholder="Full name"
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
                            :default-value="panel.email"
                            required
                            autocomplete="email"
                            placeholder="Email address"
                            data-test="user-email"
                        />
                        <InputError :message="errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="role">Role</Label>
                        <Select v-model="role">
                            <SelectTrigger
                                id="role"
                                class="w-full shadow-none"
                                data-test="user-role"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in roles"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <input type="hidden" name="role" :value="role" />
                        <InputError :message="errors.role" />
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <Label for="active">Active</Label>
                        <Switch
                            id="active"
                            v-model="active"
                            data-test="user-active"
                        />
                        <input
                            type="hidden"
                            name="is_active"
                            :value="active ? '1' : '0'"
                        />
                    </div>
                    <InputError :message="errors.is_active" />

                    <div class="mt-auto flex justify-end gap-2 pb-4">
                        <Button variant="outline" as-child>
                            <Link :href="index()">Cancel</Link>
                        </Button>
                        <Button
                            type="submit"
                            :disabled="processing"
                            data-test="save-user"
                        >
                            Save changes
                        </Button>
                    </div>
                </Form>
            </SheetContent>
        </Sheet>
    </div>
</template>
