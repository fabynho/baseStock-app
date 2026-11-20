<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDateTime, formatM2 } from '@/lib/format';
import { show as floorShow } from '@/routes/floors';
import { index } from '@/routes/movements';
import { show as purchaseShow } from '@/routes/purchases';
import { show as saleShow } from '@/routes/sales';
import type { EnumOption, FloorOption, Paginated, StockMovement } from '@/types/stock';
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, X } from '@lucide/vue';
import { computed, reactive, watch } from 'vue';

const props = defineProps<{
    movements: Paginated<StockMovement>;
    filters: {
        floor_id?: number | string | null;
        type?: string | null;
        document_number?: string | null;
        date_from?: string | null;
        date_to?: string | null;
        per_page?: number | string | null;
    };
    floors: { data: FloorOption[] };
    types: EnumOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Kardex', href: index().url }],
    },
});

// 'all' representa "sem filtro" porque o Select não aceita valor vazio.
const filters = reactive({
    floor_id: props.filters.floor_id ? String(props.filters.floor_id) : 'all',
    type: props.filters.type ?? 'all',
    document_number: props.filters.document_number ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
    per_page: props.filters.per_page ? String(props.filters.per_page) : '50',
});

const hasFilters = computed(
    () => filters.floor_id !== 'all' || filters.type !== 'all' || !!filters.document_number || !!filters.date_from || !!filters.date_to,
);

// Filtros recarregam só a lista; o campo de documento tem debounce.
let timer: ReturnType<typeof setTimeout>;

