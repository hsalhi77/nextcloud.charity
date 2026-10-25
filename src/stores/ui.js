import { defineStore } from 'pinia'

export const useUiStore = defineStore('ui', {
	state: () => ({
		slidePanelOpen: false,
		slidePanelMode: 'add',
		slidePanelEntity: null,
		slidePanelEntityType: '',
		slidePanelEntityId: null,
		slidePanelInitialTab: null,
		filters: {},
	}),

	actions: {
		openSlidePanel({ mode, entityType, entityId = null, entity = null, tab = null }) {
			this.slidePanelMode = mode
			this.slidePanelEntityType = entityType
			this.slidePanelEntityId = entityId
			this.slidePanelEntity = entity
			this.slidePanelInitialTab = tab
			this.slidePanelOpen = true
		},

		closeSlidePanel() {
			this.slidePanelOpen = false
			this.slidePanelEntity = null
			this.slidePanelEntityId = null
			this.slidePanelInitialTab = null
		},

		setFilter(entity, filter) {
			this.filters[entity] = filter
		},

		clearFilter(entity) {
			delete this.filters[entity]
		},

		getFilter(entity) {
			return this.filters[entity] || null
		},
	},
})
