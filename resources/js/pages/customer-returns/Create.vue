<script setup lang="ts">
import FloorSearchInput from '@/components/floors/FloorSearchInput.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDateTime, formatM2, parseDecimal, toDecimalInput } from '@/lib/format';
import { store } from '@/routes/customer-returns';
import { index as movementsIndex } from '@/routes/movements';
import { show as saleShow } from '@/routes/sales';
import type { Floor, ReturnableItem, ReturnableSale } from '@/types/stock';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Plus, Trash2 } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';

const props = defineProps<{
    sale: ReturnableSale | null; // preenchido quando a devolução parte de uma venda
    floors: Floor[]; // usado só na devolução avulsa
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Devolução de cliente', href: '#' }],
    },
});

const form = useForm({
    sale_id: props.sale?.id ?? null,
    document_number: props.sale?.document_number ?? '',
    customer_name: '',
    reason: '',
});

// ---------- Devolução vinculada: quantidade por piso da venda ----------
const quantities = reactive<Record<number, string>>({});

const hasReturnable = (item: ReturnableItem) => parseDecimal(item.returnable) > 0;
const exceeds = (item: ReturnableItem) => parseDecimal(quantities[item.floor.id]) > parseDecimal(item.returnable);
const returnAll = (item: ReturnableItem) => {
    quantities[item.floor.id] = toDecimalInput(parseDecimal(item.returnable));
};
const returnEverything = () => props.sale?.items.filter(hasReturnable).forEach(returnAll);

const allReturned = computed(() => !!props.sale && !props.sale.items.some(hasReturnable));
const hasExceeded = computed(() => !!props.sale && props.sale.items.some(exceeds));

// ---------- Devolução avulsa: linhas livres ----------
interface Row {
    key: number;
    floor_id: string;
    boxes: string;
    quantity: string;
}

let nextKey = 1;
const newRow = (): Row => ({ key: nextKey++, floor_id: '', boxes: '', quantity: '' });
const rows = ref<Row[]>([newRow()]);

const floorOf = (row: Row): Floor | undefined => props.floors.find((f) => String(f.id) === row.floor_id);
const isTaken = (floorId: number, row: Row) => rows.value.some((r) => r.key !== row.key && r.floor_id === String(floorId));
const addRow = () => rows.value.push(newRow());
const removeRow = (row: Row) => {
    rows.value = rows.value.filter((r) => r.key !== row.key);
};

const onBoxesInput = (row: Row) => {
    const perBox = parseDecimal(floorOf(row)?.m2_per_box);
    row.quantity = row.boxes === '' ? '' : toDecimalInput(parseDecimal(row.boxes) * perBox);
};

const onFloorChange = (row: Row) => {
    const floor = floorOf(row);

    if (!floor) {
        row.boxes = '';
        row.quantity = '';
        return;
    }

    if (!floor.m2_per_box) {
        row.boxes = '';
        return;
    }

    if (row.boxes !== '') {
        onBoxesInput(row);
    }
};

const onQuantityInput = (row: Row) => {
    row.boxes = '';
};

// ---------- Comum ----------
const total = computed(() =>
    props.sale
        ? props.sale.items.reduce((sum, item) => sum + parseDecimal(quantities[item.floor.id]), 0)
        : rows.value.reduce((sum, row) => sum + parseDecimal(row.quantity), 0),
);

const error = (key: string): string | undefined => (form.errors as Record<string, string | undefined>)[key];

const submit = () => {
    const items = props.sale
        ? props.sale.items
              .filter((item) => parseDecimal(quantities[item.floor.id]) > 0)
              .map((item) => ({ floor_id: item.floor.id, quantity_m2: quantities[item.floor.id] }))
        : rows.value.map((row) => ({ floor_id: row.floor_id ? Number(row.floor_id) : null, quantity_m2: row.quantity }));

    form.transform((data) => ({
        ...data,
        customer_name: props.sale ? null : data.customer_name || null,
        items,
    })).post(store.url(), { preserveScroll: true });
};
</script>

