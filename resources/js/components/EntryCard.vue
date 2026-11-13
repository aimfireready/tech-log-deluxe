<script setup>
import { Link } from '@inertiajs/vue3';
import HighlightedText from './HighlightedText.vue';
import { formatFull, formatTime } from '../utils/dates';

const expanded = defineModel('expanded', { type: Boolean, default: false });

defineProps({
    entry: { type: Object, required: true },
    active: { type: Boolean, default: false },
});

defineEmits(['filter-tag']);
</script>

<template>
    <article
        class="card tl-entry mb-2"
        :class="{ 'tl-entry-active': active }"
        :aria-expanded="expanded"
        tabindex="-1"
        :data-entry-id="entry.id"
    >
        <div class="card-body py-3">
            <div class="d-flex gap-3">
                <time
                    class="text-body-secondary small font-monospace pt-1 flex-shrink-0 tl-entry-time"
                    :datetime="entry.occurred_at"
                    :title="formatFull(entry.occurred_at)"
                >
                    {{ formatTime(entry.occurred_at) }}
                </time>

                <div class="flex-grow-1 min-w-0">
                    <h2 class="h6 mb-1">
                        <button type="button" class="btn btn-link p-0 text-start text-body text-decoration-none fw-semibold tl-entry-toggle" @click="expanded = !expanded">
                            <HighlightedText v-if="entry.highlight" :segments="entry.highlight.title" />
                            <template v-else>
                                {{ entry.title }}
                            </template>
                        </button>
                    </h2>

                    <div v-if="!expanded" class="text-body-secondary small tl-excerpt">
                        <HighlightedText v-if="entry.highlight" :segments="entry.highlight.snippet" />
                        <template v-else>
                            {{ entry.excerpt }}
                        </template>
                    </div>

                    <!-- body is sanitized on the server before it is stored (App\Support\EntryHtml). -->
                    <!-- eslint-disable-next-line vue/no-v-html -->
                    <div v-else class="tl-prose mt-2" v-html="entry.body" />

                    <div class="d-flex flex-wrap align-items-center gap-2 mt-2 small">
                        <button
                            v-for="tag in entry.tags"
                            :key="tag.slug"
                            type="button"
                            class="badge rounded-pill text-bg-secondary border-0 tl-tag"
                            :title="`Show only ${tag.name}`"
                            @click="$emit('filter-tag', tag.slug)"
                        >
                            {{ tag.name }}
                        </button>
                        <span class="text-body-secondary ms-auto">{{ entry.author?.name }}</span>
                        <Link :href="`/entries/${entry.id}`" class="text-body-secondary">
                            Open
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </article>
</template>
