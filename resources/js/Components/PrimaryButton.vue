<template>
    <button
        :type="type"
        class="btn-ios btn-ios-primary"
        :class="$attrs.class"
        v-bind="$attrs"
    >
        <slot />
    </button>
</template>

<script setup>
defineProps({ type: { type: String, default: 'submit' } })
</script>
