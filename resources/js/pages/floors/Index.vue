<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { formatM2 } from '@/lib/format';
import { create, edit, index } from '@/routes/floors';
import type { Floor, Paginated } from '@/types/stock';
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Search } from '@lucide/vue';
import { ref, watch } from 'vue';

const props = defineProps<{
    floors: Paginated<Floor>;
    filters: { search: string | null };
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

// Busca com debounce: recarrega só a lista, mantendo o foco no campo.
const search = ref(props.filters.search ?? '');
let timer: ReturnType<typeof setTimeout>;

watch(search, (value) => {
    clearTimeout(timer);
    timer = setTimeout(() => {
        router.get(index().url, { search: value || undefined }, { preserveState: true, preserveScroll: true, replace: true });
    }, 300);
});
</script>

<template>
    <Head title="Pisos" />

    <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
        <div class="flex items-start justify-between gap-4">
            <Heading title="Pisos" description="Cadastro de pisos e saldos atuais em m²" />

            <Button as-child>
                <Link :href="create()">
                    <Plus class="h-4 w-4" />
                    Novo piso
                </Link>
            </Button>
        </div>

        <div class="relative max-w-sm">
            <Search class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input v-model="search" class="pl-9" placeholder="Buscar por código, referência, descrição ou marca" autocomplete="off" />
        </div>

        <div class="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-32">Código</TableHead>
                        <TableHead>Descrição</TableHead>
                        <TableHead>Marca</TableHead>
                        <TableHead class="text-right">Físico (m²)</TableHead>
                        <TableHead class="text-right">Comprometido (m²)</TableHead>
                        <TableHead class="text-right">Disponível (m²)</TableHead>
                        <TableHead class="w-28">Status</TableHead>
                        <TableHead class="w-20 text-right">Ações</TableHead>
                    </TableRow>
                </TableHeader>

                <TableBody>
                    <TableRow v-if="floors.data.length === 0">
                        <TableCell colspan="8" class="h-24 text-center text-muted-foreground">Nenhum piso encontrado.</TableCell>
                    </TableRow>

                    <TableRow v-for="floor in floors.data" :key="floor.id" :class="{ 'text-muted-foreground': !floor.is_active }">
                        <TableCell class="font-medium">{{ floor.code }}</TableCell>
                        <TableCell>
                            {{ floor.description }}
                            <span v-if="floor.reference" class="block text-xs text-muted-foreground">Ref.: {{ floor.reference }}</span>
                        </TableCell>
                        <TableCell>{{ floor.brand || '—' }}</TableCell>
                        <TableCell class="text-right tabular-nums">{{ formatM2(floor.physical_stock) }}</TableCell>
                        <TableCell class="text-right tabular-nums">{{ formatM2(floor.committed_stock) }}</TableCell>
                        <TableCell
                            class="text-right font-medium tabular-nums"
                            :class="{ 'text-red-600 dark:text-red-400': floor.below_minimum }"
                            :title="floor.below_minimum ? 'Abaixo do estoque mínimo' : undefined"
                        >
                            {{ formatM2(floor.available_stock) }}
                        </TableCell>
                        <TableCell>
                            <Badge :variant="floor.is_active ? 'default' : 'secondary'">
                                {{ floor.is_active ? 'Ativo' : 'Inativo' }}
                            </Badge>
                        </TableCell>
                        <TableCell class="text-right">
                            <Button variant="ghost" size="icon" as-child :aria-label="`Editar ${floor.code}`">
                                <Link :href="edit(floor.id)">
                                    <Pencil class="h-4 w-4" />
                                </Link>
                            </Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div v-if="floors.meta.total > 0" class="flex items-center justify-between text-sm text-muted-foreground">
            <span>Mostrando {{ floors.meta.from }}–{{ floors.meta.to }} de {{ floors.meta.total }}</span>

            <div class="flex items-center gap-2">
                <Button v-if="floors.links.prev" variant="outline" size="sm" as-child>
                    <Link :href="floors.links.prev" preserve-scroll>Anterior</Link>
                </Button>
                <Button v-else variant="outline" size="sm" disabled>Anterior</Button>

                <span>Página {{ floors.meta.current_page }} de {{ floors.meta.last_page }}</span>

                <Button v-if="floors.links.next" variant="outline" size="sm" as-child>
                    <Link :href="floors.links.next" preserve-scroll>Próxima</Link>
                </Button>
                <Button v-else variant="outline" size="sm" disabled>Próxima</Button>
            </div>
        </div>
    </div>
</template>
