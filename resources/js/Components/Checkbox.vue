<script setup>
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);

const props = defineProps({
    checked: { type: [Array, Boolean], required: true },
    value:   { default: null },
});

const proxyChecked = computed({
    get() { return props.checked; },
    set(val) { emit('update:checked', val); },
});
</script>

<template>
    <input
        type="checkbox"
        :value="value"
        v-model="proxyChecked"
        class="ios-checkbox"
    />
</template>

<style scoped>
.ios-checkbox {
    appearance: none;
    -webkit-appearance: none;
    width: 1.125rem;
    height: 1.125rem;
    border-radius: 0.3125rem;
    border: 1.5px solid rgba(0, 0, 0, 0.20);
    background: rgba(255, 255, 255, 0.8);
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
    flex-shrink: 0;
    position: relative;
}

.ios-checkbox:checked {
    background: var(--ios-blue);
    border-color: var(--ios-blue);
    box-shadow: 0 2px 6px rgba(0, 122, 255, 0.35);
}

.ios-checkbox:checked::after {
    content: '';
    position: absolute;
    left: 3px;
    top: 1px;
    width: 6px;
    height: 10px;
    border: 2px solid white;
    border-top: none;
    border-left: none;
    transform: rotate(45deg);
}

.ios-checkbox:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(0, 122, 255, 0.20);
}

.ios-checkbox:active {
    transform: scale(0.92);
}
</style>
