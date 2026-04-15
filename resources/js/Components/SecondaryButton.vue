<template>
    <button
        type="button"
        class="btn-ios btn-ios-glass"
        :class="$attrs.class"
        v-bind="$attrs"
    >
        <slot />
    </button>
</template>
