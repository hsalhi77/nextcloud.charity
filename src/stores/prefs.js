import { defineStore } from 'pinia'
import { get, post } from '../services/api.js'

const DEFAULT_PAGE_SIZE = 20
const DEFAULT_SORT_ORDER = 'newest'

export const usePrefsStore = defineStore('prefs', {
	state: () => ({
		defaultPageSize: DEFAULT_PAGE_SIZE,
		defaultSortOrder: DEFAULT_SORT_ORDER,
		recordsPerPage: '',
		sorts: {},
		loaded: false,
	}),

	getters: {
		pageSize: state => {
			const n = parseInt(state.recordsPerPage, 10)
			return Number.isFinite(n) && n > 0 ? n : state.defaultPageSize
		},
		sortFor: state => grid => {
			const user = state.sorts[grid]
			if (user && user.key) return { key: user.key, direction: user.direction }
			if (state.defaultSortOrder === 'oldest') return { key: 'id', direction: 'asc' }
			return { key: 'id', direction: 'desc' }
		},
	},

	actions: {
		async load() {
			const [config, prefs] = await Promise.all([
				get('/api/v1.0/config'),
				get('/api/v1.0/config/prefs'),
			])
			this.defaultPageSize = parseInt(config.defaultPageSize, 10) || DEFAULT_PAGE_SIZE
			this.defaultSortOrder = config.defaultSortOrder === 'oldest' ? 'oldest' : DEFAULT_SORT_ORDER
			this.recordsPerPage = prefs.recordsPerPage || ''
			this.sorts = prefs.sorts || {}
			this.loaded = true
		},
		async setRecordsPerPage(value) {
			this.recordsPerPage = String(value)
			await post('/api/v1.0/config/prefs', { recordsPerPage: String(value) })
		},
		async setSort(grid, sort) {
			const next = { ...this.sorts, [grid]: { key: sort.key, direction: sort.direction } }
			this.sorts = next
			await post('/api/v1.0/config/prefs', { sorts: next })
		},
	},
})
