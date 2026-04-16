import { watch, onMounted, onUnmounted } from 'vue';

export function useModalHistory(isOpenRef, closeCallback) {
    let internalClose = false;

    const onPopState = (event) => {
        if (isOpenRef.value) {
            internalClose = true;
            closeCallback();
            setTimeout(() => { internalClose = false; }, 50);
        }
    };

    watch(isOpenRef, (isOpen) => {
        if (internalClose) return;

        if (isOpen) {
            // Duplicate the current Inertia history state so Inertia doesn't break
            const currentState = window.history.state || {};
            window.history.pushState({ ...currentState, _isModal: true }, '');
        } else {
            // When closing via UI, clean up the dummy history push we made
            if (window.history.state && window.history.state._isModal) {
                internalClose = true;
                window.history.back();
                setTimeout(() => { internalClose = false; }, 50);
            }
        }
    });

    onMounted(() => {
        window.addEventListener('popstate', onPopState);
    });

    onUnmounted(() => {
        window.removeEventListener('popstate', onPopState);
    });
}
