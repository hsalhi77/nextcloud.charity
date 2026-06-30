import Vue from 'vue'
import { translate, translatePlural } from '@nextcloud/l10n'
import { generateFilePath, getRootUrl } from '@nextcloud/router'
import App from './App.vue'
import router from './router'
import { createPinia, PiniaVuePlugin } from 'pinia'

Vue.use(PiniaVuePlugin)
const pinia = createPinia()

Vue.prototype.t = translate
Vue.prototype.n = translatePlural

// CSP hack for Nextcloud webpack dev server
__webpack_nonce__ = btoa(getRootUrl() + generateFilePath('charity', '', 'js/'))

export default new Vue({
    el: '#app',
    router,
    pinia,
    render: h => h(App),
})
