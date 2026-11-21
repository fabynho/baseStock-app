<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { index, store, update } from '@/routes/users';
import type { EnumOption, ManagedUser } from '@/types/stock';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from '@lucide/vue';
import { computed } from 'vue';

// Criação: sem prop `user`. Edição: `user` vem do UserController@edit.
const props = defineProps<{
    user?: ManagedUser;
    roles: EnumOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Usuários',
                href: index().url,
            },
        ],
    },
});

const isEdit = computed(() => !!props.user);

// O próprio admin não pode rebaixar nem desativar a si mesmo (o backend também bloqueia).
const isSelf = computed(() => props.user?.is_self ?? false);

const form = useForm({
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    password: '',
    password_confirmation: '',
    role: props.user?.role.value ?? 'user',
    is_active: props.user?.is_active ?? true,
});

const submit = () => {
    const options = {
        preserveScroll: true,
        onFinish: () => form.reset('password', 'password_confirmation'),
    };

    if (props.user) {
        form.put(update.url(props.user.id), options);
    } else {
        form.post(store.url(), options);
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Editar usuário' : 'Novo usuário'" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <Heading
            :title="isEdit ? `Editar ${user?.name}` : 'Novo usuário'"
            :description="isEdit ? 'Atualize os dados, o perfil e o status da conta' : 'Crie uma conta de acesso ao sistema'"
        />

        <form class="max-w-2xl space-y-6" @submit.prevent="submit">
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="name">Nome</Label>
                    <Input id="name" v-model="form.name" autocomplete="off" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">E-mail</Label>
                    <Input id="email" v-model="form.email" type="email" autocomplete="off" />
                    <InputError :message="form.errors.email" />
                </div>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="password">{{ isEdit ? 'Nova senha' : 'Senha' }}</Label>
                    <Input id="password" v-model="form.password" type="password" autocomplete="new-password" />
                    <p v-if="isEdit" class="text-xs text-muted-foreground">Deixe em branco para manter a senha atual.</p>
                    <InputError :message="form.errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirmar senha</Label>
                    <Input id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" />
                    <InputError :message="form.errors.password_confirmation" />
                </div>
            </div>

            <div class="grid max-w-sm gap-2">
                <Label>Perfil</Label>
                <Select v-model="form.role" :disabled="isSelf">
                    <SelectTrigger class="w-full">
                        <SelectValue placeholder="Selecione o perfil" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem v-for="role in roles" :key="role.value" :value="role.value">{{ role.label }}</SelectItem>
                    </SelectContent>
                </Select>
                <p v-if="isSelf" class="text-xs text-muted-foreground">Você não pode alterar seu próprio perfil.</p>
                <InputError :message="form.errors.role" />
            </div>

            <div class="space-y-2">
                <div class="flex items-center space-x-3">
                    <Checkbox
                        id="is_active"
                        :model-value="form.is_active"
                        :disabled="isSelf"
                        @update:model-value="form.is_active = $event === true"
                    />
                    <Label for="is_active">Usuário ativo</Label>
                </div>
                <p v-if="isSelf" class="text-xs text-muted-foreground">Você não pode desativar a si mesmo.</p>
                <InputError :message="form.errors.is_active" />
            </div>

            <div class="flex items-center gap-3">
                <Button type="submit" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Salvar
                </Button>

                <Button variant="secondary" as-child>
                    <Link :href="index()">Cancelar</Link>
                </Button>
            </div>
        </form>
    </div>
</template>