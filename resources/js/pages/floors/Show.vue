<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDateTime, formatM2 } from '@/lib/format';
import { edit, index, show } from '@/routes/floors';
import { show as purchaseShow } from '@/routes/purchases';
import { show as saleShow } from '@/routes/sales';
import type { Floor, Paginated, StockMovement } from '@/types/stock';
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil } from '@lucide/vue';

const props = defineProps<{
    floor: Floor;
    movements: Paginated<StockMovement>;
    filters: { kind: string | null };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Pisos', href: index().url },
            { title: 'Detalhes', href: '#' },
        ],
    },
});

const kinds = [
    { value: null, label: 'Todas' },
    { value: 'in', label: 'Entradas' },
    { value: 'out', label: 'Saídas' },
    { value: 'commit', label: 'Reservas' },
] as const;

const setKind = (kind: string | null) => {
    router.get(
        show(props.floor.id).url,
        { kind: kind ?? undefined },
        { preserveState: true, preserveScroll: true, replace: true, only: ['movements', 'filters'] },
    );
};

// Aparência por tipo: entrada (+), saída (−), reserva/liberação (âmbar) e ajuste (sinal do valor).
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

const qtyLabel = (m: StockMovement) => {
    const abs = formatM2(String(m.quantity_m2).replace('-', ''));
    return `${isPositive(m) ? '+' : '−'}${abs}`;
};

const qtyClass = (m: StockMovement) => {
    switch (effectOf(m)) {
        case 'in':
            return 'text-emerald-600 dark:text-emerald-400';
        case 'out':
            return 'text-red-600 dark:text-red-400';
        case 'commit':
            return 'text-amber-600 dark:text-amber-400';
        default:
            return isPositive(m) ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400';
    }
};
</script>

<template>
    <Head :title="`Piso ${floor.code}`" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading :title="`Piso ${floor.code}`" description="Dados do piso, saldos atuais e histórico de movimentações" />

            <div class="flex items-center gap-2">
                <Button variant="secondary" as-child>
                    <Link :href="index()">Voltar</Link>
                </Button>
                <Button as-child>
                    <Link :href="edit(floor.id)">
                        <Pencil class="h-4 w-4" />
                        Editar
                    </Link>
                </Button>
            </div>
        </div>

        <!-- Card principal -->
        <div class="space-y-6 rounded-xl border p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold">{{ floor.description }}</h2>
                    <p class="text-sm text-muted-foreground">Código {{ floor.code }}</p>
                </div>

                <div class="flex items-center gap-2">
                    <Badge v-if="floor.below_minimum" variant="destructive">Abaixo do mínimo</Badge>
                    <Badge :variant="floor.is_active ? 'default' : 'secondary'">
                        {{ floor.is_active ? 'Ativo' : 'Inativo' }}
                    </Badge>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Físico (m²)</p>
                    <p class="text-2xl font-semibold tabular-nums">{{ formatM2(floor.physical_stock) }}</p>
                </div>
                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Comprometido (m²)</p>
                    <p class="text-2xl font-semibold tabular-nums">{{ formatM2(floor.committed_stock) }}</p>
                </div>
                <div class="rounded-xl border p-4">
                    <p class="text-sm text-muted-foreground">Disponível (m²)</p>
                    <p class="text-2xl font-semibold tabular-nums" :class="{ 'text-red-600 dark:text-red-400': floor.below_minimum }">
                        {{ formatM2(floor.available_stock) }}
                    </p>
                </div>
            </div>

            <dl class="grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <dt class="text-muted-foreground">Referência de fábrica</dt>
                    <dd class="font-medium">{{ floor.reference || '—' }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Marca / fabricante</dt>
                    <dd class="font-medium">{{ floor.brand || '—' }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">m² por caixa</dt>
                    <dd class="font-medium tabular-nums">{{ floor.m2_per_box ? formatM2(floor.m2_per_box) : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-muted-foreground">Estoque mínimo (m²)</dt>
                    <dd class="font-medium tabular-nums">{{ floor.min_stock_alert ? formatM2(floor.min_stock_alert) : '—' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Card do histórico -->
        <div class="space-y-4 rounded-xl border p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="font-medium">Histórico de movimentações</h2>

                <div class="flex items-center gap-1">
                    <Button
                        v-for="k in kinds"
                        :key="k.label"
                        size="sm"
                        :variant="(filters.kind ?? null) === k.value ? 'default' : 'outline'"
                        @click="setKind(k.value)"
                    >
                        {{ k.label }}
                    </Button>
                </div>
            </div>

            <div class="rounded-xl border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead class="w-40">Data</TableHead>
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
                            <TableCell colspan="7" class="h-24 text-center text-muted-foreground">Nenhuma movimentação encontrada.</TableCell>
                        </TableRow>

                        <TableRow v-for="m in movements.data" :key="m.id">
                            <TableCell>{{ formatDateTime(m.created_at) }}</TableCell>
                            <TableCell>
                                {{ m.type.label }}
                                <span v-if="m.reason" class="block max-w-xs truncate text-xs text-muted-foreground" :title="m.reason">{{ m.reason }}</span>
                            </TableCell>
                            <TableCell>
                                <Link
                                    v-if="m.sale_id && m.document_number"
                                    :href="saleShow(m.sale_id)"
                                    class="underline-offset-4 hover:underline"
                                >
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
                        <Link :href="movements.links.prev" preserve-scroll :only="['movements', 'filters']">Anterior</Link>
                    </Button>
                    <Button v-else variant="outline" size="sm" disabled>Anterior</Button>

                    <span>Página {{ movements.meta.current_page }} de {{ movements.meta.last_page }}</span>

                    <Button v-if="movements.links.next" variant="outline" size="sm" as-child>
                        <Link :href="movements.links.next" preserve-scroll :only="['movements', 'filters']">Próxima</Link>
                    </Button>
                    <Button v-else variant="outline" size="sm" disabled>Próxima</Button>
                </div>
            </div>
        </div>
    </div>
</template>