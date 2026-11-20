<script setup lang="ts">
import { Input } from '@/components/ui/input';
import type { Floor } from '@/types/stock';
import { Search, X } from '@lucide/vue';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';

/**
 * Busca de piso (combobox). Filtra no cliente a lista de pisos já enviada pela page,
 * por código, referência, descrição e marca (sem acento, sem diferenciar maiúsculas,
 * todos os termos digitados precisam casar).
 *
 * v-model = id do piso como string ('' = nenhum), igual ao antigo <Select>.
 * A lista é renderizada via Teleport (position: fixed) para não ser cortada
 * pelo overflow da <Table>.
 */
const props = withDefaults(
    defineProps<{
        floors: Floor[];
        modelValue: string;
        placeholder?: string;
        invalid?: boolean;
        maxResults?: number;
        /** Piso não selecionável (ex.: já usado em outra linha ou sem estoque). */
        isDisabled?: (floor: Floor) => boolean;
        /** Texto exibido à direita de um piso desabilitado (ex.: "sem estoque"). */
        disabledLabel?: (floor: Floor) => string | undefined;
    }>(),
    {
        placeholder: 'Buscar por código, referência, descrição ou marca',
        invalid: false,
        maxResults: 30,
    },
);

const emit = defineEmits<{ 'update:modelValue': [value: string] }>();

defineSlots<{
    /** Informação extra à direita de cada opção habilitada (ex.: disponível em m²). */
    meta?(props: { floor: Floor }): unknown;
}>();

const root = ref<HTMLElement | null>(null);
const open = ref(false);
const query = ref('');
const activeIndex = ref(0);
const listStyle = ref<Record<string, string>>({});

const normalize = (value: string) =>
    value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase();

const label = (floor: Floor) => `${floor.code} — ${floor.description}`;

const selected = computed(() => props.floors.find((f) => String(f.id) === props.modelValue));

// Índice de busca calculado uma vez por mudança na lista de pisos.
const index = computed(() =>
    props.floors.map((floor) => ({
        floor,
        haystack: normalize([floor.code, floor.reference, floor.description, floor.brand].filter(Boolean).join(' ')),
    })),
);

const matches = computed(() => {
    const terms = normalize(query.value.trim()).split(/\s+/).filter(Boolean);

    return terms.length ? index.value.filter((item) => terms.every((term) => item.haystack.includes(term))) : index.value;
});

const results = computed(() => matches.value.slice(0, props.maxResults));

// Fechado: mostra o piso escolhido. Aberto: mostra o que o operador está digitando.
const inputValue = computed(() => (open.value ? query.value : selected.value ? label(selected.value) : ''));

const disabled = (floor: Floor) => props.isDisabled?.(floor) ?? false;

const inputEl = () => root.value?.querySelector('input') ?? null;

function reposition() {
    const rect = root.value?.getBoundingClientRect();
    if (!rect) return;

    const spaceBelow = window.innerHeight - rect.bottom;
    const placeAbove = spaceBelow < 280 && rect.top > spaceBelow;

    listStyle.value = {
        left: `${rect.left}px`,
        width: `${rect.width}px`,
        ...(placeAbove ? { bottom: `${window.innerHeight - rect.top + 4}px` } : { top: `${rect.bottom + 4}px` }),
    };
}

function openList() {
    query.value = '';
    activeIndex.value = 0;
    open.value = true;
    reposition();
}

function close() {
    open.value = false;
}

function onType(value: string | number) {
    query.value = String(value);
    activeIndex.value = 0;

    if (!open.value) {
        open.value = true;
        reposition();
    }
}

function choose(floor: Floor) {
    if (disabled(floor)) return;

    emit('update:modelValue', String(floor.id));
    close();
    inputEl()?.blur();
}

function clear() {
    emit('update:modelValue', '');
    inputEl()?.focus();
}

function move(delta: number) {
    const total = results.value.length;
    if (total === 0) return;

    activeIndex.value = (activeIndex.value + delta + total) % total;

    nextTick(() => document.querySelector('[data-floor-option][data-active="true"]')?.scrollIntoView({ block: 'nearest' }));
}

