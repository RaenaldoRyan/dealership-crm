<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import HeaderSearch from '@/components/HeaderSearch.vue';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { create as createContact } from '@/routes/contacts';
import { create as createDeal } from '@/routes/deals';
import { index as usersIndex } from '@/routes/users';
import { create as createVehicle } from '@/routes/vehicles';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const { currentUrl } = useCurrentUrl();

const primaryAction = computed(() => {
    const path = currentUrl.value;

    if (path === '/deals' || path.startsWith('/deals/')) {
        return {
            label: 'New deal',
            href: createDeal(),
            test: 'create-deal',
        };
    }

    if (path === '/contacts' || path.startsWith('/contacts/')) {
        return {
            label: 'New contact',
            href: createContact(),
            test: 'create-contact',
        };
    }

    if (path === '/vehicles' || path.startsWith('/vehicles/')) {
        return {
            label: 'Add vehicle',
            href: createVehicle(),
            test: 'create-vehicle',
        };
    }

    if (
        (path === '/users' || path.startsWith('/users/')) &&
        page.props.can.manageUsers
    ) {
        return {
            label: 'Add user',
            href: usersIndex.url({ query: { user: 'new' } }),
            test: 'create-user',
        };
    }

    return null;
});
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-3 border-b border-sidebar-border/70 px-4 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-6"
    >
        <div class="flex min-w-0 items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 1">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <div class="ml-auto flex items-center gap-3">
            <HeaderSearch />

            <Button v-if="primaryAction" as-child>
                <Link
                    :href="primaryAction.href"
                    :data-test="primaryAction.test"
                >
                    {{ primaryAction.label }}
                </Link>
            </Button>
        </div>
    </header>
</template>
