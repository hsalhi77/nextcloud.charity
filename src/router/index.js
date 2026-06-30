import Vue from 'vue'
import VueRouter from 'vue-router'

Vue.use(VueRouter)

const routes = [
	{ path: '/', name: 'home', redirect: '/cases' },
	{ path: '/cases', name: 'cases', component: () => import('../views/Cases.vue') },
	{ path: '/payments', name: 'payments', component: () => import('../views/Payments.vue') },
	{ path: '/updates', name: 'updates', component: () => import('../views/Updates.vue') },
	{ path: '/settings', name: 'settings', component: () => import('../views/Settings.vue') },
	{ path: '/city', name: 'city', component: () => import('../views/City.vue') },
	{ path: '/casetype', name: 'casetype', component: () => import('../views/CaseType.vue') },
	{ path: '/updatetype', name: 'updatetype', component: () => import('../views/UpdateType.vue') },
]

export default new VueRouter({
	mode: 'history',
	base: OC.generateUrl('/apps/charity', {}),
	routes,
})
