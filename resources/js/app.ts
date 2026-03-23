import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { ZiggyVue } from 'ziggy-js';
import { createI18n } from 'vue-i18n'

const appName = import.meta.env.VITE_APP_NAME || 'Utocures';

createInertiaApp({
  title: (title) => (title ? `${title} - ${appName}` : appName),
  resolve: (name) => resolvePageComponent(`./pages/${name}.vue`, import.meta.glob<DefineComponent>('./pages/**/*.vue')),
  setup({ el, App, props, plugin }) {
    const locale = (props.initialPage.props.locale as string) || 'es'
    const translations = props.initialPage.props.translations as Record<string, any>

    const i18n = createI18n({
      legacy: false,
      locale,
      fallbackLocale: 'es',
      messages: { [locale as string]: translations },
    })

    createApp({ render: () => h(App, props) })
      .use(plugin)
      .use(ZiggyVue)
      .use(i18n)
      .mount(el);
  },
  progress: {
    color: '#4B5563',
  },
});
