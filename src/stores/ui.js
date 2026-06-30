import { defineStore } from 'pinia'

export const useUiStore = defineStore('ui', {
	state: () => ({
		slidePanelOpen: false,
		slidePanelMode: 'add',
		slidePanelEntity: null,
		slidePanelEntityType: '',
		slidePanelEntityId: null,
	}),

	actions: {
		openSlidePanel({ mode, entityType, entityId = null, entity = null }) {
			this.slidePanelMode = mode
			this.slidePanelEntityType = entityType
			this.slidePanelEntityId = entityId
			this.slidePanelEntity = entity
			this.slidePanelOpen = true
		},

		closeSlidePanel() {
			this.slidePanelOpen = false
			this.slidePanelEntity = null
			this.slidePanelEntityId = null
		},
	},
})
