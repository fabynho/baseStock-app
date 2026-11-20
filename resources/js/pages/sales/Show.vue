<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import SaleStatusBadge from '@/components/sales/SaleStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatDateTime, formatM2 } from '@/lib/format';
import { cancel, create, deliver, index } from '@/routes/sales';
import type { Deliverer, Sale } from '@/types/stock';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Ban, LoaderCircle, PackageCheck } from '@lucide/vue';
import { computed, ref } from 'vue';

const props = defineProps<{
    sale: Sale;
    deliverers: Deliverer[]; // vazio quando a venda não está pendente
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Vendas',
                href: index().url,
            },
            {
                title: 'Detalhes',
                href: '#',
            },
        ],
    },
});

const items = computed(() => props.sale.items ?? []);
const total = computed(() => items.value.reduce((sum, item) => sum + Number(item.quantity_m2), 0));
const delivererName = computed(() => {
    if (props.sale.status.value !== 'delivered' || props.sale.delivery_type.value === 'immediate') {
        return '—';
    }

    return props.sale.deliverer?.name ?? '—';
});

// Entrega futura exige entregador; retirada posterior pelo cliente é opcional.
const delivererRequired = computed(() => props.sale.delivery_type.value === 'scheduled_delivery');

// ---- Confirmar entrega / retirada (baixa o comprometido e o físico) ----
const deliverForm = useForm({ deliverer_id: delivererRequired.value ? '' : 'none' });

const confirmDelivery = () => {
    deliverForm
        .transform((data) => ({
            deliverer_id: data.deliverer_id === 'none' || data.deliverer_id === '' ? null : Number(data.deliverer_id),
        }))
        .post(deliver.url(props.sale.id), { preserveScroll: true });
};

// ---- Cancelar venda pendente (admin): libera o comprometido ----
const cancelOpen = ref(false);
const cancelForm = useForm({ reason: '' });

const cancelSale = () => {
    cancelForm.post(cancel.url(props.sale.id), {
        preserveScroll: true,
        onSuccess: () => {
            cancelOpen.value = false;
            cancelForm.reset();
        },
    });
};
</script>

<template>
    <Head :title="`Venda ${sale.document_number}`" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading :title="`Venda ${sale.document_number}`" description="Detalhes da venda e situação da entrega" />

            <div class="flex items-center gap-2">
                <Button variant="secondary" as-child>
                    <Link :href="index()">Voltar</Link>
                </Button>
                <Button as-child>
                    <Link :href="create()">Nova venda</Link>
                </Button>
            </div>
        </div>

        <dl class="grid max-w-4xl gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border p-4">
                <dt class="text-sm text-muted-foreground">Status</dt>
                <dd class="mt-1"><SaleStatusBadge :status="sale.status" /></dd>
            </div>
            <div class="rounded-xl border p-4">
                <dt class="text-sm text-muted-foreground">Cliente</dt>
                <dd class="font-medium">{{ sale.customer_name }}</dd>
            </div>
            <div class="rounded-xl border p-4">
                <dt class="text-sm text-muted-foreground">Vendedor</dt>
                <dd class="font-medium">{{ sale.seller?.name ?? '—' }}</dd>
            </div>
            <div class="rounded-xl border p-4">
                <dt class="text-sm text-muted-foreground">Tipo de entrega</dt>
                <dd class="font-medium">{{ sale.delivery_type.label }}</dd>
            </div>
            <div class="rounded-xl border p-4">
                <dt class="text-sm text-muted-foreground">Data da venda</dt>
                <dd class="font-medium">{{ formatDateTime(sale.sold_at) }}</dd>
            </div>
            <div class="rounded-xl border p-4">
                <dt class="text-sm text-muted-foreground">Entregue em</dt>
                <dd class="font-medium">{{ formatDateTime(sale.delivered_at) }}</dd>
            </div>
            <div class="rounded-xl border p-4">
                <dt class="text-sm text-muted-foreground">Entregador</dt>
                <dd class="font-medium">{{ delivererName }}</dd>
            </div>
            <div class="rounded-xl border p-4">
                <dt class="text-sm text-muted-foreground">Registrado por</dt>
                <dd class="font-medium">{{ sale.creator?.name ?? '—' }}</dd>
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
                        <TableCell>{{ item.floor?.description ?? '—' }}</TableCell>
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

        <div v-if="sale.notes" class="max-w-4xl rounded-xl border p-4">
            <p class="text-sm text-muted-foreground">Observações</p>
            <p class="whitespace-pre-line">{{ sale.notes }}</p>
        </div>

        <!-- Confirmar entrega / retirada -->
        <form v-if="sale.can_confirm" class="max-w-4xl space-y-4 rounded-xl border p-4" @submit.prevent="confirmDelivery">
            <div>
                <h2 class="font-medium">Confirmar saída</h2>
                <p class="text-sm text-muted-foreground">Baixa o estoque comprometido e o estoque físico desta venda.</p>
            </div>

            <div class="grid max-w-sm gap-2">
                <Label>Entregador {{ delivererRequired ? '' : '(opcional)' }}</Label>
                <Select v-model="deliverForm.deliverer_id">
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
                <InputError :message="deliverForm.errors.deliverer_id" />
            </div>

            <InputError :message="deliverForm.errors.stock" />

            <Button type="submit" :disabled="deliverForm.processing">
                <LoaderCircle v-if="deliverForm.processing" class="h-4 w-4 animate-spin" />
                <PackageCheck v-else class="h-4 w-4" />
                Marcar como entregue
            </Button>
        </form>

        <p v-if="sale.status.value === 'delivered'" class="max-w-4xl text-sm text-muted-foreground">
            Venda já entregue não pode ser cancelada. Para devolver o piso ao estoque, registre uma devolução de cliente.
        </p>

        <!-- Cancelar (somente admin, venda pendente) -->
        <div v-if="sale.can_cancel" class="max-w-4xl">
            <Button variant="destructive" @click="cancelOpen = true">
                <Ban class="h-4 w-4" />
                Cancelar venda
            </Button>

            <Dialog v-model:open="cancelOpen">
                <DialogContent>
                    <form class="space-y-6" @submit.prevent="cancelSale">
                        <DialogHeader class="space-y-3">
                            <DialogTitle>Cancelar venda {{ sale.document_number }}?</DialogTitle>
                            <DialogDescription>O estoque comprometido desta venda será liberado. Esta ação não pode ser desfeita.</DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="reason">Motivo do cancelamento</Label>
                            <textarea
                                id="reason"
                                v-model="cancelForm.reason"
                                rows="3"
                                class="flex min-h-16 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            />
                            <InputError :message="cancelForm.errors.reason" />
                            <InputError :message="cancelForm.errors.stock" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button type="button" variant="secondary">Voltar</Button>
                            </DialogClose>

                            <Button type="submit" variant="destructive" :disabled="cancelForm.processing">
                                <LoaderCircle v-if="cancelForm.processing" class="h-4 w-4 animate-spin" />
                                Confirmar cancelamento
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
