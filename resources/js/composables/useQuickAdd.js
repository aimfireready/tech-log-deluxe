import { ref } from 'vue';

// One panel for the whole app; any screen can open it.
const open = ref(false);

export function useQuickAdd() {
    return {
        open,
        show: () => (open.value = true),
        hide: () => (open.value = false),
    };
}
