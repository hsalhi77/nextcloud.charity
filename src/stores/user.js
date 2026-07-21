import { defineStore } from 'pinia'
import { post } from '../services/api.js'

export const useUserStore = defineStore('user', {
	state: () => ({
		groups: [],
		loading: false,
		error: null,
	}),

	getters: {
		isAdmin: state => state.groups.some(g => g.toLowerCase() === 'admin'),
		isAdminOrCharityAdmin: state => state.groups.some(g => g.toLowerCase() === 'admin' || g.toLowerCase() === 'charity admin'),
		isCharityUser: state => {
			const lower = state.groups.map(g => g.toLowerCase())
			return !lower.includes('admin') && !lower.includes('charity admin')
		},
	},

	actions: {
		async fetchGroups() {
			this.loading = true
			this.error = null
			try {
				const groups = await post('/team/userGroups', {})
				this.groups = Array.isArray(groups) ? groups : []
			} catch (err) {
				this.error = err.message
				throw err
			} finally {
				this.loading = false
			}
		},
	},
})
