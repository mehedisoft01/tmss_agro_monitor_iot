import { createApp } from 'vue';
import { createI18n } from 'vue-i18n';
import { createRouter, createWebHistory } from 'vue-router'

import App from './App.vue';
import { store } from './store';
import { useInitials } from './lib/initial';
import {routes} from './router/web'

const app = createApp(App);
const locale = window.locale || 'en'; // Add fallback locale

const { addLocaleToJson, loadLocaleMessages } = useInitials();
// Router

const router = createRouter({
    history: createWebHistory('/auth'),
    routes: routes,
    linkActiveClass: 'mm-active',
    linkExactActiveClass: ''
});

// Plugins
import ToastPlugin from 'vue-toast-notification';
import 'vue-toast-notification/dist/theme-bootstrap.css';

import VueSweetalert2 from 'vue-sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

import { vValidate } from "@/plugins/validator/v-validate";
import datepicker from "@/plugins/datepicker.vue";

async function bootstrap() {
    try {
        // Load messages with better error handling
        const messagesJson = await loadLocaleMessages();
        let enMessages;

        try {
            enMessages = JSON.parse(messagesJson);
        } catch (parseError) {
            console.error('Failed to parse locale messages:', parseError);
            enMessages = {}; // Fallback to empty messages
        }

        // I18n
        const i18n = createI18n({
            legacy: false,
            locale: locale,
            fallbackLocale: 'en', // Explicit fallback
            messages: { [locale]: enMessages },
            missing: async (locale, key) => {
                if (locale === 'en') {
                    await addLocaleToJson(key);
                }
                return key; // Return key instead of 'missing:' prefix
            }
        });

        // Register plugins in logical order
        app.use(i18n);
        app.use(store);
        app.use(router);

        // UI plugins
        app.use(ToastPlugin);
        app.use(VueSweetalert2);

        // Directives and components
        app.directive('validate', vValidate);
        app.component('datepicker', datepicker);

        // Mount
        app.mount('#app');

    } catch (err) {
        console.error('Failed to bootstrap app:', err);
        // Consider showing user-friendly error message
    }
}

// Handle unhandled promise rejections
window.addEventListener('unhandledrejection', event => {
    console.error('Unhandled promise rejection:', event.reason);
});

bootstrap();