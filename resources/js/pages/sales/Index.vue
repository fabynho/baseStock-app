<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import SaleStatusBadge from '@/components/sales/SaleStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDateTime } from '@/lib/format';
import { create, index, show } from '@/routes/sales';
import type { EnumOption, Paginated, Sale } from '@/types/stock';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search } from '@lucide/vue';
import { reactive, watch } from 'vue';

const props = defineProps<{
    sales: Paginated<Sale>;
    filters: { search?: string | null; status?: string | null; date_from?: string | null; date_to?: string | null };
    statuses: EnumOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Vendas',
                href: index().url,
            },
        ],
    },
});

const filters = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? 'all',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
});

// Filtros recarregam só a lista; a busca por texto tem debounce.
let timer: ReturnType<typeof setTimeout>;

watch(filters, () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(
            index().url,
            {
                search: filters.search || undefined,
                status: filters.status === 'all' ? undefined : filters.status,
                date_from: filters.date_from || undefined,
                date_to: filters.date_to || undefined,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
});
</script>

<template>
    <Head title="Vendas" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading title="Vendas" description="Saídas registradas, vinculadas ao documento do sistema principal" />

            <Button as-child>
                <Link :href="create()">
                    <Plus class="h-4 w-4" />
                    Nova venda
                </Link>
            </Button>
        </div>

        <div class="flex flex-wrap items-end gap-4">
            <div class="relative w-full max-w-sm">
                <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="filters.search" class="pl-9" placeholder="Buscar por documento ou cliente" autocomplete="off" />
            </div>

            <div class="grid gap-1">
                <Label class="text-xs text-muted-foreground">Status</Label>
                <Select v-model="filters.status">
                    <SelectTrigger class="w-56">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todos os status</SelectItem>
                        <SelectItem v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="grid gap-1">
                <Label for="date_from" class="text-xs text-muted-foreground">Venda de</Label>
                <Input id="date_from" v-model="filters.date_from" type="date" />
            </div>

            <div class="grid gap-1">
                <Label for="date_to" class="text-xs text-muted-foreground">Venda até</Label>
                <Input id="date_to" v-model="filters.date_to" type="date" />
            </div>
        </div>

        <div class="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Documento</TableHead>
                        <TableHead>Cliente</TableHead>
                        <TableHead>Vendedor</TableHead>
                        <TableHead>Tipo</TableHead>
                        <TableHead class="w-48">Status</TableHead>
                        <TableHead class="w-40">Data da venda</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-if="sales.data.length === 0">
                        <TableCell colspan="6" class="h-24 text-center text-muted-foreground">Nenhuma venda encontrada.</TableCell>
                    </TableRow>

                    <TableRow v-for="sale in sales.data" :key="sale.id" :class="{ 'text-muted-foreground': sale.status.value === 'canceled' }">
                        <TableCell class="font-medium">
                            <Link :href="show(sale.id)" class="underline-offset-4 hover:underline">{{ sale.document_number }}</Link>
                        </TableCell>
                        <TableCell>{{ sale.customer_name }}</TableCell>
                        <TableCell>{{ sale.seller?.name ?? '—' }}</TableCell>
                        <TableCell>{{ sale.delivery_type.label }}</TableCell>
                        <TableCell><SaleStatusBadge :status="sale.status" /></TableCell>
                        <TableCell>{{ formatDateTime(sale.sold_at) }}</TableCell>
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
    </div>
</template>
