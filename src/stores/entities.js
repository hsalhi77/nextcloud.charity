import { defineStore } from 'pinia'
import { post, postForm, put, del } from '../services/api.js'

function normaliseDate(item, field) {
	if (item && item[field] && typeof item[field] === 'object' && item[field].date) {
		item[field] = item[field].date.slice(0, 10)
	}
}

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
						const data = await post(`${endpoint}/getall`, params)
						this.items = this._normalise(data)
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
				const data = await post(`${endpoint}/${id}`)
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
				const data = await post(endpoint, payload)
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
				const data = await post(`/attachment/${objectId}/${objectType}`, {})
				this.items = data
			} catch (err) {
				this.error = err.message
				throw err
			} finally {
				this.loading = false
			}
		},
		async upload(objectType, objectId, file, tag = '') {
			this.uploading = true
			this.error = null
			try {
				const chunkSize = 1024 * 1024 // 1MB chunks
				const totalChunks = Math.ceil(file.size / chunkSize)
				const uploadId = Math.random().toString(36).substring(2) + Date.now().toString(36)

				for (let i = 0; i < totalChunks; i++) {
					const start = i * chunkSize
					const end = Math.min(start + chunkSize, file.size)
					const chunk = file.slice(start, end)
					const formData = new FormData()
					formData.append('chunk', chunk, file.name + '.part' + i)
					formData.append('index', i)
					formData.append('total', totalChunks)
					formData.append('uploadId', uploadId)
					await postForm('/attachment/chunk', formData)
				}

				const finalizeForm = new FormData()
				finalizeForm.append('objectType', objectType)
				finalizeForm.append('objectId', objectId)
				finalizeForm.append('uploadId', uploadId)
				finalizeForm.append('filename', file.name)
				finalizeForm.append('tag', tag || '')
				finalizeForm.append('total', totalChunks)
				const data = await postForm('/attachment/finalize', finalizeForm)
				if (data) this.items.push(data)
				return data
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
