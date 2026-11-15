<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import DelivererFormDialog from '@/components/deliverers/DelivererFormDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { index as deliverersIndex } from '@/routes/deliverers';
import type { Deliverer } from '@/types/stock';
import { Head } from '@inertiajs/vue3';
import { Pencil, Plus } from '@lucide/vue';
import { ref } from 'vue';

defineProps<{
    deliverers: Deliverer[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Entregadores',
                href: deliverersIndex(),
            },
        ],
    }
});


const dialogOpen = ref(false);
const editing = ref<Deliverer | null>(null);

const openCreate = () => {
    editing.value = null;
    dialogOpen.value = true;
};

const openEdit = (deliverer: Deliverer) => {
    editing.value = deliverer;
    dialogOpen.value = true;
};
</script>

<template>
   
        <Head title="Entregadores" />

        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
            <div class="flex items-start justify-between gap-4">
                <Heading title="Entregadores" description="Responsáveis pela entrega e baixa física das vendas pendentes" />

                <Button @click="openCreate">
                    <Plus class="h-4 w-4" />
                    Novo entregador
                </Button>
            </div>

            <div class="rounded-xl border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nome</TableHead>
                            <TableHead>Veículo / placa</TableHead>
                            <TableHead class="w-32">Status</TableHead>
                            <TableHead class="w-20 text-right">Ações</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <TableRow v-if="deliverers.length === 0">
                            <TableCell colspan="4" class="h-24 text-center text-muted-foreground">Nenhum entregador cadastrado.</TableCell>
                        </TableRow>

                        <TableRow v-for="deliverer in deliverers" :key="deliverer.id" :class="{ 'text-muted-foreground': !deliverer.is_active }">
                            <TableCell class="font-medium">{{ deliverer.name }}</TableCell>
                            <TableCell>{{ deliverer.vehicle_info || '—' }}</TableCell>
                            <TableCell>
                                <Badge :variant="deliverer.is_active ? 'default' : 'secondary'">
                                    {{ deliverer.is_active ? 'Ativo' : 'Inativo' }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-right">
                                <Button variant="ghost" size="icon" :aria-label="`Editar ${deliverer.name}`" @click="openEdit(deliverer)">
                                    <Pencil class="h-4 w-4" />
                                </Button>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>

        <!-- key: remonta o diálogo ao alternar entre criar e editar entregadores diferentes -->
        <DelivererFormDialog v-model:open="dialogOpen" :deliverer="editing" :key="editing?.id ?? 'new'" />
    
</template>