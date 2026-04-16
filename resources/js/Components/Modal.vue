<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useModalHistory } from '@/Composables/useModalHistory.js';

const props = defineProps({
    show:      { type: Boolean, default: false },
    maxWidth:  { type: String, default: '2xl' },
    closeable: { type: Boolean, default: true },
});

const emit     = defineEmits(['close']);
const dialog   = ref();
const showSlot = ref(props.show);

watch(() => props.show, () => {
    if (props.show) {
        document.body.style.overflow = 'hidden';
        showSlot.value = true;
        dialog.value?.showModal();
    } else {
        document.body.style.overflow = '';
        setTimeout(() => {
            dialog.value?.close();
            showSlot.value = false;
        }, 250);
    }
});

const close = () => { if (props.closeable) emit('close'); };

const showRef = computed(() => props.show);
useModalHistory(showRef, close);

const closeOnEscape = (e) => {
    if (e.key === 'Escape') { e.preventDefault(); if (props.show) close(); }
};

onMounted(()  => document.addEventListener('keydown', closeOnEscape));
onUnmounted(() => {
    document.removeEventListener('keydown', closeOnEscape);
    document.body.style.overflow = '';
});

const maxWidthClass = computed(() => ({
    sm:  'modal__panel--sm',
    md:  'modal__panel--md',
    lg:  'modal__panel--lg',
    xl:  'modal__panel--xl',
    '2xl': 'modal__panel--2xl',
})[props.maxWidth]);
</script>

<template>
    <dialog
        class="modal__dialog"
        ref="dialog"
    >
        <div class="modal__overlay-wrap">
            <!-- Backdrop -->
            <Transition name="modal-backdrop">
                <div v-show="show" class="modal__backdrop" @click="close" />
            </Transition>

            <!-- Panel -->
            <Transition name="modal-panel">
                <div v-show="show" class="modal__panel" :class="maxWidthClass">
                    <slot v-if="showSlot" />
                </div>
            </Transition>
        </div>
    </dialog>
</template>

<style scoped>
.modal__dialog {
    z-index: 500;
    margin: 0;
    min-height: 100%;
    min-width: 100%;
    overflow-y: auto;
    background: transparent;
    padding: 0;
    border: none;
}

.modal__dialog::backdrop { display: none; }

.modal__overlay-wrap {
    position: fixed;
    inset: 0;
    z-index: 500;
    overflow-y: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

/* ─── Backdrop ───────────────────────────────────────────────────────────── */
.modal__backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.20);
    backdrop-filter: blur(8px) saturate(180%);
    -webkit-backdrop-filter: blur(8px) saturate(180%);
}

/* ─── Panel — liquid glass sheet ─────────────────────────────────────────── */
.modal__panel {
    position: relative;
    z-index: 510;
    width: 100%;
    background: rgba(255, 255, 255, 0.82);
    backdrop-filter: blur(40px) saturate(200%);
    -webkit-backdrop-filter: blur(40px) saturate(200%);
    border: 1px solid rgba(255, 255, 255, 0.90);
    border-radius: 1.5rem;
    box-shadow:
        0 24px 80px rgba(0, 80, 200, 0.16),
        0 8px 24px rgba(0, 0, 0, 0.10),
        inset 0 1px 0 rgba(255, 255, 255, 1),
        inset 0 -1px 0 rgba(0, 0, 0, 0.04);
    overflow: hidden;
}

/* Max-width variants */
.modal__panel--sm  { max-width: 24rem; }
.modal__panel--md  { max-width: 28rem; }
.modal__panel--lg  { max-width: 32rem; }
.modal__panel--xl  { max-width: 36rem; }
.modal__panel--2xl { max-width: 42rem; }

/* ─── Transitions ────────────────────────────────────────────────────────── */
.modal-backdrop-enter-active,
.modal-backdrop-leave-active   { transition: opacity 0.25s ease; }
.modal-backdrop-enter-from,
.modal-backdrop-leave-to        { opacity: 0; }

.modal-panel-enter-active { transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1); }
.modal-panel-leave-active { transition: all 0.2s ease; }
.modal-panel-enter-from   { opacity: 0; transform: scale(0.92) translateY(12px); }
.modal-panel-leave-to     { opacity: 0; transform: scale(0.96) translateY(6px); }
</style>
