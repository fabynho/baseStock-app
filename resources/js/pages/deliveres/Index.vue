<script setup lang="ts">
import ConfirmDeliveryDialog from '@/components/deliveries/ConfirmDeliveryDialog.vue';
import Heading from '@/components/Heading.vue';
import SaleStatusBadge from '@/components/sales/SaleStatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDateTime, formatM2 } from '@/lib/format';
import { index as deliveriesIndex } from '@/routes/deliveres';
import { show } from '@/routes/sales';
import type { Deliverer, Paginated, Sale } from '@/types/stock';
import { Head, Link, router } from '@inertiajs/vue3';
import { PackageCheck, Search } from '@lucide/vue';
import { ref, watch } from 'vue';

const props = defineProps<{
    sales: Paginated<Sale>;
    deliverers: Deliverer[];
    filters: { search: string | null };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Entregas', href: deliveriesIndex().url }],
    },
});

// Busca com debounce: recarrega só a lista, mantendo o foco no campo.
const search = ref(props.filters.search ?? '');
let timer: ReturnType<typeof setTimeout>;

watch(search, (value) => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(deliveriesIndex().url, { search: value || undefined }, { preserveState: true, preserveScroll: true, replace: true });
    }, 300);
});

const totalOf = (sale: Sale) => (sale.items ?? []).reduce((sum, item) => sum + Number(item.quantity_m2), 0);

// Confirmação: um único dialog, reaproveitado para qualquer venda da lista.
const dialogOpen = ref(false);
const selected = ref<Sale | null>(null);

const openConfirm = (sale: Sale) => {
    selected.value = sale;
    dialogOpen.value = true;
};
</script>

<template>
    <Head title="Fila de entregas" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <Heading
            title="Fila de entregas"
            :description="`Vendas aguardando entrega ou retirada (${sales.meta.total} pendente${sales.meta.total === 1 ? '' : 's'})`"
        />

        <div class="relative max-w-sm">
            <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input v-model="search" class="pl-9" placeholder="Buscar por documento ou cliente" autocomplete="off" />
        </div>

        <div class="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Documento</TableHead>
                        <TableHead>Cliente</TableHead>
                        <TableHead>Vendedor</TableHead>
                        <TableHead>Tipo</TableHead>
                        <TableHead>Pisos</TableHead>
                        <TableHead class="text-right">Total (m²)</TableHead>
                        <TableHead class="w-40">Data da venda</TableHead>
                        <TableHead class="w-44 text-right">Ação</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-if="sales.data.length === 0">
                        <TableCell colspan="8" class="h-24 text-center text-muted-foreground">Nenhuma entrega ou retirada pendente.</TableCell>
                    </TableRow>

                    <TableRow v-for="sale in sales.data" :key="sale.id" class="align-top">
                        <TableCell class="font-medium">
                            <Link :href="show(sale.id)" class="underline-offset-4 hover:underline">{{ sale.document_number }}</Link>
                        </TableCell>
                        <TableCell>{{ sale.customer_name }}</TableCell>
                        <TableCell>{{ sale.seller?.name ?? '—' }}</TableCell>
                        <TableCell>
                            <Badge variant="outline">{{ sale.delivery_type.label }}</Badge>
                        </TableCell>
                        <TableCell>
                            <ul class="space-y-0.5 text-sm">
                                <li v-for="item in sale.items ?? []" :key="item.id" class="flex justify-between gap-4">
                                    <span :title="item.floor?.description">{{ item.floor?.code ?? item.floor_id }}</span>
                                    <span class="text-muted-foreground tabular-nums">{{ formatM2(item.quantity_m2) }}</span>
                                </li>
                            </ul>
                        </TableCell>
                        <TableCell class="text-right font-medium tabular-nums">{{ formatM2(totalOf(sale)) }}</TableCell>
                        <TableCell>{{ formatDateTime(sale.sold_at) }}</TableCell>
                        <TableCell class="text-right">
                            <Button v-if="sale.can_confirm" size="sm" @click="openConfirm(sale)">
                                <PackageCheck class="h-4 w-4" />
                                Marcar como entregue
                            </Button>
                            <SaleStatusBadge v-else :status="sale.status" />
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div v-if="sales.meta.total > 0" class="flex items-center justify-between text-sm text-muted-foreground">
            <span>Mostrando {{ sales.meta.from }}–{{ sales.meta.to }} de {{ sales.meta.total }}</span>

            <div class="flex items-center gap-2">
                <Button v-if="sales.links.prev" variant="outline" size="sm" as-child>
                    <Link :href="sales.links.prev" preserve-scroll>Anterior</Link>
                </Button>
                <Button v-else variant="outline" size="sm" disabled>Anterior</Button>

                <span>Página {{ sales.meta.current_page }} de {{ sales.meta.last_page }}</span>

                <Button v-if="sales.links.next" variant="outline" size="sm" as-child>
                    <Link :href="sales.links.next" preserve-scroll>Próxima</Link>
                </Button>
                <Button v-else variant="outline" size="sm" disabled>Próxima</Button>
            </div>
        </div>

        <ConfirmDeliveryDialog v-model:open="dialogOpen" :sale="selected" :deliverers="deliverers" />
    </div>
</template>