<template>
    <Head title="Devolução de cliente" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <Heading
            :title="sale ? `Devolução da venda ${sale.document_number}` : 'Devolução avulsa'"
            :description="
                sale
                    ? 'Só é possível devolver, por piso, o que foi vendido e ainda não foi devolvido.'
                    : 'Devolução sem venda vinculada. Para devolver itens de uma venda, abra a venda e use “Registrar devolução”.'
            "
        />

        <form class="max-w-4xl space-y-8" @submit.prevent="submit">
            <dl v-if="sale" class="grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border p-4">
                    <dt class="text-sm text-muted-foreground">Venda</dt>
                    <dd class="font-medium">
                        <Link :href="saleShow(sale.id)" class="underline-offset-4 hover:underline">{{ sale.document_number }}</Link>
                    </dd>
                </div>
                <div class="rounded-xl border p-4">
                    <dt class="text-sm text-muted-foreground">Cliente</dt>
                    <dd class="font-medium">{{ sale.customer_name }}</dd>
                </div>
                <div class="rounded-xl border p-4">
                    <dt class="text-sm text-muted-foreground">Entregue em</dt>
                    <dd class="font-medium">{{ formatDateTime(sale.delivered_at) }}</dd>
                </div>
            </dl>

            <div class="grid gap-6 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="document_number">Número do documento da devolução</Label>
                    <Input id="document_number" v-model="form.document_number" autocomplete="off" />
                    <p v-if="sale" class="text-xs text-muted-foreground">Preenchido com o documento da venda; troque se a devolução tiver outro número.</p>
                    <InputError :message="form.errors.document_number" />
                </div>

                <div v-if="!sale" class="grid gap-2">
                    <Label for="customer_name">Cliente (opcional)</Label>
                    <Input id="customer_name" v-model="form.customer_name" autocomplete="off" />
                    <InputError :message="form.errors.customer_name" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="reason">Motivo da devolução</Label>
                <textarea
                    id="reason"
                    v-model="form.reason"
                    rows="3"
                    class="flex min-h-16 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                />
                <InputError :message="form.errors.reason" />
            </div>

            <!-- Devolução vinculada a uma venda -->
            <div v-if="sale" class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-medium">Itens da venda</h2>
                    <Button type="button" variant="outline" size="sm" :disabled="allReturned" @click="returnEverything">Devolver tudo</Button>
                </div>

                <div class="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Piso</TableHead>
                                <TableHead class="text-right">Vendido (m²)</TableHead>
                                <TableHead class="text-right">Já devolvido (m²)</TableHead>
                                <TableHead class="text-right">Pode devolver (m²)</TableHead>
                                <TableHead class="w-40">Devolver (m²)</TableHead>
                                <TableHead class="w-16" />
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow v-for="item in sale.items" :key="item.floor.id" class="align-top">
                                <TableCell>
                                    <span class="font-medium">{{ item.floor.code }}</span>
                                    <span class="block text-xs text-muted-foreground">
                                        {{ item.floor.description }}<template v-if="item.floor.reference"> - {{ item.floor.reference }}</template>
                                    </span>
                                </TableCell>
                                <TableCell class="text-right tabular-nums">{{ formatM2(item.sold) }}</TableCell>
                                <TableCell class="text-right tabular-nums">{{ formatM2(item.returned) }}</TableCell>
                                <TableCell class="text-right font-medium tabular-nums">{{ formatM2(item.returnable) }}</TableCell>
                                <TableCell>
                                    <Input
                                        v-model="quantities[item.floor.id]"
                                        inputmode="decimal"
                                        placeholder="0,00"
                                        autocomplete="off"
                                        :disabled="!hasReturnable(item)"
                                        :class="{ 'border-red-500': exceeds(item) }"
                                    />
                                    <p v-if="exceeds(item)" class="mt-1 text-xs text-red-600 dark:text-red-400">Maior que o saldo devolvível.</p>
                                </TableCell>
                                <TableCell class="text-right">
                                    <Button type="button" variant="ghost" size="sm" :disabled="!hasReturnable(item)" @click="returnAll(item)">Tudo</Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>

                        <TableFooter>
                            <TableRow>
                                <TableCell colspan="4" class="text-right font-medium">Total a devolver</TableCell>
                                <TableCell class="font-semibold tabular-nums">{{ formatM2(total) }} m²</TableCell>
                                <TableCell />
                            </TableRow>
                        </TableFooter>
                    </Table>
                </div>

                <p v-if="allReturned" class="text-sm text-muted-foreground">Todos os itens desta venda já foram devolvidos.</p>
                <InputError :message="form.errors.items" />
                <InputError :message="form.errors.stock" />
            </div>

            <!-- Devolução avulsa -->
            <div v-else class="space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-medium">Itens</h2>
                    <Button type="button" variant="outline" size="sm" @click="addRow">
                        <Plus class="h-4 w-4" />
                        Adicionar piso
                    </Button>
                </div>

                <div class="rounded-xl border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Piso</TableHead>
                                <TableHead class="w-32">Caixas</TableHead>
                                <TableHead class="w-40">Quantidade (m²)</TableHead>
                                <TableHead class="w-12" />
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            <TableRow v-for="(row, i) in rows" :key="row.key" class="align-top">
                                <TableCell>
                                    <FloorSearchInput
                                        v-model="row.floor_id"
                                        :floors="floors"
                                        :invalid="Boolean(error(`items.${i}.floor_id`))"
                                        :is-disabled="(floor) => isTaken(floor.id, row)"
                                        :disabled-label="() => 'já adicionado'"
                                        @update:model-value="onFloorChange(row)"
                                    >
                                        <template #meta="{ floor }">{{ formatM2(floor.physical_stock) }} m²</template>
                                    </FloorSearchInput>
                                    <InputError :message="error(`items.${i}.floor_id`)" />
                                </TableCell>

                                <TableCell>
                                    <Input
                                        v-model="row.boxes"
                                        inputmode="decimal"
                                        placeholder="—"
                                        :disabled="!floorOf(row)?.m2_per_box"
                                        :title="floorOf(row) && !floorOf(row)?.m2_per_box ? 'Piso sem m² por caixa cadastrado' : undefined"
                                        autocomplete="off"
                                        @input="onBoxesInput(row)"
                                    />
                                </TableCell>

                                <TableCell>
                                    <Input v-model="row.quantity" inputmode="decimal" placeholder="0,00" autocomplete="off" @input="onQuantityInput(row)" />
                                    <InputError :message="error(`items.${i}.quantity_m2`)" />
                                </TableCell>

                                <TableCell>
                                    <Button type="button" variant="ghost" size="icon" :disabled="rows.length === 1" aria-label="Remover item" @click="removeRow(row)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </TableCell>
                            </TableRow>
                        </TableBody>

                        <TableFooter>
                            <TableRow>
                                <TableCell colspan="2" class="text-right font-medium">Total</TableCell>
                                <TableCell class="font-semibold tabular-nums">{{ formatM2(total) }} m²</TableCell>
                                <TableCell />
                            </TableRow>
                        </TableFooter>
                    </Table>
                </div>

                <InputError :message="form.errors.items" />
                <InputError :message="form.errors.stock" />
            </div>

            <div class="flex items-center gap-3">
                <Button type="submit" :disabled="form.processing || allReturned || hasExceeded">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Registrar devolução
                </Button>

                <Button variant="secondary" as-child>
                    <Link :href="sale ? saleShow(sale.id) : movementsIndex()">Cancelar</Link>
                </Button>
            </div>
        </form>
    </div>
</template>