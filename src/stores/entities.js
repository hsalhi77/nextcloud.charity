import { defineStore } from 'pinia'
import { get, post, put, del } from '../services/api.js'

/**
 * Normalise a date field from the backend envelope to YYYY-MM-DD
 * @param {object} item - The entity item
 * @param {string} field - The date field name
 */
function normaliseDate(item, field) {
	if (item && item[field] && typeof item[field] === 'object' && item[field].date) {
		item[field] = item[field].date.slice(0, 10)
	}
}

/**
 * Unwrap the backend envelope when it exists
 * @param {any} res - The response or raw data
 * @return {any} The unwrapped data
 */
function unwrap(res) {
	if (res && typeof res === 'object' && 'data' in res) {
		return res.data
	}
	return res
}

/**
 * Generic entity store factory
 * @param {string} name - The Pinia store name
 * @param {string} endpoint - The API endpoint prefix
 * @param {string[]} dateFields - Fields to normalise as dates
 */
function createEntityStore(name, endpoint, dateFields = []) {
	return defineStore(name, {
		state: () => ({
			items: [],
			loading: false,
			error: null,
			_pendingFetch: null,
		}),

		getters: {
			byId: state => id => state.items.find(item => item.id === id),
		},

		actions: {
			_normalise(items) {
				if (!Array.isArray(items)) items = [items]
				items.forEach(item => {
					dateFields.forEach(field => normaliseDate(item, field))
				})
				return items
			},

			async fetchAll(params = {}) {
				if (this._pendingFetch) {
					return this._pendingFetch
				}
				this.loading = true
				this.error = null
				this._pendingFetch = (async () => {
					try {
						const res = await post(`${endpoint}/getall`, params)
						this.items = Array.isArray(res.data) ? this._normalise(res.data) : []
					} catch (err) {
						this.error = err.message
						throw err
					} finally {
						this.loading = false
						this._pendingFetch = null
					}
				})()
				return this._pendingFetch
			},

			async fetchOne(id) {
				const data = unwrap(await get(`${endpoint}/${id}`))
				const item = this._normalise(data)[0]
				if (item) {
					const idx = this.items.findIndex(i => i.id === id)
					if (idx !== -1) {
						this.items.splice(idx, 1, item)
					} else {
						this.items.push(item)
					}
				}
				return item
			},

			async create(payload) {
				const data = unwrap(await post(endpoint, payload))
				const normalised = this._normalise(data)[0]
				if (normalised) this.items.push(normalised)
				return normalised
			},

			async update(id, payload) {
				await put(`${endpoint}/${id}`, payload)
				return await this.fetchOne(id)
			},

			async remove(id) {
				await del(`${endpoint}/${id}`)
				const index = this.items.findIndex(item => item.id === id)
				if (index !== -1) this.items.splice(index, 1)
			},

			setItems(items) {
				this.items = this._normalise(items)
			},
		},
	})
}

export const useCasesStore = createEntityStore('cases', '/cases', ['dob', 'dateAdded'])
export const usePaymentsStore = createEntityStore('payments', '/payments', ['paymentDate'])
export const useUpdatesStore = createEntityStore('updates', '/updates', ['updateDate'])
export const useCitiesStore = createEntityStore('cities', '/city')
export const useCaseTypesStore = createEntityStore('caseTypes', '/casetype')
export const useUpdateTypesStore = createEntityStore('updateTypes', '/updatetype')

export const useAttachmentsStore = defineStore('attachments', {
	state: () => ({
		items: [],
		loading: false,
		uploading: false,
		error: null,
	}),

	getters: {
		forObject: state => (objectType, objectId) => state.items.filter(a => a.object_type === objectType && a.object_id === objectId),
	},

	actions: {
		async fetchByObject(objectType, objectId) {
			this.loading = true
			this.error = null
			try {
				const res = await post(`/attachment/${objectId}/${objectType}`, {})
				this.items = Array.isArray(res.data) ? res.data : []
			} catch (err) {
				this.error = err.message
				throw err
			} finally {
				this.loading = false
			}
		},
		async upload(objectType, objectId, file) {
			this.uploading = true
			this.error = null
			try {
				const res = await post('/attachment', {
					objectType,
					objectId,
					file: {
						name: file.name,
						data: file.data,
						size: file.size,
						tag: file.tag || '',
						description: file.description || '',
					},
				})
				if (res.data) this.items.push(res.data)
				return res.data
			} catch (err) {
				this.error = err.message
				throw err
			} finally {
				this.uploading = false
			}
		},
		async remove(id) {
			await del(`/attachment/${id}`)
			const idx = this.items.findIndex(a => a.id === id)
			if (idx !== -1) this.items.splice(idx, 1)
		},
	},
})
