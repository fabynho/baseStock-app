<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import type { EnumOption } from '@/types/stock';
import { computed } from 'vue';

const props = defineProps<{
    status: EnumOption;
}>();

const variant = computed(() => {
    switch (props.status.value) {
        case 'delivered':
            return 'default';
        case 'canceled':
            return 'destructive';
        default:
            return 'outline';
    }
});
</script>

<template>
    <Badge :variant="variant" :class="{ 'border-amber-500 text-amber-600 dark:text-amber-400': status.value === 'pending_delivery' }">
        {{ status.label }}
    </Badge>
</template>
