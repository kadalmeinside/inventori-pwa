<template>
    <button
        type="button"
        class="btn-ios btn-ios-danger"
        :class="$attrs.class"
        v-bind="$attrs"
    >
        <slot />
    </button>
</template>
