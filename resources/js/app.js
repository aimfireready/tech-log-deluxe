import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import AppLayout from './layouts/AppLayout.vue';

const appName = import.meta.env.VITE_APP_NAME || 'Tech Log';

// Dates are stored in UTC; the server uses this to make date filters match the days shown here.
const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
router.on('before', (event) => {
    event.detail.visit.headers['X-Timezone'] = timezone;
});

createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.vue', { eager: true });
        const page = pages[`./pages/${name}.vue`];

        // Auth pages set their own layout; everything else gets the app shell.
        page.default.layout ??= AppLayout;

        return page;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: {
        color: '#4fa3ff',
    },
});
