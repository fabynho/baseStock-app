<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowDownToLine,
    BookOpen,
    FolderGit2,
    Layers,
    LayoutGrid,
    ShoppingBag,
    Truck,
    UserCheck,
    Boxes
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
        icon: FolderGit2,
    }
];

const footerNavItems: NavItem[] = [
    {
        title: 'Usuários',
        href: '/users',
        icon: UserCheck,
    },

];
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
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>