<script setup lang="ts">
import FloorSearchInput from '@/components/floors/FloorSearchInput.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatM2, parseDecimal, toDecimalInput } from '@/lib/format';
import { index, store } from '@/routes/sales';
import type { EnumOption, Floor, Seller } from '@/types/stock';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';

const props = defineProps<{
    sellers: Seller[];
    floors: Floor[];
    deliveryTypes: EnumOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Vendas',
                href: index().url,
            },
            {
                title: 'Nova venda',
                href: '#',
            },
        ],
    },
});

// Cabeçalho da venda. `sold_at` vazio = agora (o backend usa now()).
const form = useForm({
    document_number: '',
    customer_name: '',
    seller_id: '',
    delivery_type: '',
    sold_at: '',
    notes: '',
});

// O tipo de entrega define o efeito no estoque — por isso não há valor pré-selecionado.
const deliveryHint = computed(() => {
    if (form.delivery_type === 'immediate') {
        return 'Retirada imediata: baixa direta do estoque físico agora.';
    }
    if (form.delivery_type === 'scheduled_delivery' || form.delivery_type === 'customer_pickup') {
        return 'Reserva o estoque (comprometido) até a entrega/retirada ser confirmada.';
    }
    return null;
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

const hasStock = (floor: Floor) => parseDecimal(floor.available_stock) > 0;

// Aviso de UX apenas: a checagem real (com lock) é do backend.
const exceeds = (row: Row) => {
    const floor = floorOf(row);
    return !!floor && parseDecimal(row.quantity) > parseDecimal(floor.available_stock);
};

const addRow = () => rows.value.push(newRow());
const removeRow = (row: Row) => {
    rows.value = rows.value.filter((r) => r.key !== row.key);
};

// Calculadora auxiliar: caixas × m² por caixa → m².
const onBoxesInput = (row: Row) => {
    const perBox = parseDecimal(floorOf(row)?.m2_per_box);
    row.quantity = row.boxes === '' ? '' : toDecimalInput(parseDecimal(row.boxes) * perBox);
};

// Trata tanto a troca de piso quanto o clique no botão limpar (X) do FloorSearchInput.
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

const total = computed(() => rows.value.reduce((sum, row) => sum + parseDecimal(row.quantity), 0));

// Erros do Laravel chegam como "items.0.floor_id".
const error = (key: string): string | undefined => (form.errors as Record<string, string | undefined>)[key];

const submit = () => {
    form.transform((data) => ({
        ...data,
        seller_id: data.seller_id ? Number(data.seller_id) : null,
        items: rows.value.map((row) => ({
            floor_id: row.floor_id ? Number(row.floor_id) : null,
            quantity_m2: row.quantity,
        })),
    })).post(store.url(), { preserveScroll: true });
};
</script>

<template>
    <Head title="Nova venda" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <Heading title="Nova venda" description="Registre a saída de pisos vinculada ao documento do sistema principal." />

        <form class="max-w-4xl space-y-8" @submit.prevent="submit">
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="document_number">Número do documento</Label>
                    <Input id="document_number" v-model="form.document_number" autocomplete="off" />
                    <InputError :message="form.errors.document_number" />
                </div>

                <div class="grid gap-2">
                    <Label for="customer_name">Cliente</Label>
                    <Input id="customer_name" v-model="form.customer_name" autocomplete="off" />
                    <InputError :message="form.errors.customer_name" />
                </div>

                <div class="grid gap-2">
                    <Label>Vendedor</Label>
                    <Select v-model="form.seller_id">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Selecione o vendedor" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="seller in sellers" :key="seller.id" :value="String(seller.id)">{{ seller.name }}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.seller_id" />
                </div>

                <div class="grid gap-2">
                    <Label>Tipo de entrega</Label>
                    <Select v-model="form.delivery_type">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Selecione o tipo" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="type in deliveryTypes" :key="type.value" :value="type.value">{{ type.label }}</SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="deliveryHint" class="text-xs text-muted-foreground">{{ deliveryHint }}</p>
                    <InputError :message="form.errors.delivery_type" />
                </div>

                <div class="grid gap-2">
                    <Label for="sold_at">Data da venda (opcional)</Label>
                    <Input id="sold_at" v-model="form.sold_at" type="datetime-local" />
                    <p class="text-xs text-muted-foreground">Em branco = data e hora do registro.</p>
                    <InputError :message="form.errors.sold_at" />
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
                                    <FloorSearchInput
                                        v-model="row.floor_id"
                                        :floors="floors"
                                        :invalid="Boolean(error(`items.${i}.floor_id`))"
                                        :is-disabled="(floor) => isTaken(floor.id, row) || !hasStock(floor)"
                                        :disabled-label="(floor) => (isTaken(floor.id, row) ? 'já adicionado' : 'sem estoque')"
                                        @update:model-value="onFloorChange(row)"
                                    >
                                        <template #meta="{ floor }">
                                            disp. {{ formatM2(floor.available_stock) }} m²
                                        </template>
                                    </FloorSearchInput>
                                    <p v-if="floorOf(row)" class="mt-1 text-xs text-muted-foreground">
                                        Disponível: {{ formatM2(floorOf(row)?.available_stock) }} m² · Físico: {{ formatM2(floorOf(row)?.physical_stock) }} m² · Comprometido:
                                        {{ formatM2(floorOf(row)?.committed_stock) }} m²
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
                                    <Input
                                        v-model="row.quantity"
                                        inputmode="decimal"
                                        placeholder="0,00"
                                        autocomplete="off"
                                        :class="{ 'border-red-500': exceeds(row) }"
                                        @input="onQuantityInput(row)"
                                    />
                                    <p v-if="exceeds(row)" class="mt-1 text-xs text-red-600 dark:text-red-400">Maior que o disponível.</p>
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
                <!-- Regras de estoque (ex.: disponível insuficiente) vindas do StockService -->
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
                    Registrar venda
                </Button>

                <Button variant="secondary" as-child>
                    <Link :href="index()">Cancelar</Link>
                </Button>
            </div>
        </form>
    </div>
</template>