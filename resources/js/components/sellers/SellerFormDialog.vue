<script setup lang="ts">
import SellerController from '@/actions/App/Http/Controllers/SellerController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Seller } from '@/types/stock';
import { Form } from '@inertiajs/vue3';
import { LoaderCircle } from '@lucide/vue';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    seller?: Seller | null;
}>();

const open = defineModel<boolean>('open', { required: true });

const isEdit = computed(() => !!props.seller);

// Um único componente atende criação (store) e edição (update).
const formAction = computed(() => (props.seller ? SellerController.update.form.patch(props.seller.id) : SellerController.store.form()));

// Checkbox desmarcado não vai no envio do formulário, então o valor segue num input hidden (0/1).
const active = ref(props.seller?.is_active ?? true);

watch(open, (isOpen) => {
    if (isOpen) {
        active.value = props.seller?.is_active ?? true;
    }
});
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <Form
                v-bind="formAction"
                :options="{ preserveScroll: true }"
                class="space-y-6"
                v-slot="{ errors, processing }"
                @success="open = false"
            >
                <DialogHeader class="space-y-3">
                    <DialogTitle>{{ isEdit ? 'Editar vendedor' : 'Novo vendedor' }}</DialogTitle>
                    <DialogDescription>
                        {{ isEdit ? 'Atualize os dados do vendedor.' : 'Informe os dados do novo vendedor.' }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-2">
                    <Label for="name">Nome</Label>
                    <Input id="name" name="name" :default-value="seller?.name ?? ''" placeholder="Nome do vendedor" autocomplete="off" />
                    <InputError :message="errors.name" />
                </div>

                <div class="flex items-center space-x-3">
                    <input type="hidden" name="is_active" :value="active ? 1 : 0" />
                    <Checkbox id="is_active" :model-value="active" @update:model-value="active = $event === true" />
                    <Label for="is_active">Vendedor ativo</Label>
                </div>
                <InputError :message="errors.is_active" />

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button type="button" variant="secondary">Cancelar</Button>
                    </DialogClose>

                    <Button type="submit" :disabled="processing">
                        <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
                        Salvar
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
