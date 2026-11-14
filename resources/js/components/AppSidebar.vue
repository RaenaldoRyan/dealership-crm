<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Car,
    ChartColumn,
    Kanban,
    LayoutGrid,
    UserCog,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarSeparator,
} from '@/components/ui/sidebar';
import { index as contacts } from '@/routes/contacts';
import { overview } from '@/routes';
import { index as deals } from '@/routes/deals';
import { index as reports } from '@/routes/reports';
import { index as users } from '@/routes/users';
import { index as vehicles } from '@/routes/vehicles';
import type { NavItem } from '@/types';

const page = usePage();

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Overview',
            href: overview(),
            icon: LayoutGrid,
        },
        {
            title: 'Pipeline',
            href: deals(),
            icon: Kanban,
        },
        {
            title: 'Contacts',
            href: contacts(),
            icon: Users,
        },
        {
            title: 'Inventory',
            href: vehicles(),
            icon: Car,
        },
    ];

    if (page.props.can.viewReports) {
        items.push({
            title: 'Reports',
            href: reports(),
            icon: ChartColumn,
        });
    }

    return items;
});

const adminNavItems = computed<NavItem[]>(() => {
    if (!page.props.can.manageUsers) {
        return [];
    }

    return [
        {
            title: 'Users',
            href: users(),
            icon: UserCog,
        },
    ];
});
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton
                        size="lg"
                        as-child
                        tooltip="Pipeline"
                        class="group-data-[collapsible=icon]:p-0!"
                    >
                        <Link :href="overview()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <template v-if="adminNavItems.length > 0">
                <SidebarSeparator />
                <NavMain label="Admin" :items="adminNavItems" />
            </template>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
