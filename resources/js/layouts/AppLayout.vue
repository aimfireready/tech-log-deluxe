<script setup>
import { ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import FlashToasts from '../components/FlashToasts.vue';
import QuickAddPanel from '../components/QuickAddPanel.vue';
import ShortcutHelp from '../components/ShortcutHelp.vue';
import { useQuickAdd } from '../composables/useQuickAdd';
import { useShortcuts } from '../composables/useShortcuts';
import { useTimeline } from '../composables/useTimeline';

const page = usePage();
const quickAdd = useQuickAdd();
const { currentFilters, setFilter } = useTimeline();
const searchInput = ref(null);
const query = ref(currentFilters().q ?? '');
const showHelp = ref(false);
const menuOpen = ref(false);

// Follow the URL when filters change elsewhere (chips, back button, tag clicks).
watch(() => page.props.filters?.q, (q) => (query.value = q ?? ''));

useShortcuts({
    '/': () => searchInput.value?.focus(),
    '?': () => (showHelp.value = true),
    n: () => quickAdd.show(),
});

function search() {
    setFilter('q', query.value);
}

function logout() {
    router.post('/logout');
}
</script>

<template>
    <div class="min-vh-100">
        <header class="tl-topbar sticky-top border-bottom">
            <nav class="container tl-container d-flex align-items-center gap-2 gap-sm-3 py-2" aria-label="Main">
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
                    <button type="button" class="btn btn-sm btn-primary text-nowrap" @click="quickAdd.show()">
                        <span class="d-none d-sm-inline">New entry</span>
                        <span class="d-sm-none" aria-hidden="true">+</span>
                        <span class="d-sm-none visually-hidden">New entry</span>
                    </button>

                    <button
                        type="button"
                        class="btn btn-sm btn-link text-body-secondary text-decoration-none d-none d-sm-inline-block"
                        aria-label="Keyboard shortcuts"
                        @click="showHelp = true"
                    >
                        <kbd>?</kbd>
                    </button>

                    <div class="dropdown">
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary dropdown-toggle text-truncate tl-user-menu"
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

        <QuickAddPanel />
        <FlashToasts />
        <ShortcutHelp v-model:open="showHelp" />
    </div>
</template>