function onKeydown(event: KeyboardEvent) {
    switch (event.key) {
        case 'ArrowDown':
            event.preventDefault();
            open.value ? move(1) : openList();
            break;
        case 'ArrowUp':
            event.preventDefault();
            open.value ? move(-1) : openList();
            break;
        case 'Enter':
            // Nunca deixa o Enter enviar o formulário de dentro da busca.
            event.preventDefault();
            if (open.value && results.value[activeIndex.value]) {
                choose(results.value[activeIndex.value].floor);
            }
            break;
        case 'Escape':
            if (open.value) {
                event.preventDefault();
                event.stopPropagation();
                close();
                inputEl()?.blur();
            }
            break;
    }
}

// A lista é fixed: acompanha scroll e resize enquanto estiver aberta.
watch(open, (isOpen) => {
    if (isOpen) {
        window.addEventListener('scroll', reposition, true);
        window.addEventListener('resize', reposition);
    } else {
        window.removeEventListener('scroll', reposition, true);
        window.removeEventListener('resize', reposition);
    }
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', reposition, true);
    window.removeEventListener('resize', reposition);
});
</script>

<template>
    <div ref="root" class="relative">
        <Search class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />

        <Input
            :model-value="inputValue"
            :placeholder="open && selected ? label(selected) : placeholder"
            :aria-invalid="invalid || undefined"
            :aria-expanded="open"
            role="combobox"
            aria-autocomplete="list"
            autocomplete="off"
            class="pr-9 pl-9"
            @update:model-value="onType"
            @focus="openList"
            @blur="close"
            @keydown="onKeydown"
        />

        <button
            v-if="selected"
            type="button"
            tabindex="-1"
            aria-label="Limpar piso"
            class="absolute top-1/2 right-2 flex h-6 w-6 -translate-y-1/2 items-center justify-center rounded-sm text-muted-foreground hover:text-foreground"
            @mousedown.prevent
            @click="clear"
        >
            <X class="h-4 w-4" />
        </button>

        <Teleport to="body">
            <!-- mousedown.prevent: clicar na lista (ou arrastar a barra de rolagem) não tira o foco do input -->
            <ul
                v-if="open"
                role="listbox"
                :style="listStyle"
                class="fixed z-50 max-h-64 overflow-auto rounded-md border bg-popover p-1 text-popover-foreground shadow-md"
                @mousedown.prevent
            >
                <li v-if="results.length === 0" class="px-3 py-6 text-center text-sm text-muted-foreground">Nenhum piso encontrado.</li>

                <li
                    v-for="(item, i) in results"
                    :key="item.floor.id"
                    data-floor-option
                    role="option"
                    :data-active="i === activeIndex"
                    :aria-selected="String(item.floor.id) === modelValue"
                    :aria-disabled="disabled(item.floor)"
                    class="flex items-center justify-between gap-3 rounded-sm px-2 py-1.5 text-sm"
                    :class="[
                        disabled(item.floor) ? 'cursor-not-allowed opacity-50' : 'cursor-pointer',
                        i === activeIndex && !disabled(item.floor) ? 'bg-accent text-accent-foreground' : '',
                    ]"
                    @mousemove="activeIndex = i"
                    @click="choose(item.floor)"
                >
                    <div class="min-w-0">
                        <p class="truncate">
                            <span class="font-medium">{{ item.floor.code }}</span> — {{ item.floor.description }}
                        </p>
                        <p v-if="item.floor.reference || item.floor.brand" class="truncate text-xs text-muted-foreground">
                            <template v-if="item.floor.reference">Ref.: {{ item.floor.reference }}</template>
                            <template v-if="item.floor.reference && item.floor.brand"> · </template>
                            <template v-if="item.floor.brand">{{ item.floor.brand }}</template>
                        </p>
                    </div>

                    <span v-if="disabled(item.floor)" class="shrink-0 text-xs text-muted-foreground">
                        {{ disabledLabel?.(item.floor) }}
                    </span>
                    <span v-else class="shrink-0 text-xs text-muted-foreground">
                        <slot name="meta" :floor="item.floor" />
                    </span>
                </li>

                <li v-if="matches.length > results.length" class="px-2 py-1.5 text-center text-xs text-muted-foreground">
                    Mostrando {{ results.length }} de {{ matches.length }} pisos — continue digitando para refinar.
                </li>
            </ul>
        </Teleport>
    </div>
</template>