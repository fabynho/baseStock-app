<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDate, formatDateTime, formatM2 } from '@/lib/format';
import { create, index } from '@/routes/purchases';
import type { Purchase } from '@/types/stock';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    purchase: Purchase;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Compras',
                href: index().url,
            },
            {
                title: 'Detalhes',
                href: '#',
            },
        ],
    },
});

const items = computed(() => props.purchase.items ?? []);

const total = computed(() => items.value.reduce((sum, item) => sum + Number(item.quantity_m2), 0));
</script>

<template>
    <Head :title="`Compra ${purchase.document_number}`" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading :title="`Compra ${purchase.document_number}`" description="Entrada de estoque registrada" />

            <div class="flex items-center gap-2">
                <Button variant="secondary" as-child>
                    <Link :href="index()">Voltar</Link>
                </Button>
                <Button as-child>
                    <Link :href="create()">Nova compra</Link>
                </Button>
            </div>
        </div>

        <dl class="grid max-w-4xl gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border p-4">
                <dt class="text-sm text-muted-foreground">Fornecedor</dt>
                <dd class="font-medium">{{ purchase.supplier_name }}</dd>
            </div>
            <div class="rounded-xl border p-4">
                <dt class="text-sm text-muted-foreground">Data do documento</dt>
                <dd class="font-medium">{{ formatDate(purchase.document_date) }}</dd>
            </div>
            <div class="rounded-xl border p-4">
                <dt class="text-sm text-muted-foreground">Entrada em</dt>
                <dd class="font-medium">{{ formatDateTime(purchase.received_at) }}</dd>
            </div>
            <div class="rounded-xl border p-4">
                <dt class="text-sm text-muted-foreground">Registrado por</dt>
                <dd class="font-medium">{{ purchase.creator?.name ?? '—' }}</dd>
            </div>
        </dl>

        <div class="max-w-4xl rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-32">Código</TableHead>
                        <TableHead>Descrição</TableHead>
                        <TableHead class="w-40 text-right">Quantidade (m²)</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-for="item in items" :key="item.id">
                        <TableCell class="font-medium">{{ item.floor?.code ?? item.floor_id }}</TableCell>
                        <TableCell>{{ item.floor?.description ?? '—' }} - {{ item.floor?.reference ?? '—' }}</TableCell>
                        <TableCell class="text-right tabular-nums">{{ formatM2(item.quantity_m2) }}</TableCell>
                    </TableRow>
                </TableBody>

                <TableFooter>
                    <TableRow>
                        <TableCell colspan="2" class="text-right font-medium">Total</TableCell>
                        <TableCell class="text-right font-semibold tabular-nums">{{ formatM2(total) }}</TableCell>
                    </TableRow>
                </TableFooter>
            </Table>
        </div>

        <div v-if="purchase.notes" class="max-w-4xl rounded-xl border p-4">
            <p class="text-sm text-muted-foreground">Observações</p>
            <p class="whitespace-pre-line">{{ purchase.notes }}</p>
        </div>
    </div>
</template>
