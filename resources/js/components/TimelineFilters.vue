<script setup>
import { computed, reactive, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useTimeline } from '../composables/useTimeline';

const page = usePage();
const { currentFilters, applyFilters } = useTimeline();

const draft = reactive({ tag: '', from: '', to: '' });

// Mirror the URL's filters into the controls whenever the page props change.
watch(() => page.props.filters, (filters) => {
    draft.tag = filters?.tag ?? '';
    draft.from = filters?.from ?? '';
    draft.to = filters?.to ?? '';
}, { immediate: true });

const tagName = (slug) => page.props.tags?.find((tag) => tag.slug === slug)?.name ?? slug;

const chips = computed(() => {
    const filters = page.props.filters ?? {};
    const list = [];

    if (filters.q) list.push({ key: 'q', label: `“${filters.q}”` });
    if (filters.tag) list.push({ key: 'tag', label: `#${tagName(filters.tag)}` });
    if (filters.from) list.push({ key: 'from', label: `from ${filters.from}` });
    if (filters.to) list.push({ key: 'to', label: `to ${filters.to}` });

    return list;
});

function apply() {
    applyFilters({ ...currentFilters(), ...draft });
}

function remove(key) {
    applyFilters({ ...currentFilters(), [key]: '' });
}

function clearAll() {
    applyFilters({});
}
</script>

<template>
    <section class="mb-3" aria-label="Filters">
        <form class="row g-2 align-items-end" @submit.prevent="apply">
            <div class="col-12 col-sm-4">
                <label for="filter-tag" class="form-label small text-body-secondary mb-1">Tag</label>
                <select id="filter-tag" v-model="draft.tag" class="form-select form-select-sm" @change="apply">
                    <option value="">
                        All tags
                    </option>
                    <option v-for="tag in page.props.tags" :key="tag.slug" :value="tag.slug">
                        {{ tag.name }}
                    </option>
                </select>
            </div>
            <div class="col-6 col-sm-3">
                <label for="filter-from" class="form-label small text-body-secondary mb-1">From</label>
                <input
                    id="filter-from"
                    v-model="draft.from"
                    type="date"
                    class="form-control form-control-sm"
                    :max="draft.to || undefined"
                    @change="apply"
                >
            </div>
            <div class="col-6 col-sm-3">
                <label for="filter-to" class="form-label small text-body-secondary mb-1">To</label>
                <input
                    id="filter-to"
                    v-model="draft.to"
                    type="date"
                    class="form-control form-control-sm"
                    :min="draft.from || undefined"
                    @change="apply"
                >
            </div>
        </form>

        <div v-if="chips.length" class="d-flex flex-wrap align-items-center gap-2 mt-2">
            <span class="small text-body-secondary">Showing</span>
            <span v-for="chip in chips" :key="chip.key" class="badge rounded-pill text-bg-dark border d-inline-flex align-items-center gap-1">
                {{ chip.label }}
                <button type="button" class="btn-close btn-close-white tl-badge-close" :aria-label="`Remove filter ${chip.label}`" @click="remove(chip.key)" />
            </span>
            <button type="button" class="btn btn-link btn-sm p-0" @click="clearAll">
                Clear all
            </button>
        </div>
        <div v-if="page.props.errors?.to" class="text-danger small mt-1" role="alert">
            {{ page.props.errors.to }}
        </div>
    </section>
</template>
