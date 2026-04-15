<template>
    <label class="label-ios" v-bind="$attrs">
        {{ value }}
        <slot />
    </label>
</template>

<script setup>
defineOptions({ inheritAttrs: false });

defineProps({
    value: String,
});
</script>
