<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { formatDateTime, formatM2 } from '@/lib/format';
import { deliver } from '@/routes/sales';
import type { Deliverer, Sale } from '@/types/stock';
import { useForm } from '@inertiajs/vue3';
import { LoaderCircle, PackageCheck } from '@lucide/vue';
import { computed, watch } from 'vue';

const props = defineProps<{
    sale: Sale | null;
    deliverers: Deliverer[];
}>();

const open = defineModel<boolean>('open', { required: true });

// Entrega futura exige entregador; retirada posterior pelo cliente é opcional.
const delivererRequired = computed(() => props.sale?.delivery_type.value === 'scheduled_delivery');

const items = computed(() => props.sale?.items ?? []);

const totalM2 = computed(() =>
    items.value.reduce((sum, item) => sum + Number(item.quantity_m2), 0),
);

const form = useForm({ deliverer_id: '', stock: '' });

watch(open, (isOpen) => {
    if (isOpen) {
        form.clearErrors();
        form.deliverer_id = delivererRequired.value ? '' : 'none';
    }
});

const submit = () => {
    if (!props.sale) return;

    form.transform((data) => ({
        deliverer_id: data.deliverer_id === 'none' || data.deliverer_id === '' ? null : Number(data.deliverer_id),
    })).post(deliver.url(props.sale.id), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <form class="space-y-5" @submit.prevent="submit">
                <DialogHeader class="space-y-2">
                    <DialogTitle>Marcar venda {{ sale?.document_number }} como entregue?</DialogTitle>
                    <DialogDescription>
                        Baixa o estoque comprometido e o estoque físico desta venda. Esta ação não pode ser desfeita.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="sale" class="space-y-4 rounded-lg border bg-muted/30 p-3.5 text-sm">
                    <div class="flex flex-wrap items-start justify-between gap-2 border-b pb-3">
                        <div>
                            <p class="font-medium text-foreground">{{ sale.customer_name }}</p>
                            <p class="text-xs text-muted-foreground tabular-nums">Venda em {{ formatDateTime(sale.sold_at) }}</p>
                        </div>
                        <Badge variant="outline">{{ sale.delivery_type.label }}</Badge>
                    </div>

                    <div class="space-y-1.5">
                        <p class="text-xs font-medium text-muted-foreground">Pisos da venda</p>
                        <ul class="max-h-44 space-y-1.5 overflow-y-auto pr-1">
                            <li
                                v-for="item in items"
                                :key="item.id"
                                class="flex items-baseline justify-between gap-3 text-sm"
                            >
                                <div class="min-w-0">
                                    <span class="font-medium text-foreground">{{ item.floor?.code ?? item.floor_id }}</span>
                                    <span v-if="item.floor?.description" class="ml-1.5 truncate text-xs text-muted-foreground">
                                        {{ item.floor.description }} - {{ item.floor.reference ?? '—' }}
                                    </span>
                                </div>
                                <span class="shrink-0 font-medium text-foreground tabular-nums">
                                    {{ formatM2(item.quantity_m2) }}
                                </span>
                            </li>
                        </ul>
                    </div>

                    <div class="flex items-center justify-between border-t pt-3 font-medium">
                        <span>Total da entrega</span>
                        <span class="text-base tabular-nums">{{ formatM2(totalM2) }}</span>
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label>Entregador {{ delivererRequired ? '' : '(opcional)' }}</Label>
                    <Select v-model="form.deliverer_id">
                        <SelectTrigger class="w-full">
                            <SelectValue placeholder="Selecione o entregador" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-if="!delivererRequired" value="none">Sem entregador (retirada no balcão)</SelectItem>
                            <SelectItem v-for="deliverer in deliverers" :key="deliverer.id" :value="String(deliverer.id)">
                                {{ deliverer.name }}<span v-if="deliverer.vehicle_info" class="text-muted-foreground"> · {{ deliverer.vehicle_info }}</span>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.deliverer_id" />
                    <!-- Regras de estoque vindas do StockService -->
                    <InputError :message="form.errors.stock" />
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button type="button" variant="secondary">Voltar</Button>
                    </DialogClose>

                    <Button type="submit" :disabled="form.processing">
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <PackageCheck v-else class="h-4 w-4" />
                        Confirmar entrega
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>