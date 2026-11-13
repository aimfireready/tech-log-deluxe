<script setup>
import { defineAsyncComponent, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const RichEditor = defineAsyncComponent(() => import('../../components/RichEditor.vue'));
import TagInput from '../../components/TagInput.vue';
import { formatFull } from '../../utils/dates';

const props = defineProps({
    entry: { type: Object, required: true },
});

const editing = ref(false);
const form = useForm({
    title: props.entry.title,
    body: props.entry.body,
    tags: props.entry.tags.map((tag) => tag.name),
});

function startEditing() {
    form.defaults({ title: props.entry.title, body: props.entry.body, tags: props.entry.tags.map((tag) => tag.name) });
    form.reset();
    form.clearErrors();
    editing.value = true;
}

function save() {
    if (form.processing) {
        return;
    }

    form.put(`/entries/${props.entry.id}`, {
        preserveScroll: true,
        onSuccess: () => (editing.value = false),
    });
}

function onKeydown(event) {
    if (event.key === 'Enter' && (event.metaKey || event.ctrlKey)) {
        event.preventDefault();
        save();
    } else if (event.key === 'Escape') {
        editing.value = false;
    }
}

function destroy() {
    if (window.confirm(`Delete “${props.entry.title}”? You can undo this for a few seconds afterwards.`)) {
        router.delete(`/entries/${props.entry.id}`);
    }
}
</script>

<template>
    <Head :title="entry.title" />

    <p class="small mb-3">
        <Link href="/" class="text-body-secondary">
            ← Timeline
        </Link>
    </p>

    <article v-if="!editing" class="card">
        <div class="card-body p-4">
            <header class="mb-3">
                <h1 class="h4 mb-1">
                    {{ entry.title }}
                </h1>
                <p class="small text-body-secondary mb-0">
                    <time :datetime="entry.occurred_at">{{ formatFull(entry.occurred_at) }}</time>
                    · {{ entry.author?.name }}
                    <template v-if="entry.updated_at !== entry.created_at">
                        · edited <time :datetime="entry.updated_at">{{ formatFull(entry.updated_at) }}</time>
                    </template>
                </p>
                <div v-if="entry.tags.length" class="d-flex flex-wrap gap-1 mt-2">
                    <Link
                        v-for="tag in entry.tags"
                        :key="tag.slug"
                        :href="`/?tag=${tag.slug}`"
                        class="badge rounded-pill text-bg-secondary text-decoration-none"
                    >
                        {{ tag.name }}
                    </Link>
                </div>
            </header>

            <!-- body is sanitized on the server before it is stored (App\Support\EntryHtml). -->
            <!-- eslint-disable-next-line vue/no-v-html -->
            <div v-if="entry.body" class="tl-prose" v-html="entry.body" />
            <p v-else class="text-body-secondary fst-italic">
                No details.
            </p>

            <footer class="d-flex gap-2 mt-4">
                <button type="button" class="btn btn-primary btn-sm" @click="startEditing">
                    Edit
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm ms-auto" @click="destroy">
                    Delete
                </button>
            </footer>
        </div>
    </article>

    <form
        v-else
        class="card"
        novalidate
        @submit.prevent="save"
        @keydown="onKeydown"
    >
        <div class="card-body p-4 d-flex flex-column gap-3">
            <div>
                <label for="edit-title" class="form-label">Title</label>
                <input
                    id="edit-title"
                    v-model="form.title"
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': form.errors.title }"
                    maxlength="255"
                    autofocus
                >
                <div v-if="form.errors.title" class="invalid-feedback">
                    {{ form.errors.title }}
                </div>
            </div>

            <p class="small text-body-secondary mb-0">
                Logged for <time :datetime="entry.occurred_at">{{ formatFull(entry.occurred_at) }}</time>. The date can't be changed.
            </p>

            <div>
                <label for="edit-tags" class="form-label">Tags</label>
                <TagInput id="edit-tags" v-model="form.tags" :error="form.errors.tags ?? Object.entries(form.errors).find(([k]) => k.startsWith('tags.'))?.[1]" />
            </div>

            <div>
                <span class="form-label d-block">Details</span>
                <RichEditor id="edit-body" v-model="form.body" label="Details" />
                <div v-if="form.errors.body" class="text-danger small mt-1">
                    {{ form.errors.body }}
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <button type="submit" class="btn btn-primary" :disabled="form.processing">
                    Save
                </button>
                <button type="button" class="btn btn-outline-secondary" @click="editing = false">
                    Cancel
                </button>
                <span class="small text-body-secondary ms-auto"><kbd>Ctrl</kbd>/<kbd>⌘</kbd> + <kbd>Enter</kbd> to save</span>
            </div>
        </div>
    </form>
</template>
