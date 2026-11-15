<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatM2, parseDecimal, toDecimalInput } from '@/lib/format';
import { index, store } from '@/routes/purchases';
import type { Floor } from '@/types/stock';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';

const props = defineProps<{
    floors: Floor[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Compras',
                href: index().url,
            },
            {
                title: 'Nova compra',
                href: '#',
            },
        ],
    },
});

// Cabeçalho da compra. `received_at` vazio = agora (o backend usa now()).
const form = useForm({
    document_number: '',
    supplier_name: '',
    document_date: '',
    received_at: '',
    notes: '',
});

// Itens ficam fora do useForm para permitir campos auxiliares (caixas) sem enviá-los.
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

// Evita o mesmo piso em duas linhas (o backend também rejeita).
const isTaken = (floorId: number, row: Row) => rows.value.some((r) => r.key !== row.key && r.floor_id === String(floorId));

const addRow = () => rows.value.push(newRow());
const removeRow = (row: Row) => {
    rows.value = rows.value.filter((r) => r.key !== row.key);
};

// Calculadora auxiliar: caixas × m² por caixa → m².
const onBoxesInput = (row: Row) => {
    const perBox = parseDecimal(floorOf(row)?.m2_per_box);
    row.quantity = row.boxes === '' ? '' : toDecimalInput(parseDecimal(row.boxes) * perBox);
};

const onQuantityInput = (row: Row) => {
    row.boxes = '';
};

const total = computed(() => rows.value.reduce((sum, row) => sum + parseDecimal(row.quantity), 0));

// Erros do Laravel chegam como "items.0.floor_id".
const error = (key: string): string | undefined => (form.errors as Record<string, string | undefined>)[key];

const submit = () => {
    form.transform((data) => ({
        ...data,
        items: rows.value.map((row) => ({
            floor_id: row.floor_id ? Number(row.floor_id) : null,
            quantity_m2: row.quantity,
        })),
    })).post(store.url(), { preserveScroll: true });
};
</script>

<template>
    <Head title="Nova compra" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <Heading title="Nova compra" description="Registre a entrada de pisos de um fornecedor. Cada item soma no estoque físico." />

        <form class="max-w-4xl space-y-8" @submit.prevent="submit">
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="document_number">Número do documento (NF)</Label>
                    <Input id="document_number" v-model="form.document_number" autocomplete="off" />
                    <InputError :message="form.errors.document_number" />
                </div>

                <div class="grid gap-2">
                    <Label for="supplier_name">Fornecedor</Label>
                    <Input id="supplier_name" v-model="form.supplier_name" autocomplete="off" />
                    <InputError :message="form.errors.supplier_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="document_date">Data do documento</Label>
                    <Input id="document_date" v-model="form.document_date" type="date" />
                    <InputError :message="form.errors.document_date" />
                </div>

                <div class="grid gap-2">
                    <Label for="received_at">Entrada em (opcional)</Label>
                    <Input id="received_at" v-model="form.received_at" type="datetime-local" />
                    <p class="text-xs text-muted-foreground">Em branco = data e hora do registro.</p>
                    <InputError :message="form.errors.received_at" />
                </div>
            </div>

            <div class="space-y-3">
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
                                    <Select v-model="row.floor_id" @update:model-value="onBoxesInput(row)">
                                        <SelectTrigger class="w-full">
                                            <SelectValue placeholder="Selecione o piso" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="floor in floors" :key="floor.id" :value="String(floor.id)" :disabled="isTaken(floor.id, row)">
                                                {{ floor.code }} — {{ floor.description }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <p v-if="floorOf(row)" class="mt-1 text-xs text-muted-foreground">
                                        Físico atual: {{ formatM2(floorOf(row)?.physical_stock) }} m²
                                    </p>
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
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="icon"
                                        :disabled="rows.length === 1"
                                        aria-label="Remover item"
                                        @click="removeRow(row)"
                                    >
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

            <div class="grid gap-2">
                <Label for="notes">Observações</Label>
                <textarea
                    id="notes"
                    v-model="form.notes"
                    rows="3"
                    class="flex min-h-16 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                />
                <InputError :message="form.errors.notes" />
            </div>

            <div class="flex items-center gap-3">
                <Button type="submit" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Registrar entrada
                </Button>

                <Button variant="secondary" as-child>
                    <Link :href="index()">Cancelar</Link>
                </Button>
            </div>
        </form>
    </div>
</template>
