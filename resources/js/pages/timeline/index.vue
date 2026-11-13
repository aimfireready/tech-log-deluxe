<script setup>
import { computed, nextTick, reactive, ref, watch } from 'vue';
import { Head, InfiniteScroll, router } from '@inertiajs/vue3';
import EntryCard from '../../components/EntryCard.vue';
import EntrySkeleton from '../../components/EntrySkeleton.vue';
import TimelineFilters from '../../components/TimelineFilters.vue';
import { useQuickAdd } from '../../composables/useQuickAdd';
import { useShortcuts } from '../../composables/useShortcuts';
import { useTimeline } from '../../composables/useTimeline';
import { groupByDay } from '../../utils/dates';

const props = defineProps({
    entries: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const quickAdd = useQuickAdd();
const { filtering, setFilter } = useTimeline();
const expanded = reactive({});
const activeIndex = ref(-1);

const groups = computed(() => groupByDay(props.entries.data));
const flat = computed(() => props.entries.data);
const hasFilters = computed(() => Object.keys(props.filters).length > 0);

// A new filter or search starts the keyboard cursor over.
watch(() => props.filters, () => (activeIndex.value = -1));

async function move(step) {
    if (!flat.value.length) {
        return;
    }

    activeIndex.value = Math.min(Math.max(activeIndex.value + step, 0), flat.value.length - 1);
    await nextTick();

    const card = document.querySelector(`[data-entry-id="${flat.value[activeIndex.value].id}"]`);
    card?.focus({ preventScroll: true });
    card?.scrollIntoView({ block: 'nearest' });
}

const activeEntry = () => flat.value[activeIndex.value];
const isInteractive = (event) => event.target.closest?.('button, a, [role="dialog"]');

useShortcuts({
    j: () => (quickAdd.open.value ? false : move(1)),
    k: () => (quickAdd.open.value ? false : move(-1)),
    Enter: (event) => {
        if (quickAdd.open.value || isInteractive(event) || !activeEntry()) {
            return false;
        }

        expanded[activeEntry().id] = !expanded[activeEntry().id];
    },
    o: () => {
        if (quickAdd.open.value || !activeEntry()) {
            return false;
        }

        router.visit(`/entries/${activeEntry().id}`);
    },
});
</script>

<template>
    <Head title="Timeline" />

    <TimelineFilters />

    <EntrySkeleton v-if="filtering" :count="4" />

    <template v-else-if="flat.length">
        <h1 class="visually-hidden">
            Timeline
        </h1>
        <InfiniteScroll data="entries" only-next>
            <section v-for="group in groups" :key="group.key" :aria-labelledby="`day-${group.key}`" class="mb-3">
                <h2 :id="`day-${group.key}`" class="tl-day-heading h6 text-body-secondary text-uppercase small fw-semibold py-2 mb-2">
                    {{ group.label }}
                </h2>
                <EntryCard
                    v-for="entry in group.entries"
                    :key="entry.id"
                    v-model:expanded="expanded[entry.id]"
                    :entry="entry"
                    :active="activeEntry()?.id === entry.id"
                    @filter-tag="(slug) => setFilter('tag', slug)"
                />
            </section>

            <template #loading>
                <EntrySkeleton :count="2" />
            </template>
        </InfiniteScroll>
    </template>

    <section v-else class="text-center text-body-secondary py-5" aria-labelledby="timeline-empty-title">
        <template v-if="hasFilters">
            <h1 id="timeline-empty-title" class="h5 text-body">
                No matching entries
            </h1>
            <p class="mb-0">
                Try fewer words, another tag, or a wider date range.
            </p>
        </template>
        <template v-else>
            <h1 id="timeline-empty-title" class="h5 text-body">
                No entries yet
            </h1>
            <p>Changes you log will appear here, newest first.</p>
            <button type="button" class="btn btn-primary" @click="quickAdd.show()">
                Log the first change
            </button>
            <p class="small mt-2 mb-0">
                or press <kbd>n</kbd> anywhere
            </p>
        </template>
    </section>
</template>
