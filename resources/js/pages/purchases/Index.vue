<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDate, formatDateTime } from '@/lib/format';
import { create, index, show } from '@/routes/purchases';
import type { Paginated, Purchase } from '@/types/stock';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search } from '@lucide/vue';
import { reactive, watch } from 'vue';

const props = defineProps<{
    purchases: Paginated<Purchase>;
    filters: { search?: string | null; date_from?: string | null; date_to?: string | null };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Compras',
                href: index().url,
            },
        ],
    },
});

const filters = reactive({
    search: props.filters.search ?? '',
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
                date_from: filters.date_from || undefined,
                date_to: filters.date_to || undefined,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    }, 300);
});
</script>

<template>
    <Head title="Compras" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading title="Compras" description="Entradas de estoque por compra de fornecedor" />

            <Button as-child>
                <Link :href="create()">
                    <Plus class="h-4 w-4" />
                    Nova compra
                </Link>
            </Button>
        </div>

        <div class="flex flex-wrap items-end gap-4">
            <div class="relative w-full max-w-sm">
                <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="filters.search" class="pl-9" placeholder="Buscar por documento ou fornecedor" autocomplete="off" />
            </div>

            <div class="grid gap-1">
                <Label for="date_from" class="text-xs text-muted-foreground">Entrada de</Label>
                <Input id="date_from" v-model="filters.date_from" type="date" />
            </div>

            <div class="grid gap-1">
                <Label for="date_to" class="text-xs text-muted-foreground">Entrada até</Label>
                <Input id="date_to" v-model="filters.date_to" type="date" />
            </div>
        </div>

        <div class="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Documento</TableHead>
                        <TableHead>Fornecedor</TableHead>
                        <TableHead class="w-36">Data do documento</TableHead>
                        <TableHead class="w-40">Entrada em</TableHead>
                        <TableHead>Registrado por</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-if="purchases.data.length === 0">
                        <TableCell colspan="5" class="h-24 text-center text-muted-foreground">Nenhuma compra encontrada.</TableCell>
                    </TableRow>

                    <TableRow v-for="purchase in purchases.data" :key="purchase.id">
                        <TableCell class="font-medium">
                            <Link :href="show(purchase.id)" class="underline-offset-4 hover:underline">{{ purchase.document_number }}</Link>
                        </TableCell>
                        <TableCell>{{ purchase.supplier_name }}</TableCell>
                        <TableCell>{{ formatDate(purchase.document_date) }}</TableCell>
                        <TableCell>{{ formatDateTime(purchase.received_at) }}</TableCell>
                        <TableCell>{{ purchase.creator?.name ?? '—' }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div v-if="purchases.meta.total > 0" class="flex items-center justify-between text-sm text-muted-foreground">
            <span>Mostrando {{ purchases.meta.from }}–{{ purchases.meta.to }} de {{ purchases.meta.total }}</span>

            <div class="flex items-center gap-2">
                <Button v-if="purchases.links.prev" variant="outline" size="sm" as-child>
                    <Link :href="purchases.links.prev" preserve-scroll>Anterior</Link>
                </Button>
                <Button v-else variant="outline" size="sm" disabled>Anterior</Button>

                <span>Página {{ purchases.meta.current_page }} de {{ purchases.meta.last_page }}</span>

                <Button v-if="purchases.links.next" variant="outline" size="sm" as-child>
                    <Link :href="purchases.links.next" preserve-scroll>Próxima</Link>
                </Button>
                <Button v-else variant="outline" size="sm" disabled>Próxima</Button>
            </div>
        </div>
    </div>
</template>
