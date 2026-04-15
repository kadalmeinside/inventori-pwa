<template>
    <input
        class="input-ios"
        :class="$attrs.class"
        v-bind="$attrs"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        ref="input"
    />
</template>

<script setup>
import { ref } from 'vue';

defineProps({ modelValue: { type: [String, Number], default: '' } });
defineEmits(['update:modelValue']);

const input = ref(null);
defineExpose({ focus: () => input.value.focus() });
</script>
