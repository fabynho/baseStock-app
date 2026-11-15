<script setup lang="ts">
import FloorController from '@/actions/App/Http/Controllers/FloorController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatM2 } from '@/lib/format';
import { index } from '@/routes/floors';
import type { Floor } from '@/types/stock';
import { Form, Head, Link } from '@inertiajs/vue3';
import { LoaderCircle } from '@lucide/vue';
import { computed, ref } from 'vue';

// Criação: sem prop `floor`. Edição: `floor` vem do FloorController@edit.
const props = defineProps<{
    floor?: Floor;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Pisos',
                href: index().url,
            },
        ],
    },
});

const isEdit = computed(() => !!props.floor);

const formAction = computed(() => (props.floor ? FloorController.update.form.patch(props.floor.id) : FloorController.store.form()));

// Checkbox desmarcado não vai no envio do formulário, então o valor segue num input hidden (0/1).
const active = ref(props.floor?.is_active ?? true);
</script>

<template>
    <Head :title="isEdit ? 'Editar piso' : 'Novo piso'" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <Heading
            :title="isEdit ? `Editar piso ${floor?.code}` : 'Novo piso'"
            :description="isEdit ? 'Atualize os dados cadastrais do piso' : 'Cadastre um novo piso. O estoque inicial entra por uma compra ou ajuste manual.'"
        />

        <!-- Saldos: somente leitura. Só mudam por movimentações (compra, venda, avaria, ajuste...). -->
        <div v-if="floor" class="grid max-w-2xl gap-4 sm:grid-cols-3">
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Físico (m²)</p>
                <p class="text-xl font-semibold tabular-nums">{{ formatM2(floor.physical_stock) }}</p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Comprometido (m²)</p>
                <p class="text-xl font-semibold tabular-nums">{{ formatM2(floor.committed_stock) }}</p>
            </div>
            <div class="rounded-xl border p-4">
                <p class="text-sm text-muted-foreground">Disponível (m²)</p>
                <p class="text-xl font-semibold tabular-nums" :class="{ 'text-red-600 dark:text-red-400': floor.below_minimum }">
                    {{ formatM2(floor.available_stock) }}
                </p>
            </div>
        </div>

        <Form v-bind="formAction" class="max-w-2xl space-y-6" v-slot="{ errors, processing }">
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="code">Código</Label>
                    <Input id="code" name="code" :default-value="floor?.code ?? ''" placeholder="Código interno" autocomplete="off" />
                    <InputError :message="errors.code" />
                </div>

                <div class="grid gap-2">
                    <Label for="reference">Referência de fábrica</Label>
                    <Input id="reference" name="reference" :default-value="floor?.reference ?? ''" autocomplete="off" />
                    <InputError :message="errors.reference" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="description">Descrição</Label>
                <Input id="description" name="description" :default-value="floor?.description ?? ''" placeholder="Nome / descrição completa do piso" autocomplete="off" />
                <InputError :message="errors.description" />
            </div>

            <div class="grid gap-6 sm:grid-cols-3">
                <div class="grid gap-2">
                    <Label for="brand">Marca / fabricante</Label>
                    <Input id="brand" name="brand" :default-value="floor?.brand ?? ''" autocomplete="off" />
                    <InputError :message="errors.brand" />
                </div>

                <div class="grid gap-2">
                    <Label for="m2_per_box">m² por caixa</Label>
                    <Input id="m2_per_box" name="m2_per_box" inputmode="decimal" :default-value="floor?.m2_per_box ?? ''" placeholder="Opcional" autocomplete="off" />
                    <InputError :message="errors.m2_per_box" />
                </div>

                <div class="grid gap-2">
                    <Label for="min_stock_alert">Estoque mínimo (m²)</Label>
                    <Input id="min_stock_alert" name="min_stock_alert" inputmode="decimal" :default-value="floor?.min_stock_alert ?? ''" placeholder="Opcional" autocomplete="off" />
                    <InputError :message="errors.min_stock_alert" />
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <input type="hidden" name="is_active" :value="active ? 1 : 0" />
                <Checkbox id="is_active" :model-value="active" @update:model-value="active = $event === true" />
                <Label for="is_active">Piso ativo</Label>
            </div>
            <InputError :message="errors.is_active" />

            <div class="flex items-center gap-3">
                <Button type="submit" :disabled="processing">
                    <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
                    Salvar
                </Button>

                <Button variant="secondary" as-child>
                    <Link :href="index()">Cancelar</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