watch(filters, () => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(
            index().url,
            {
                floor_id: filters.floor_id === 'all' ? undefined : filters.floor_id,
                type: filters.type === 'all' ? undefined : filters.type,
                document_number: filters.document_number || undefined,
                date_from: filters.date_from || undefined,
                date_to: filters.date_to || undefined,
                per_page: filters.per_page === '50' ? undefined : filters.per_page,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
});

const clearFilters = () => {
    filters.floor_id = 'all';
    filters.type = 'all';
    filters.document_number = '';
    filters.date_from = '';
    filters.date_to = '';
};

// Sinal/cor por tipo: entrada (+), saída (−), reserva/liberação (âmbar), ajuste (sinal do valor).
const effectOf = (m: StockMovement): 'in' | 'out' | 'commit' | 'adjust' => {
    switch (m.type.value) {
        case 'purchase_in':
        case 'customer_return_in':
            return 'in';
        case 'sale_delivery_out':
        case 'breakage_out':
            return 'out';
        case 'sale_reservation':
        case 'sale_cancel_release':
            return 'commit';
        default:
            return 'adjust';
    }
};

const isPositive = (m: StockMovement) => {
    const effect = effectOf(m);
    if (effect === 'adjust') return Number(m.quantity_m2) > 0;
    return effect === 'in' || m.type.value === 'sale_reservation';
};

const qtyLabel = (m: StockMovement) => `${isPositive(m) ? '+' : '−'}${formatM2(String(m.quantity_m2).replace('-', ''))}`;

const qtyClass = (m: StockMovement) => {
    if (effectOf(m) === 'commit') return 'text-amber-600 dark:text-amber-400';
    return isPositive(m) ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
};
</script>

<template>
    <Head title="Kardex" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <Heading title="Kardex" description="Histórico auditável de todas as movimentações de estoque" />

        <div class="flex flex-wrap items-end gap-4">
            <div class="grid gap-1">
                <Label class="text-xs text-muted-foreground">Piso</Label>
                <Select v-model="filters.floor_id">
                    <SelectTrigger class="w-64">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todos os pisos</SelectItem>
                        <SelectItem v-for="floor in floors.data" :key="floor.id" :value="String(floor.id)">
                            {{ floor.code }} — {{ floor.description }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="grid gap-1">
                <Label class="text-xs text-muted-foreground">Tipo de movimentação</Label>
                <Select v-model="filters.type">
                    <SelectTrigger class="w-60">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Todos os tipos</SelectItem>
                        <SelectItem v-for="type in types" :key="type.value" :value="type.value">{{ type.label }}</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="relative w-full max-w-xs">
                <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="filters.document_number" class="pl-9" placeholder="Número do documento" autocomplete="off" />
            </div>

            <div class="grid gap-1">
                <Label for="date_from" class="text-xs text-muted-foreground">De</Label>
                <Input id="date_from" v-model="filters.date_from" type="date" />
            </div>

            <div class="grid gap-1">
                <Label for="date_to" class="text-xs text-muted-foreground">Até</Label>
                <Input id="date_to" v-model="filters.date_to" type="date" />
            </div>

            <div class="grid gap-1">
                <Label class="text-xs text-muted-foreground">Por página</Label>
                <Select v-model="filters.per_page">
                    <SelectTrigger class="w-24">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="25">25</SelectItem>
                        <SelectItem value="50">50</SelectItem>
                        <SelectItem value="100">100</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <Button v-if="hasFilters" variant="ghost" @click="clearFilters">
                <X class="h-4 w-4" />
                Limpar filtros
            </Button>
        </div>

        <div class="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-40">Data</TableHead>
                        <TableHead>Piso</TableHead>
                        <TableHead>Tipo</TableHead>
                        <TableHead>Documento</TableHead>
                        <TableHead class="text-right">Quantidade (m²)</TableHead>
                        <TableHead class="text-right">Físico após</TableHead>
                        <TableHead class="text-right">Comprometido após</TableHead>
                        <TableHead>Usuário</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-if="movements.data.length === 0">
                        <TableCell colspan="8" class="h-24 text-center text-muted-foreground">Nenhuma movimentação encontrada.</TableCell>
                    </TableRow>

                    <TableRow v-for="m in movements.data" :key="m.id">
                        <TableCell>{{ formatDateTime(m.created_at) }}</TableCell>
                        <TableCell>
                            <template v-if="m.floor">
                                <Link :href="floorShow(m.floor.id)" class="font-medium underline-offset-4 hover:underline">{{ m.floor.code }}</Link>
                                <span class="block max-w-xs truncate text-xs text-muted-foreground" :title="m.floor.description">
                                    {{ m.floor.description }} - {{ m.floor.reference }}
                                </span>
                            </template>
                            <span v-else>—</span>
                        </TableCell>
                        <TableCell>
                            {{ m.type.label }}
                            <span v-if="m.reason" class="block max-w-xs truncate text-xs text-muted-foreground" :title="m.reason">{{ m.reason }}</span>
                        </TableCell>
                        <TableCell>
                            <Link v-if="m.sale_id && m.document_number" :href="saleShow(m.sale_id)" class="underline-offset-4 hover:underline">
                                {{ m.document_number }}
                            </Link>
                            <Link
                                v-else-if="m.purchase_id && m.document_number"
                                :href="purchaseShow(m.purchase_id)"
                                class="underline-offset-4 hover:underline"
                            >
                                {{ m.document_number }}
                            </Link>
                            <span v-else>{{ m.document_number || '—' }}</span>
                        </TableCell>
                        <TableCell class="text-right font-medium tabular-nums" :class="qtyClass(m)">{{ qtyLabel(m) }}</TableCell>
                        <TableCell class="text-right tabular-nums">{{ formatM2(m.physical_balance_after) }}</TableCell>
                        <TableCell class="text-right tabular-nums">{{ formatM2(m.committed_balance_after) }}</TableCell>
                        <TableCell>{{ m.user?.name ?? '—' }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div v-if="movements.meta.total > 0" class="flex items-center justify-between text-sm text-muted-foreground">
            <span>Mostrando {{ movements.meta.from }}–{{ movements.meta.to }} de {{ movements.meta.total }}</span>

            <div class="flex items-center gap-2">
                <Button v-if="movements.links.prev" variant="outline" size="sm" as-child>
                    <Link :href="movements.links.prev" preserve-scroll>Anterior</Link>
                </Button>
                <Button v-else variant="outline" size="sm" disabled>Anterior</Button>

                <span>Página {{ movements.meta.current_page }} de {{ movements.meta.last_page }}</span>

                <Button v-if="movements.links.next" variant="outline" size="sm" as-child>
                    <Link :href="movements.links.next" preserve-scroll>Próxima</Link>
                </Button>
                <Button v-else variant="outline" size="sm" disabled>Próxima</Button>
            </div>
        </div>
    </div>
</template>