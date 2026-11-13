import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const FILTER_KEYS = ['q', 'tag', 'from', 'to'];

// Module-level so the layout's search box and the timeline's filter bar share one loading state.
const filtering = ref(false);

/**
 * Drop empty values and unknown keys so URLs stay clean (/?tag=dns, not /?q=&tag=dns&from=).
 */
export function cleanFilters(filters) {
    return Object.fromEntries(
        FILTER_KEYS
            .map((key) => [key, typeof filters[key] === 'string' ? filters[key].trim() : filters[key]])
            .filter(([, value]) => value),
    );
}

export function useTimeline() {
    const page = usePage();

    function currentFilters() {
        return page.component === 'timeline/index' ? { ...(page.props.filters ?? {}) } : {};
    }

    /**
     * Visit the timeline with these filters. A full visit replaces the loaded entries; don't add
     * `reset` here: in Inertia 3 it turns the visit into a partial reload of only that prop.
     */
    function applyFilters(filters) {
        router.get('/', cleanFilters(filters), {
            preserveState: true,
            replace: true,
            onStart: () => (filtering.value = true),
            onFinish: () => (filtering.value = false),
        });
    }

    function setFilter(key, value) {
        applyFilters({ ...currentFilters(), [key]: value });
    }

    return { filtering, currentFilters, applyFilters, setFilter };
}
