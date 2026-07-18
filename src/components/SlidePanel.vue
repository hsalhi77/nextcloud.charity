<template>
	<transition name="slide">
		<div class="cm-slide-panel-overlay" @click="ui.closeSlidePanel">
			<aside class="cm-slide-panel" role="dialog" aria-modal="true" @click.stop>
				<header class="cm-slide-panel__header">
					<h2>{{ title }}</h2>
					<NcButton type="tertiary" :aria-label="t('charity', 'Close panel')" @click="ui.closeSlidePanel">
						<template #icon>
							<CloseIcon :size="20" />
						</template>
					</NcButton>
				</header>
				<div class="cm-slide-panel__content">
					<EntityForm
						v-if="ui.slidePanelMode === 'add' || ui.slidePanelMode === 'edit'"
						ref="entityForm"
						:mode="ui.slidePanelMode"
						:entity-type="ui.slidePanelEntityType"
						:entity="ui.slidePanelEntity" />
					<EntityDetail
						v-else
						:entity-type="ui.slidePanelEntityType"
						:entity-id="ui.slidePanelEntityId" />
				</div>
				<div v-if="ui.slidePanelMode === 'add' || ui.slidePanelMode === 'edit'" class="cm-slide-panel__footer">
					<NcButton type="secondary" @click="ui.closeSlidePanel">
						{{ t('charity', 'Cancel') }}
					</NcButton>
					<NcButton type="primary" @click="submitForm">
						{{ submitLabel }}
					</NcButton>
				</div>
			</aside>
		</div>
	</transition>
</template>

<script>
import { NcButton } from '@nextcloud/vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
import EntityForm from './EntityForm.vue'
import EntityDetail from './EntityDetail.vue'
import { useUiStore } from '../stores/ui.js'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'SlidePanel',
	components: {
		NcButton,
		CloseIcon,
		EntityForm,
		EntityDetail,
	},
	setup() {
		const ui = useUiStore()
		return { ui, t }
	},
	computed: {
		title() {
			const type = this.ui.slidePanelEntityType
			const mode = this.ui.slidePanelMode
			const typeLabel = t('charity', type.replace(/^cc_/, '').replace(/_/g, ' '))
			if (mode === 'add') return t('charity', 'Add {type}', { type: typeLabel })
			if (mode === 'edit') return t('charity', 'Edit {type}', { type: typeLabel })
			return t('charity', 'Details')
		},
		submitLabel() {
			return this.ui.slidePanelMode === 'add' ? t('charity', 'Create') : t('charity', 'Save Changes')
		},
	},
	methods: {
		submitForm() {
			if (this.$refs.entityForm) {
				this.$refs.entityForm.submit()
			}
		},
	},
}
</script>

<style scoped>
.cm-slide-panel-overlay {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 9998;
	background: rgba(0, 0, 0, 0.2);
}

.cm-slide-panel {
	position: fixed;
	top: 50px;
	right: 0;
	bottom: 0;
	width: min(420px, 100vw);
	max-width: 100vw;
	background: var(--color-main-background);
	border-left: 1px solid var(--color-border);
	box-shadow: var(--color-box-shadow);
	z-index: 9999;
	display: flex;
	flex-direction: column;
}

.cm-slide-panel__header {
	flex-shrink: 0;
	height: 44px;
	display: flex;
	align-items: center;
	justify-content: flex-end;
	padding: 0 12px;
}

.cm-slide-panel__header h2 {
	display: none;
}

.cm-slide-panel__content {
	flex: 1;
	min-height: 0;
	overflow-y: auto;
	padding: 4px 16px 16px;
}

.cm-slide-panel__footer {
	flex-shrink: 0;
	height: 48px;
	display: flex;
	align-items: center;
	justify-content: flex-end;
	gap: 8px;
	padding: 12px 16px;
	border-top: 1px solid var(--color-border);
	background: var(--color-main-background);
}

.slide-enter-active,
.slide-leave-active {
	transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.slide-enter,
.slide-leave-to {
	transform: translateX(100%);
}
</style>
