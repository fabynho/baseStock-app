<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    ArrowDownToLine,
    BookOpen,
    FolderGit2,
    Layers,
    LayoutGrid,
    PackageX,
    ShoppingBag,
    Truck,
    UserCheck,
    Boxes,
    Undo2,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
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
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Vendedores',
        href: '/sellers',
        icon: UserCheck,
    },
    {
        title: 'Entregadores',
        href: '/deliverers',
        icon: Truck,
    },
    {
        title: 'Pisos',
        href: '/floors',
        icon: Layers,
    },
    {
        title: 'Entradas',
        href: '/purchases',
        icon: ArrowDownToLine,
    },
    {
        title: 'Vendas',
        href: '/sales',
        icon: ShoppingBag,
    },
    {
        title: 'Movimentações',
        href: '/movements',
        icon: BookOpen,
    },
    {
        title: 'Fila de entregas',
        href: '/deliveres',
        icon: Truck,
    },
    {
        title: 'Posição de Estoque',
        href: '/stock',
        icon: Boxes,
    },
    {
        title: 'Quebras / Avarias',
        href: '/breakages/create',
        icon: PackageX,
    },
    {
        title: 'Devoluções',
        href: '/customer-returns/create',
        icon: Undo2,
    }
];

const page = usePage();
const isAdmin = computed(() => page.props.auth.user?.role === 'admin');

const footerNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [];

    if (isAdmin.value) {
        items.push({
            title: 'Usuários',
            href: '/users',
            icon: UserCheck,
        });
    }

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter v-if="footerNavItems.length > 0" :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>