<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatM2 } from '@/lib/format';
import { index } from '@/routes/stock';
import type { Floor } from '@/types/stock';
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertTriangle, Boxes, PackageCheck, RotateCcw, Search, Truck } from '@lucide/vue';
import { onBeforeUnmount, ref, watch } from 'vue';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedFloors {
    data: Floor[];
    links?: PaginationLink[];
    meta?: {
        current_page: number;
        last_page: number;
        from: number | null;
        to: number | null;
        total: number;
        links: PaginationLink[];
    };
}

interface Filters {
    search: string | null;
    below_minimum: boolean;
    include_inactive: boolean;
}

interface Stats {
    pending_deliveries: number;
    below_minimum: number;
}

const props = defineProps<{
    floors: PaginatedFloors;
    filters: Filters;
    stats: Stats;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Posição de Estoque',
                href: index().url,
            },
        ],
    },
});

const search = ref(props.filters.search ?? '');
const belowMinimum = ref(Boolean(props.filters.below_minimum));
const includeInactive = ref(Boolean(props.filters.include_inactive));

let searchTimeout: ReturnType<typeof setTimeout> | null = null;

const visitWithFilters = (overrides: Partial<Filters> = {}) => {
    const nextSearch = (overrides.search !== undefined ? overrides.search : search.value)?.trim() ?? '';
    const nextBelowMin = overrides.below_minimum !== undefined ? overrides.below_minimum : belowMinimum.value;
    const nextInactive = overrides.include_inactive !== undefined ? overrides.include_inactive : includeInactive.value;

    router.get(
        index().url,
        {
            search: nextSearch || undefined,
            below_minimum: nextBelowMin || undefined,
            include_inactive: nextInactive || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

watch(search, (value) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    searchTimeout = setTimeout(() => {
        visitWithFilters({ search: value });
    }, 300);
});

const toggleBelowMinimum = () => {
    belowMinimum.value = !belowMinimum.value;
    visitWithFilters({ below_minimum: belowMinimum.value });
};

const toggleIncludeInactive = () => {
    includeInactive.value = !includeInactive.value;
    visitWithFilters({ include_inactive: includeInactive.value });
};

const resetFilters = () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
    search.value = '';
    belowMinimum.value = false;
    includeInactive.value = false;
    router.get(index().url, {}, { preserveState: true, preserveScroll: true, replace: true });
};

const hasActiveFilters = () =>
    Boolean(search.value.trim()) || belowMinimum.value || includeInactive.value;

const floorItems = () => props.floors.data ?? [];
const paginationLinks = () => props.floors.meta?.links ?? props.floors.links ?? [];

const cleanLabel = (label: string) =>
    label
        .replace(/&laquo;/g, '«')
        .replace(/&raquo;/g, '»')
        .replace(/<[^>]*>/g, '')
        .trim();

onBeforeUnmount(() => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }
});
</script>

