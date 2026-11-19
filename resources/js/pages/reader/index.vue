<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import ReaderLayout from '../../layouts/ReaderLayout.vue';
import PostFeed from '../../components/reader/PostFeed.vue';

defineOptions({ layout: ReaderLayout });

const props = defineProps({
    posts: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
});

const searching = computed(() => Boolean(props.filters.q));
</script>

<template>
    <Head :title="searching ? `Search: ${filters.q}` : 'Reading view'" />

    <header v-if="searching" class="gh-archive">
        <div class="gh-inner">
            <span class="gh-eyebrow">Search</span>
            <h1>{{ filters.q }}</h1>
            <p>
                {{ posts.data.length ? 'Matching posts, newest first.' : 'No posts match.' }}
                <Link href="/read" class="text-decoration-underline">
                    Clear search
                </Link>
            </p>
        </div>
    </header>

    <header v-else class="gh-masthead gh-inner">
        <h1>Tech Log</h1>
        <p>Config changes, installs, policy changes and staff changes, as they happened.</p>
    </header>

    <PostFeed v-if="posts.data.length" :posts="posts.data" />

    <section v-else-if="!searching" class="gh-empty gh-inner">
        <h2>Nothing published yet</h2>
        <p>
            Entries logged in the app appear here.
            <Link href="/" class="text-decoration-underline">
                Open the app
            </Link>
        </p>
    </section>

    <section v-else class="gh-empty gh-inner">
        <p>Try fewer or shorter words; every word has to match.</p>
    </section>
</template>
