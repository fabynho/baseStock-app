<script setup lang="ts">
import FloorSearchInput from '@/components/floors/FloorSearchInput.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatM2, parseDecimal, toDecimalInput } from '@/lib/format';
import { index as movementsIndex } from '@/routes/movements';
import { store } from '@/routes/breakages';
import type { Floor } from '@/types/stock';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from '@lucide/vue';
import { computed, ref } from 'vue';

const props = defineProps<{
    floors: Floor[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Movimentações',
                href: movementsIndex().url,
            },
            {
                title: 'Nova quebra / avaria',
                href: '#',
            },
        ],
    },
});

const form = useForm({
    floor_id: '',
    quantity_m2: '',
    reason: '',
});

// Campo auxiliar apenas na UI para converter caixas em m² sem sujar o payload do form.
const boxes = ref('');

const selectedFloor = computed<Floor | undefined>(() =>
    props.floors.find((f) => String(f.id) === form.floor_id),
);

const onBoxesInput = () => {
    const perBox = parseDecimal(selectedFloor.value?.m2_per_box);
    form.quantity_m2 = boxes.value === '' ? '' : toDecimalInput(parseDecimal(boxes.value) * perBox);
};

const onFloorChange = () => {
    const floor = selectedFloor.value;

    if (!floor) {
        boxes.value = '';
        form.quantity_m2 = '';
        return;
    }

    if (!floor.m2_per_box) {
        boxes.value = '';
        return;
    }

    if (boxes.value !== '') {
        onBoxesInput();
    }
};

const onQuantityInput = () => {
    boxes.value = '';
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        floor_id: data.floor_id ? Number(data.floor_id) : null,
    })).post(store.url(), { preserveScroll: true });
};
</script>

<template>
    <Head title="Nova quebra / avaria" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <Heading
            title="Nova quebra / avaria"
            description="Registre a baixa de pisos avariados ou quebrados. O desconto ocorre apenas sobre o estoque disponível."
        />

        <form class="max-w-4xl space-y-8" @submit.prevent="submit">
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="grid gap-2 sm:col-span-2">
                    <Label for="floor_id">Piso</Label>
                    <FloorSearchInput
                        id="floor_id"
                        v-model="form.floor_id"
                        :floors="floors"
                        :invalid="Boolean(form.errors.floor_id)"
                        @update:model-value="onFloorChange"
                    >
                        <template #meta="{ floor }">
                            Disp.: {{ formatM2(floor.available_stock) }} m²
                        </template>
                    </FloorSearchInput>

                    <p v-if="selectedFloor" class="text-xs text-muted-foreground">
                        Disponível: {{ formatM2(selectedFloor.available_stock) }} m²
                        <template v-if="selectedFloor.physical_stock !== undefined">
                            · Físico: {{ formatM2(selectedFloor.physical_stock) }} m²
                        </template>
                    </p>

                    <InputError :message="form.errors.floor_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="boxes">Caixas (opcional)</Label>
                    <Input
                        id="boxes"
                        v-model="boxes"
                        inputmode="decimal"
                        placeholder="—"
                        :disabled="!selectedFloor?.m2_per_box"
                        :title="selectedFloor && !selectedFloor?.m2_per_box ? 'Piso sem m² por caixa cadastrado' : undefined"
                        autocomplete="off"
                        @input="onBoxesInput"
                    />
                    <p class="text-xs text-muted-foreground">Calcula automaticamente a quantidade em m².</p>
                </div>

                <div class="grid gap-2">
                    <Label for="quantity_m2">Quantidade (m²)</Label>
                    <Input
                        id="quantity_m2"
                        v-model="form.quantity_m2"
                        inputmode="decimal"
                        placeholder="0,00"
                        autocomplete="off"
                        @input="onQuantityInput"
                    />
                    <InputError :message="form.errors.quantity_m2" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="reason">Motivo da quebra / avaria</Label>
                <textarea
                    id="reason"
                    v-model="form.reason"
                    rows="3"
                    placeholder="Descreva o ocorrido (ex.: queda durante descarga, borda lascada no manuseio...)"
                    class="flex min-h-16 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                />
                <InputError :message="form.errors.reason" />
            </div>

            <InputError :message="(form.errors as Record<string, string | undefined>).stock" />

            <div class="flex items-center gap-3">
                <Button type="submit" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Registrar baixa
                </Button>

                <Button variant="secondary" as-child>
                    <Link :href="movementsIndex()">Cancelar</Link>
                </Button>
            </div>
        </form>
    </div>
</template>