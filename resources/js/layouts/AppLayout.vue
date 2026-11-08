<script setup>
import { ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import ShortcutHelp from '../components/ShortcutHelp.vue';
import { useShortcuts } from '../composables/useShortcuts';

const page = usePage();
const searchInput = ref(null);
const query = ref(new URLSearchParams(window.location.search).get('q') ?? '');
const showHelp = ref(false);
const menuOpen = ref(false);

useShortcuts({
    '/': () => searchInput.value?.focus(),
    '?': () => (showHelp.value = true),
});

function search() {
    router.get('/', query.value ? { q: query.value } : {}, { preserveState: true, replace: true });
}

function logout() {
    router.post('/logout');
}
</script>

<template>
    <div class="min-vh-100">
        <header class="tl-topbar sticky-top border-bottom">
            <nav class="container tl-container d-flex align-items-center gap-3 py-2" aria-label="Main">
                <Link href="/" class="navbar-brand fw-semibold text-body text-decoration-none">
                    Tech Log
                </Link>

                <form class="tl-search flex-grow-1" role="search" @submit.prevent="search">
                    <label for="search" class="visually-hidden">Search entries</label>
                    <input
                        id="search"
                        ref="searchInput"
                        v-model="query"
                        type="search"
                        class="form-control form-control-sm"
                        placeholder="Search  ( / )"
                        @keydown.esc="searchInput.blur()"
                    >
                </form>

                <div class="ms-auto d-flex align-items-center gap-2">
                    <button
                        type="button"
                        class="btn btn-sm btn-link text-body-secondary text-decoration-none"
                        aria-label="Keyboard shortcuts"
                        @click="showHelp = true"
                    >
                        <kbd>?</kbd>
                    </button>

                    <div class="dropdown">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary dropdown-toggle"
                            :aria-expanded="menuOpen"
                            @click="menuOpen = !menuOpen"
                        >
                            {{ page.props.auth.user?.name }}
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" :class="{ show: menuOpen }">
                            <li>
                                <button type="button" class="dropdown-item" @click="logout">
                                    Log out
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>
        </header>

        <main class="container tl-container py-4">
            <slot />
        </main>

        <ShortcutHelp v-model:open="showHelp" />
    </div>
</template>
