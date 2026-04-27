import {createApp, h} from 'vue';
import {createInertiaApp} from '@inertiajs/vue3';
import {resolvePageComponent} from 'laravel-vite-plugin/inertia-helpers';
import {ZiggyVue} from '../../vendor/tightenco/ziggy';
import {MotionPlugin} from '@vueuse/motion';

// PrimeVue core & services
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';
import ToastService from 'primevue/toastservice';
import ConfirmationService from 'primevue/confirmationservice';
import Form from '@primevue/forms/form';
import FormField from '@primevue/forms/formfield';
import IftaLabel from 'primevue/iftalabel';

// Manual component imports for stability
import Button from 'primevue/button';
import Avatar from 'primevue/avatar';
import Tag from 'primevue/tag';
import Chip from 'primevue/chip';
import Dialog from 'primevue/dialog';
import Divider from 'primevue/divider';
import Select from 'primevue/select';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import InputNumber from 'primevue/inputnumber';
import Stepper from 'primevue/stepper';
import StepList from 'primevue/steplist';
import StepPanels from 'primevue/steppanels';
import StepItem from 'primevue/stepitem';
import Step from 'primevue/step';
import StepPanel from 'primevue/steppanel';
import Menu from 'primevue/menu';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';

import 'primeicons/primeicons.css';

// PrimeVue directives (not auto-imported)
import Tooltip from 'primevue/tooltip';
import BadgeDirective from 'primevue/badgedirective';
import Ripple from 'primevue/ripple';
import AnimateOnScroll from 'primevue/animateonscroll';
import StyleClass from 'primevue/styleclass';
import FocusTrap from 'primevue/focustrap';

// i18n
import i18n, {setLocale} from './plugins/i18n';

const appName = import.meta.env.VITE_APP_NAME || 'Nexo Digital Store';

// ── Apply theme from localStorage BEFORE first render (avoids flash) ──────────
;(function () {
    const theme = localStorage.getItem('nexo-theme') || 'nexo';
    const mode = localStorage.getItem('nexo-mode') || 'dark';
    const locale = localStorage.getItem('nexo-locale') || 'es';
    document.documentElement.setAttribute('data-theme', theme);
    document.documentElement.setAttribute('data-theme-mode', mode);
    document.documentElement.setAttribute('lang', locale);
})();

createInertiaApp({
    title: (title) => title ? `${title} — ${appName}` : appName,
    resolve: (name) =>
        resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({el, App, props, plugin}) {
        const app = createApp({render: () => h(App, props)});

        app.use(plugin);
        app.use(ZiggyVue);
        app.use(i18n);
        app.use(MotionPlugin);

        // PrimeVue — darkModeSelector synced with data-theme-mode attribute
        app.use(PrimeVue, {
            theme: {
                preset: Aura,
                options: {
                    darkModeSelector: '[data-theme-mode="dark"]',

                },
            },
            ripple: true,
        });
        app.use(ToastService);
        app.use(ConfirmationService);

        // Register components manually for better compatibility with PrimeVue 4
        app.component('Button', Button);
        app.component('Avatar', Avatar);
        app.component('Tag', Tag);
        app.component('Chip', Chip);
        app.component('Dialog', Dialog);
        app.component('Divider', Divider);
        app.component('Select', Select);
        app.component('DataTable', DataTable);
        app.component('Column', Column);
        app.component('InputNumber', InputNumber);
        app.component('Stepper', Stepper);
        app.component('StepList', StepList);
        app.component('StepPanels', StepPanels);
        app.component('StepItem', StepItem);
        app.component('Step', Step);
        app.component('StepPanel', StepPanel);
        app.component('Menu', Menu);
        app.component('Toast', Toast);
        app.component('ConfirmDialog', ConfirmDialog);

        // Form components from @primevue/forms (not auto-resolvable)
        app.component('Form', Form);
        app.component('FormField', FormField);
        app.component('IftaLabel', IftaLabel);

        // PrimeVue directives
        app.directive('tooltip', Tooltip);
        app.directive('badge', BadgeDirective);
        app.directive('ripple', Ripple);
        app.directive('animateonscroll', AnimateOnScroll);
        app.directive('styleclass', StyleClass);
        app.directive('focustrap', FocusTrap);

        // NOTE: PrimeVue components are auto-imported via unplugin-vue-components
        // (PrimeVueResolver in vite.config.js) — no manual .component() calls needed.

        // Sync locale from Inertia shared props after mount
        app.mixin({
            mounted() {
                const ui = this.$page?.props?.ui;
                if (ui?.locale) setLocale(ui.locale);
            },
        });

        app.mount(el);
    },
    progress: {
        color: 'var(--nx-primary, #6366f1)',
        showSpinner: true,
    },
});
