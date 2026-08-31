import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

import '../css/app.css'
import 'vue-sonner/style.css'

createInertiaApp({
    resolve: async (name) => {
        const pages = import.meta.glob('./pages/**/*.vue')
        const importPage = pages[`./pages/${name}.vue`]
        if (!importPage) throw new Error(`Unknown page ${name}`)

        const module = await importPage()
        const page = module.default
        page.layout ??= (page) => page
        return page
    },

    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })

        app.use(plugin)

        app.config.globalProperties.route = (name, params = {}, absolute = true) =>
            route(name, params, absolute, window.Ziggy)

        app.mount(el)
    },
})