<template>
    <Head title="Posição de Estoque" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <Heading
            title="Posição de Estoque"
            description="Acompanhe em tempo real o saldo físico, comprometido e disponível por piso."
        />

        <!-- Indicadores rápidos -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="flex items-center justify-between rounded-xl border bg-card p-4 text-card-foreground shadow-xs">
                <div class="space-y-1">
                    <p class="text-xs font-medium text-muted-foreground">Entregas pendentes</p>
                    <p class="text-2xl font-semibold tabular-nums">{{ stats.pending_deliveries }}</p>
                    <p class="text-xs text-muted-foreground">Vendas aguardando entrega/retirada</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                    <Truck class="h-5 w-5" />
                </div>
            </div>

            <button
                type="button"
                class="flex items-center justify-between rounded-xl border bg-card p-4 text-left text-card-foreground shadow-xs transition-colors hover:bg-muted/40"
                :class="belowMinimum ? 'border-amber-500/60 ring-1 ring-amber-500/40' : ''"
                @click="toggleBelowMinimum"
            >
                <div class="space-y-1">
                    <p class="text-xs font-medium text-muted-foreground">Abaixo do estoque mínimo</p>
                    <p class="text-2xl font-semibold tabular-nums" :class="stats.below_minimum > 0 ? 'text-amber-600 dark:text-amber-400' : ''">
                        {{ stats.below_minimum }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        {{ belowMinimum ? 'Filtro ativo — clique para limpar' : 'Clique para filtrar pisos críticos' }}
                    </p>
                </div>
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg"
                    :class="
                        stats.below_minimum > 0
                            ? 'bg-amber-500/10 text-amber-600 dark:text-amber-400'
                            : 'bg-muted text-muted-foreground'
                    "
                >
                    <AlertTriangle class="h-5 w-5" />
                </div>
            </button>

            <div class="flex items-center justify-between rounded-xl border bg-card p-4 text-card-foreground shadow-xs sm:col-span-2 lg:col-span-1">
                <div class="space-y-1">
                    <p class="text-xs font-medium text-muted-foreground">Pisos listados na página</p>
                    <p class="text-2xl font-semibold tabular-nums">{{ floorItems().length }}</p>
                    <p class="text-xs text-muted-foreground">
                        Total encontrado: {{ props.floors.meta?.total ?? floorItems().length }}
                    </p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-muted text-muted-foreground">
                    <Boxes class="h-5 w-5" />
                </div>
            </div>
        </div>

        <!-- Barra de filtros -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative w-full sm:max-w-sm">
                <Search class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Buscar por código, referência, descrição ou marca..."
                    autocomplete="off"
                    class="pl-9"
                />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <Button
                    type="button"
                    :variant="belowMinimum ? 'default' : 'outline'"
                    size="sm"
                    @click="toggleBelowMinimum"
                >
                    <AlertTriangle class="h-4 w-4" />
                    Abaixo do mínimo
                </Button>

                <Button
                    type="button"
                    :variant="includeInactive ? 'default' : 'outline'"
                    size="sm"
                    @click="toggleIncludeInactive"
                >
                    <PackageCheck class="h-4 w-4" />
                    Incluir inativos
                </Button>

                <Button
                    v-if="hasActiveFilters()"
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="resetFilters"
                >
                    <RotateCcw class="h-4 w-4" />
                    Limpar filtros
                </Button>
            </div>
        </div>

        <!-- Tabela de posição -->
        <div class="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Piso</TableHead>
                        <TableHead>Marca / Ref.</TableHead>
                        <TableHead class="text-right">Físico</TableHead>
                        <TableHead class="text-right">Comprometido</TableHead>
                        <TableHead class="text-right">Disponível</TableHead>
                        <TableHead class="text-right">Mínimo</TableHead>
                        <TableHead class="text-center">Status</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-if="floorItems().length === 0">
                        <TableCell colspan="7" class="py-10 text-center text-sm text-muted-foreground">
                            Nenhum piso encontrado com os filtros selecionados.
                        </TableCell>
                    </TableRow>

                    <TableRow
                        v-for="floor in floorItems()"
                        :key="floor.id"
                        :class="!floor.is_active ? 'opacity-60' : ''"
                    >
                        <TableCell>
                            <div class="font-medium">
                                {{ floor.code }} — {{ floor.description }}
                            </div>
                            <div v-if="floor.m2_per_box" class="text-xs text-muted-foreground tabular-nums">
                                {{ formatM2(floor.m2_per_box) }} m²/caixa
                            </div>
                        </TableCell>

                        <TableCell class="text-sm text-muted-foreground">
                            <span v-if="floor.brand">{{ floor.brand }}</span>
                            <span v-if="floor.brand && floor.reference"> · </span>
                            <span v-if="floor.reference">Ref.: {{ floor.reference }}</span>
                            <span v-if="!floor.brand && !floor.reference">—</span>
                        </TableCell>

                        <TableCell class="text-right tabular-nums">
                            {{ formatM2(floor.physical_stock) }} m²
                        </TableCell>

                        <TableCell class="text-right tabular-nums text-muted-foreground">
                            {{ formatM2(floor.committed_stock) }} m²
                        </TableCell>

                        <TableCell
                            class="text-right font-semibold tabular-nums"
                            :class="
                                floor.below_minimum
                                    ? 'text-amber-600 dark:text-amber-400'
                                    : Number(floor.available_stock) <= 0
                                      ? 'text-red-600 dark:text-red-400'
                                      : ''
                            "
                        >
                            {{ formatM2(floor.available_stock) }} m²
                        </TableCell>

                        <TableCell class="text-right tabular-nums text-muted-foreground">
                            {{ floor.min_stock_alert !== null ? `${formatM2(floor.min_stock_alert)} m²` : '—' }}
                        </TableCell>

                        <TableCell class="text-center">
                            <div class="inline-flex flex-wrap items-center justify-center gap-1.5">
                                <span
                                    v-if="!floor.is_active"
                                    class="inline-flex items-center rounded-md bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground"
                                >
                                    Inativo
                                </span>
                                <span
                                    v-if="floor.below_minimum"
                                    class="inline-flex items-center gap-1 rounded-md bg-amber-500/15 px-2 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-300"
                                >
                                    <AlertTriangle class="h-3 w-3" />
                                    Abaixo do mín.
                                </span>
                                <span
                                    v-if="floor.is_active && !floor.below_minimum"
                                    class="inline-flex items-center rounded-md bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-300"
                                >
                                    Normal
                                </span>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Paginação -->
        <div
            v-if="paginationLinks().length > 3"
            class="flex flex-wrap items-center justify-between gap-2 text-sm text-muted-foreground"
        >
            <span v-if="props.floors.meta?.total !== undefined" class="tabular-nums">
                Exibindo {{ props.floors.meta.from ?? 0 }} a {{ props.floors.meta.to ?? 0 }} de {{ props.floors.meta.total }}
            </span>

            <div class="flex flex-wrap items-center gap-1">
                <template v-for="(link, idx) in paginationLinks()" :key="idx">
                    <Button
                        v-if="link.url"
                        :variant="link.active ? 'default' : 'outline'"
                        size="sm"
                        as-child
                    >
                        <Link :href="link.url" preserve-state preserve-scroll>
                            {{ cleanLabel(link.label) }}
                        </Link>
                    </Button>
                    <Button
                        v-else
                        variant="outline"
                        size="sm"
                        disabled
                    >
                        {{ cleanLabel(link.label) }}
                    </Button>
                </template>
            </div>
        </div>
    </div>
</template>