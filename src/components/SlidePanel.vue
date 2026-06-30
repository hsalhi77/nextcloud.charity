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
					:mode="ui.slidePanelMode"
					:entity-type="ui.slidePanelEntityType"
					:entity="ui.slidePanelEntity" />
				<EntityDetail
					v-else
					:entity-type="ui.slidePanelEntityType"
					:entity-id="ui.slidePanelEntityId" />
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
	},
}
</script>

<style scoped>
.cm-slide-panel {
	position: absolute;
	top: 0;
	inset-inline-end: 0;
	width: min(420px, 100vw);
	max-width: 100vw;
	height: 100%;
	background: var(--color-main-background);
	border-inline-start: 1px solid var(--color-border);
	box-shadow: var(--color-box-shadow);
	z-index: 100;
	display: flex;
	flex-direction: column;
}

.cm-slide-panel__header {
	display: flex;
	align-items: center;
	justify-content: flex-end;
	padding: 2px 12px;
	border-bottom: none;
	background: transparent;
	min-height: 32px;
}

.cm-slide-panel__header h2 {
	display: none;
}

.cm-slide-panel__content {
	flex: 1;
	overflow-y: auto;
	padding: 4px 16px 16px;
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
