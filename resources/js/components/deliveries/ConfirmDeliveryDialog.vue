<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
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

const form = useForm({ deliverer_id: '' });

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
        <DialogContent>
            <form class="space-y-6" @submit.prevent="submit">
                <DialogHeader class="space-y-3">
                    <DialogTitle>Marcar venda {{ sale?.document_number }} como entregue?</DialogTitle>
                    <DialogDescription>Baixa o estoque comprometido e o estoque físico desta venda. Esta ação não pode ser desfeita.</DialogDescription>
                </DialogHeader>

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