<script setup lang="ts">
import ConfirmDeliveryDialog from '@/components/deliveries/ConfirmDeliveryDialog.vue';
import Heading from '@/components/Heading.vue';
import SaleStatusBadge from '@/components/sales/SaleStatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDateTime } from '@/lib/format';
import { index as deliveriesIndex } from '@/routes/deliveres';
import { show } from '@/routes/sales';
import type { Deliverer, Paginated, Sale } from '@/types/stock';
import { Head, Link, router } from '@inertiajs/vue3';
import { PackageCheck, Search, X } from '@lucide/vue';
import { onBeforeUnmount, ref, watch } from 'vue';

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
let timer: ReturnType<typeof setTimeout> | undefined;

watch(search, (value) => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(
            deliveriesIndex().url,
            { search: value.trim() || undefined },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
});

onBeforeUnmount(() => clearTimeout(timer));

const clearSearch = () => {
    search.value = '';
};

// Confirmação: um único dialog, reaproveitado para qualquer venda da lista.
const dialogOpen = ref(false);
const selected = ref<Sale | null>(null);

const openConfirm = (sale: Sale) => {
    selected.value = sale;
    dialogOpen.value = true;
};

watch(dialogOpen, (isOpen) => {
    if (!isOpen) {
        selected.value = null;
    }
});
</script>

<template>
    <Head title="Fila de entregas" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <Heading
            title="Fila de entregas"
            :description="`Vendas aguardando entrega ou retirada (${sales.meta.total} pendente${sales.meta.total === 1 ? '' : 's'})`"
        />

        <div class="relative max-w-sm">
            <Search class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
                v-model="search"
                class="pr-9 pl-9"
                placeholder="Buscar por documento ou cliente"
                autocomplete="off"
            />
            <button
                v-if="search"
                type="button"
                aria-label="Limpar busca"
                class="absolute top-1/2 right-2.5 -translate-y-1/2 rounded-sm p-0.5 text-muted-foreground transition-colors hover:text-foreground"
                @click="clearSearch"
            >
                <X class="h-4 w-4" />
            </button>
        </div>

        <div class="overflow-x-auto rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-36">Documento</TableHead>
                        <TableHead>Cliente</TableHead>
                        <TableHead>Vendedor</TableHead>
                        <TableHead class="w-40">Tipo</TableHead>
                        <TableHead class="w-44">Data da venda</TableHead>
                        <TableHead class="w-48 text-right">Ação</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-if="sales.data.length === 0">
                        <TableCell colspan="6" class="h-28 text-center text-muted-foreground">
                            <p v-if="search">Nenhuma venda encontrada para "{{ search }}".</p>
                            <p v-else>Nenhuma entrega ou retirada pendente.</p>
                        </TableCell>
                    </TableRow>

                    <TableRow v-for="sale in sales.data" :key="sale.id" class="hover:bg-muted/40">
                        <TableCell class="font-medium whitespace-nowrap">
                            <Link :href="show(sale.id)" class="underline-offset-4 hover:underline">
                                {{ sale.document_number }}
                            </Link>
                        </TableCell>
                        <TableCell class="font-medium">{{ sale.customer_name }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ sale.seller?.name ?? '—' }}</TableCell>
                        <TableCell class="whitespace-nowrap">
                            <Badge variant="outline">{{ sale.delivery_type.label }}</Badge>
                        </TableCell>
                        <TableCell class="whitespace-nowrap text-muted-foreground tabular-nums">
                            {{ formatDateTime(sale.sold_at) }}
                        </TableCell>
                        <TableCell class="text-right whitespace-nowrap">
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

        <div v-if="sales.meta.total > 0" class="flex flex-wrap items-center justify-between gap-3 text-sm text-muted-foreground">
            <span class="tabular-nums">Mostrando {{ sales.meta.from }}–{{ sales.meta.to }} de {{ sales.meta.total }}</span>

            <div class="flex items-center gap-2">
                <Button v-if="sales.links.prev" variant="outline" size="sm" as-child>
                    <Link :href="sales.links.prev" preserve-scroll>Anterior</Link>
                </Button>
                <Button v-else variant="outline" size="sm" disabled>Anterior</Button>

                <span class="px-1 tabular-nums">Página {{ sales.meta.current_page }} de {{ sales.meta.last_page }}</span>

                <Button v-if="sales.links.next" variant="outline" size="sm" as-child>
                    <Link :href="sales.links.next" preserve-scroll>Próxima</Link>
                </Button>
                <Button v-else variant="outline" size="sm" disabled>Próxima</Button>
            </div>
        </div>

        <ConfirmDeliveryDialog v-model:open="dialogOpen" :sale="selected" :deliverers="deliverers" />
    </div>
</template>