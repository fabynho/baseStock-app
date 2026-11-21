<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { create, edit, index } from '@/routes/users';
import type { ManagedUser, Paginated } from '@/types/stock';
import { Head, Link } from '@inertiajs/vue3';
import { Pencil, Plus } from '@lucide/vue';

defineProps<{
    users: Paginated<ManagedUser>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Usuários',
                href: index().url,
            },
        ],
    },
});
</script>

<template>
    <Head title="Usuários" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading title="Usuários" description="Gerencie quem acessa o sistema, o perfil de cada um e o status da conta" />

            <Button as-child>
                <Link :href="create()">
                    <Plus class="h-4 w-4" />
                    Novo usuário
                </Link>
            </Button>
        </div>

        <div class="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Nome</TableHead>
                        <TableHead>E-mail</TableHead>
                        <TableHead class="w-40">Perfil</TableHead>
                        <TableHead class="w-28">Status</TableHead>
                        <TableHead class="w-20 text-right">Ações</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-if="users.data.length === 0">
                        <TableCell colspan="5" class="h-24 text-center text-muted-foreground">Nenhum usuário encontrado.</TableCell>
                    </TableRow>

                    <TableRow v-for="user in users.data" :key="user.id" :class="{ 'text-muted-foreground': !user.is_active }">
                        <TableCell class="font-medium">
                            {{ user.name }}
                            <Badge v-if="user.is_self" variant="outline" class="ml-2">Você</Badge>
                        </TableCell>
                        <TableCell>{{ user.email }}</TableCell>
                        <TableCell>
                            <Badge :variant="user.role.value === 'admin' ? 'default' : 'secondary'">
                                {{ user.role.label }}
                            </Badge>
                        </TableCell>
                        <TableCell>
                            <Badge :variant="user.is_active ? 'default' : 'secondary'">
                                {{ user.is_active ? 'Ativo' : 'Inativo' }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-right">
                            <Button variant="ghost" size="icon" as-child :aria-label="`Editar ${user.name}`">
                                <Link :href="edit(user.id)">
                                    <Pencil class="h-4 w-4" />
                                </Link>
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div v-if="users.meta.total > 0" class="flex items-center justify-between text-sm text-muted-foreground">
            <span>Mostrando {{ users.meta.from }}–{{ users.meta.to }} de {{ users.meta.total }}</span>

            <div class="flex items-center gap-2">
                <Button v-if="users.links.prev" variant="outline" size="sm" as-child>
                    <Link :href="users.links.prev" preserve-scroll>Anterior</Link>
                </Button>
                <Button v-else variant="outline" size="sm" disabled>Anterior</Button>

                <span>Página {{ users.meta.current_page }} de {{ users.meta.last_page }}</span>

                <Button v-if="users.links.next" variant="outline" size="sm" as-child>
                    <Link :href="users.links.next" preserve-scroll>Próxima</Link>
                </Button>
                <Button v-else variant="outline" size="sm" disabled>Próxima</Button>
            </div>
        </div>
    </div>
</template>