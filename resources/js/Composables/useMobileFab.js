import { ref, onUnmounted } from 'vue'

/**
 * Global reactive state — shared across all component instances.
 * Each page registers its own FAB actions via useMobileFab([...]).
 */
const _fabActions = ref([])

/**
 * Called by each page to register mobile FAB actions.
 * Actions are cleared automatically when the page unmounts.
 *
 * @param {Array<{ label: string, icon: string, action: Function, color?: string }>} actions
 */
export function useMobileFab(actions = []) {
    _fabActions.value = actions

    onUnmounted(() => {
        _fabActions.value = []
    })
}

/**
 * Used by MobileFab.vue to read the current actions.
 */
export function useFabActions() {
    return _fabActions
}
