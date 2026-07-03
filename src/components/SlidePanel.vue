<template>
	<transition name="slide">
		<aside class="cm-slide-panel" role="dialog" aria-modal="true">
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
.cm-slide-panel {
	position: absolute;
	top: 0;
	inset-inline-end: 0;
	bottom: 0;
	width: min(420px, 100vw);
	max-width: 100vw;
	background: var(--color-main-background);
	border-inline-start: 1px solid var(--color-border);
	box-shadow: var(--color-box-shadow);
	z-index: 100;
}

.cm-slide-panel__header {
	position: absolute;
	top: 0;
	inset-inline-start: 0;
	inset-inline-end: 0;
	height: 32px;
	display: flex;
	align-items: center;
	justify-content: flex-end;
	padding: 2px 12px;
}

.cm-slide-panel__header h2 {
	display: none;
}

.cm-slide-panel__content {
	position: absolute;
	top: 32px;
	bottom: 56px;
	inset-inline-start: 0;
	inset-inline-end: 0;
	overflow-y: auto;
	padding: 4px 16px 16px;
}

.cm-slide-panel__footer {
	position: absolute;
	bottom: 0;
	inset-inline-start: 0;
	inset-inline-end: 0;
	height: 56px;
	display: flex;
	align-items: center;
	justify-content: flex-end;
	gap: 8px;
	padding: 0 16px;
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
