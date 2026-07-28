import '../css/app.css';
import './bootstrap';
import 'vue-sonner/style.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h, Fragment } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import PageTransition from './Components/PageTransition.vue';
import Vue3Lottie from 'vue3-lottie';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.vue`,
            import.meta.glob('./Pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        return createApp({ 
            render: () => h(Fragment, [
                h(App, props),
                h(PageTransition)
            ]) 
        })
            .use(plugin)
            .use(ZiggyVue)
            .use(Vue3Lottie)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
