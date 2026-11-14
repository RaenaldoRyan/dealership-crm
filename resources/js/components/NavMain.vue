<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
    label?: string;
}>();

const { currentUrl, isCurrentOrParentUrl } = useCurrentUrl();

function itemIsActive(href: NavItem['href']): boolean {
    if (isCurrentOrParentUrl(href)) {
        return true;
    }

    return toUrl(href) === '/overview' && currentUrl.value === '/dashboard';
}
</script>

<template>
    <SidebarGroup class="py-0">
        <SidebarGroupLabel v-if="label">{{ label }}</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="itemIsActive(item.href)"
                    :tooltip="item.title"
                >
                    <Link :href="item.href">
                        <component
                            :is="item.icon"
                            v-if="item.icon"
                            class="hidden group-data-[collapsible=icon]:block"
                        />
                        <span class="group-data-[collapsible=icon]:hidden">{{
                            item.title
                        }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
