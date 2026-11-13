<script setup>
import { defineAsyncComponent, nextTick, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

// TipTap is most of the JavaScript; load it only once someone starts writing.
const RichEditor = defineAsyncComponent(() => import('./RichEditor.vue'));
import TagInput from './TagInput.vue';
import { useQuickAdd } from '../composables/useQuickAdd';
import { toLocalInputValue } from '../utils/dates';

const { open, hide } = useQuickAdd();
const titleInput = ref(null);

const form = useForm({
    title: '',
    occurred_at: toLocalInputValue(),
    tags: [],
    body: '',
});

watch(open, async (isOpen) => {
    if (isOpen) {
        // A fresh "now" each time, unless a half-written entry is waiting.
        if (!form.isDirty) {
            form.occurred_at = toLocalInputValue();
            form.defaults('occurred_at', form.occurred_at);
        }
        await nextTick();
        titleInput.value?.focus();
    }
});

function submit() {
    if (form.processing) {
        return;
    }

    form
        .transform((data) => ({ ...data, occurred_at: new Date(data.occurred_at).toISOString() }))
        .post('/entries', {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                hide();
            },
        });
}

function onKeydown(event) {
    if (event.key === 'Enter' && (event.metaKey || event.ctrlKey)) {
        event.preventDefault();
        submit();
    } else if (event.key === 'Escape') {
        hide();
    }
}
</script>

<template>
    <template v-if="open">
        <aside
            class="offcanvas offcanvas-end show tl-quick-add"
            tabindex="-1"
            role="dialog"
            aria-modal="true"
            aria-labelledby="quick-add-title"
            @keydown="onKeydown"
        >
            <div class="offcanvas-header border-bottom">
                <h2 id="quick-add-title" class="offcanvas-title h5">
                    New entry
                </h2>
                <button type="button" class="btn-close" aria-label="Close" @click="hide" />
            </div>
            <form class="offcanvas-body d-flex flex-column gap-3" novalidate @submit.prevent="submit">
                <div>
                    <label for="qa-title" class="form-label">Title</label>
                    <input
                        id="qa-title"
                        ref="titleInput"
                        v-model="form.title"
                        type="text"
                        class="form-control"
                        :class="{ 'is-invalid': form.errors.title }"
                        placeholder="What changed?"
                        maxlength="255"
                    >
                    <div v-if="form.errors.title" class="invalid-feedback">
                        {{ form.errors.title }}
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-sm-5">
                        <label for="qa-when" class="form-label">When</label>
                        <input
                            id="qa-when"
                            v-model="form.occurred_at"
                            type="datetime-local"
                            class="form-control"
                            :class="{ 'is-invalid': form.errors.occurred_at }"
                        >
                        <div v-if="form.errors.occurred_at" class="invalid-feedback">
                            {{ form.errors.occurred_at }}
                        </div>
                    </div>
                    <div class="col-sm-7">
                        <label for="qa-tags" class="form-label">Tags</label>
                        <TagInput id="qa-tags" v-model="form.tags" :error="form.errors.tags ?? Object.entries(form.errors).find(([k]) => k.startsWith('tags.'))?.[1]" />
                    </div>
                </div>

                <div class="flex-grow-1 d-flex flex-column">
                    <span class="form-label">Details</span>
                    <RichEditor id="qa-body" v-model="form.body" label="Details" />
                    <div v-if="form.errors.body" class="text-danger small mt-1">
                        {{ form.errors.body }}
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <button type="submit" class="btn btn-primary" :disabled="form.processing">
                        Log entry
                    </button>
                    <button type="button" class="btn btn-outline-secondary" @click="hide">
                        Cancel
                    </button>
                    <span class="small text-body-secondary ms-auto"><kbd>Ctrl</kbd>/<kbd>⌘</kbd> + <kbd>Enter</kbd> to save</span>
                </div>
            </form>
        </aside>
        <div class="offcanvas-backdrop fade show" @click="hide" />
    </template>
</template>
