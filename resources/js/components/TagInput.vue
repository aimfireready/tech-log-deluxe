<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { addTag, suggestTags } from '../utils/tags';

const tags = defineModel({ type: Array, default: () => [] });

const props = defineProps({
    id: { type: String, required: true },
    error: { type: String, default: null },
});

const page = usePage();
const typed = ref('');
const highlighted = ref(0);
const suggestions = computed(() => suggestTags(page.props.tags ?? [], tags.value, typed.value));
const listId = computed(() => `${props.id}-suggestions`);

function commit(name = typed.value) {
    tags.value = addTag(tags.value, name, page.props.tags ?? []);
    typed.value = '';
    highlighted.value = 0;
}

function remove(index) {
    tags.value = tags.value.filter((_, i) => i !== index);
}

function onKeydown(event) {
    if (event.key === 'Enter' || event.key === ',' || event.key === 'Tab') {
        if (!typed.value.trim()) {
            return;
        }

        event.preventDefault();
        commit(suggestions.value[highlighted.value]?.name ?? typed.value);
    } else if (event.key === 'Backspace' && typed.value === '' && tags.value.length) {
        remove(tags.value.length - 1);
    } else if (event.key === 'ArrowDown' && suggestions.value.length) {
        event.preventDefault();
        highlighted.value = (highlighted.value + 1) % suggestions.value.length;
    } else if (event.key === 'ArrowUp' && suggestions.value.length) {
        event.preventDefault();
        highlighted.value = (highlighted.value - 1 + suggestions.value.length) % suggestions.value.length;
    }
}
</script>

<template>
    <div class="position-relative">
        <div
            class="form-control d-flex flex-wrap align-items-center gap-1 tl-tag-input"
            :class="{ 'is-invalid': error }"
            @click="$refs.input.focus()"
        >
            <span v-for="(tag, index) in tags" :key="tag" class="badge rounded-pill text-bg-secondary d-inline-flex align-items-center gap-1">
                {{ tag }}
                <button
                    type="button"
                    class="btn-close btn-close-white tl-badge-close"
                    :aria-label="`Remove tag ${tag}`"
                    @click.stop="remove(index)"
                />
            </span>
            <input
                :id="id"
                ref="input"
                v-model="typed"
                type="text"
                class="border-0 bg-transparent flex-grow-1 tl-tag-text"
                autocomplete="off"
                role="combobox"
                :aria-expanded="suggestions.length > 0"
                :aria-controls="listId"
                :aria-activedescendant="suggestions.length ? `${listId}-${highlighted}` : null"
                placeholder="Add tag…"
                @keydown="onKeydown"
                @blur="typed.trim() && commit()"
            >
        </div>
        <ul v-if="suggestions.length" :id="listId" class="dropdown-menu show w-100" role="listbox">
            <li
                v-for="(tag, index) in suggestions"
                :id="`${listId}-${index}`"
                :key="tag.slug"
                role="option"
                :aria-selected="index === highlighted"
            >
                <button
                    type="button"
                    class="dropdown-item"
                    :class="{ active: index === highlighted }"
                    @mousedown.prevent="commit(tag.name)"
                >
                    {{ tag.name }}
                </button>
            </li>
        </ul>
        <div v-if="error" class="invalid-feedback d-block">
            {{ error }}
        </div>
    </div>
</template>
