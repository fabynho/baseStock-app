<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import SellerFormDialog from '@/components/sellers/SellerFormDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { index  as sellersIndex } from '@/routes/sellers';
import type { Seller } from '@/types/stock';
import { Head } from '@inertiajs/vue3';
import { Pencil, Plus } from '@lucide/vue';
import { ref } from 'vue';

defineProps<{
    sellers: Seller[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Vendedores',
                href: sellersIndex(),
            },
        ],
    }
});

const dialogOpen = ref(false);
const editing = ref<Seller | null>(null);

const openCreate = () => {
    editing.value = null;
    dialogOpen.value = true;
};

const openEdit = (seller: Seller) => {
    editing.value = seller;
    dialogOpen.value = true;
};
</script>

<template>
    
        <Head title="Vendedores" />

        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <div class="flex items-start justify-between gap-4">
                <Heading title="Vendedores" description="Vendedores internos vinculados às vendas registradas" />

                <Button @click="openCreate">
                    <Plus class="h-4 w-4" />
                    Novo vendedor
                </Button>
            </div>

            <div class="rounded-xl border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nome</TableHead>
                            <TableHead class="w-32">Status</TableHead>
                            <TableHead class="w-20 text-right">Ações</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <TableRow v-if="sellers.length === 0">
                            <TableCell colspan="3" class="h-24 text-center text-muted-foreground">Nenhum vendedor cadastrado.</TableCell>
                        </TableRow>

                        <TableRow v-for="seller in sellers" :key="seller.id" :class="{ 'text-muted-foreground': !seller.is_active }">
                            <TableCell class="font-medium">{{ seller.name }}</TableCell>
                            <TableCell>
                                <Badge :variant="seller.is_active ? 'default' : 'secondary'">
                                    {{ seller.is_active ? 'Ativo' : 'Inativo' }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <Button variant="ghost" size="icon" :aria-label="`Editar ${seller.name}`" @click="openEdit(seller)">
                                    <Pencil class="h-4 w-4" />
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

        <!-- key: remonta o diálogo ao alternar entre criar e editar vendedores diferentes -->
        <SellerFormDialog v-model:open="dialogOpen" :seller="editing" :key="editing?.id ?? 'new'" />
    
</template>
