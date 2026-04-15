<template>
    <p v-show="message" class="input-error">
        {{ message }}
    </p>
</template>

<script setup>
defineProps({ message: String });
</script>

<style scoped>
.input-error {
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--ios-red);
    margin-top: 0.25rem;
    padding-left: 0.125rem;
}
</style>
