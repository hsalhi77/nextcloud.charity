<template>
	<div class="cm-view">
		<header class="cm-view__header">
			<h1>{{ t('charity', 'Case Types') }}</h1>
			<div class="cm-view__actions">
				<NcButton type="primary" @click="openAddPanel">
					<template #icon>
						<PlusIcon :size="16" />
					</template>
					{{ t('charity', 'Add Case Type') }}
				</NcButton>
			</div>
		</header>

		<div v-if="caseTypesStore.loading" class="cm-view__loading">
			<NcLoadingIcon :size="32" />
		</div>

		<EntityTable v-else
			:columns="columns"
			:items="caseTypesStore.items"
			:actions="actions"
			:empty-text="t('charity', 'No case types found')"
			@row-click="openEditPanel"
			@action="onAction" />
	</div>
</template>

<script>
import { NcButton, NcLoadingIcon } from '@nextcloud/vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import EntityTable from '../components/EntityTable.vue'
import { useCaseTypesStore } from '../stores/entities.js'
import { useUiStore } from '../stores/ui.js'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'CaseType',
	components: {
		NcButton,
		NcLoadingIcon,
		PlusIcon,
		EntityTable,
	},
	setup() {
		const caseTypesStore = useCaseTypesStore()
		const ui = useUiStore()
		return {
			caseTypesStore,
			ui,
			t,
		}
	},
	data() {
		return {
			columns: [
				{ key: 'title', label: t('charity', 'Title') },
			],
			actions: [
				{ name: 'edit', label: t('charity', 'Edit'), icon: 'icon-edit' },
				{ name: 'delete', label: t('charity', 'Delete'), icon: 'icon-delete' },
			],
		}
	},
	async mounted() {
		await this.caseTypesStore.fetchAll()
	},
	methods: {
		openAddPanel() {
			this.ui.openSlidePanel({ mode: 'add', entityType: 'cc_CaseType' })
		},
		openEditPanel(item) {
			this.ui.openSlidePanel({ mode: 'edit', entityType: 'cc_CaseType', entity: item })
		},
		onAction({ name, item }) {
			if (name === 'edit') this.openEditPanel(item)
			if (name === 'delete') this.deleteCaseType(item)
		},
		async deleteCaseType(item) {
			if (!confirm(t('charity', 'Are you sure you want to delete this case type?'))) return
			try {
				await this.caseTypesStore.remove(item.id)
			} catch (err) {
				console.error(err)
				alert(err.message)
			}
		},
	},
}
</script>

<style scoped>
.cm-view {
    padding: 24px;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.cm-view__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.cm-view__header h1 {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
}

.cm-view__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.cm-view__loading {
    display: flex;
    justify-content: center;
    padding: 48px;
}
</style>
