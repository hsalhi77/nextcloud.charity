import Vue from 'vue'
import { translate, translatePlural } from '@nextcloud/l10n'
import { generateFilePath, getRootUrl } from '@nextcloud/router'
import App from './App.vue'
import router from './router'
import { createPinia, PiniaVuePlugin } from 'pinia'
import { useUserStore } from './stores/user.js'

Vue.use(PiniaVuePlugin)
const pinia = createPinia()

Vue.prototype.t = translate
Vue.prototype.n = translatePlural

// Dynamic public path so chunked imports load correctly on subdirectory URLs
__webpack_public_path__ = generateFilePath('charity', '', 'js/')

// CSP hack for Nextcloud webpack dev server
__webpack_nonce__ = btoa(getRootUrl() + generateFilePath('charity', '', 'js/'))

const app = new Vue({
    el: '#app',
    router,
    pinia,
    render: h => h(App),
})

// Fetch user groups on load
const userStore = useUserStore()
userStore.fetchGroups()

export default app
