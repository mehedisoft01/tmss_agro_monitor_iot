
import { createApp } from 'vue';
import { createI18n } from 'vue-i18n';

import router from './router';
import App from './App.vue';
import { store } from './store';
import { useInitials } from './lib/initial';
const {mapRoutes, loanInitialJson, addLocaleToJson, loadLocaleMessages, loadBackendRoutes} = useInitials();

import directives from './directives';

const app = createApp(App);

directives(app);

// 🔹 Make sure these exist (can also come from env/config)
const baseUrl = window.baseUrl;
const locale = window.locale;

// Plugins
import '../../public/backend/css/select2_custom.css';
import 'jquery-ui/themes/base/all.css';
import 'vue-toast-notification/dist/theme-bootstrap.css';
import 'sweetalert2/dist/sweetalert2.min.css';

import ToastPlugin from 'vue-toast-notification';
import 'vue-toast-notification/dist/theme-sugar.css';
app.use(ToastPlugin);

import VueSweetalert2 from 'vue-sweetalert2';
app.use(VueSweetalert2);

import { vValidate } from "@/plugins/validator/v-validate";
app.directive('validate', vValidate);

import datepicker from "@/plugins/datepicker.vue";
app.component('datepicker', datepicker);


async function bootstrap() {
    try {
        const initialJson = JSON.parse(await loanInitialJson());
        // const enMessages = JSON.parse(await loadLocaleMessages());
        // const backendRoutes = JSON.parse(await loadBackendRoutes());

        const enMessages = initialJson.locale;
        const backendRoutes = initialJson.routes;

        const dynamicRoutes = mapRoutes(backendRoutes);

        router.addRoute({
            path: '/',
            redirect: '/dashboard'
        });

        dynamicRoutes.forEach(route => {
            router.addRoute(route);
        });

        const i18n = createI18n({
            legacy: false,
            locale: locale,
            fallbackLocale: locale,
            messages: enMessages,
            missing: async (locale, key) => {
                if (locale === 'en'){
                    await addLocaleToJson(key);
                }
                return `missing:${key}`
            }
        });

        app.use(i18n);
        app.use(router);
        app.use(store);

        app.mount('#app');

    } catch (err) {
        console.error('Failed to bootstrap app:', err);
    }
}

bootstrap();
