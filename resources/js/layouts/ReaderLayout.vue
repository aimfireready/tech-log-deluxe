<script setup>
import '../../css/reader.css';
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useShortcuts } from '../composables/useShortcuts';

const page = usePage();
const searchInput = ref(null);
const query = ref(page.props.filters?.q ?? '');

watch(() => page.props.filters?.q, (q) => (query.value = q ?? ''));

useShortcuts({
    '/': () => searchInput.value?.focus(),
});

function search() {
    const q = query.value.trim();
    router.get('/read', q ? { q } : {});
}

const isCurrentTag = (slug) => page.url === `/read/tags/${slug}`;

// Visitors who came in with the share key aren't signed in, so they get no app links.
const signedIn = computed(() => Boolean(page.props.auth?.user));
</script>

<template>
    <div class="gh">
        <header class="gh-head">
            <nav class="gh-inner gh-head-inner" aria-label="Reading view">
                <Link href="/read" class="gh-head-brand">
                    Tech Log
                </Link>

                <div class="gh-head-menu">
                    <Link href="/read" :aria-current="page.url === '/read' ? 'page' : undefined">
                        Home
                    </Link>
                    <Link
                        v-for="tag in page.props.navTags"
                        :key="tag.slug"
                        :href="`/read/tags/${tag.slug}`"
                        :aria-current="isCurrentTag(tag.slug) ? 'page' : undefined"
                    >
                        {{ tag.name }}
                    </Link>
                </div>

                <div class="gh-head-actions">
                    <form role="search" @submit.prevent="search">
                        <label for="reader-search" class="visually-hidden">Search posts</label>
                        <input
                            id="reader-search"
                            ref="searchInput"
                            v-model="query"
                            type="search"
                            class="gh-search"
                            placeholder="Search  ( / )"
                            @keydown.esc="searchInput.blur()"
                        >
                    </form>
                    <Link v-if="signedIn" href="/" class="gh-button">
                        Open app
                    </Link>
                </div>
            </nav>
        </header>

        <main>
            <slot />
        </main>

        <footer class="gh-foot">
            <div class="gh-inner gh-foot-inner">
                <span>Tech Log · Self-Hosted Tech Journal</span>
                <nav aria-label="Footer">
                    <Link href="/read">
                        Latest
                    </Link>
                    <Link v-if="signedIn" href="/">
                        Timeline
                    </Link>
                    <button
                        v-if="signedIn"
                        type="button"
                        class="btn btn-link p-0 text-reset text-decoration-none small"
                        @click="router.post('/logout')"
                    >
                        Log out
                    </button>
                </nav>
            </div>
        </footer>
    </div>
</template>
