import { onBeforeUnmount, onMounted } from 'vue';

const EDITABLE_TAGS = ['INPUT', 'TEXTAREA', 'SELECT'];

/**
 * True when the keystroke belongs to something the user is typing into.
 */
export function isTyping(event) {
    const target = event.target;

    if (!target || !target.tagName) {
        return false;
    }

    // closest() also catches nodes nested inside an editor such as TipTap's.
    return EDITABLE_TAGS.includes(target.tagName)
        || target.closest?.('[contenteditable]:not([contenteditable="false"])') != null;
}

/**
 * Pick the handler for a keydown event, or null. Modified keys are left to the
 * browser, and single-key shortcuts never fire while the user is typing.
 */
export function matchShortcut(event, bindings) {
    if (event.ctrlKey || event.metaKey || event.altKey) {
        return null;
    }

    if (isTyping(event)) {
        return null;
    }

    return bindings[event.key] ?? null;
}

/**
 * Register single-key shortcuts for the lifetime of the calling component.
 *
 * @param {Record<string, (event: KeyboardEvent) => void>} bindings keyed by KeyboardEvent.key
 */
export function useShortcuts(bindings) {
    const onKeydown = (event) => {
        const handler = matchShortcut(event, bindings);

        if (handler) {
            event.preventDefault();
            handler(event);
        }
    };

    onMounted(() => window.addEventListener('keydown', onKeydown));
    onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
}
