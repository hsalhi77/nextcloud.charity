<template>
	<div class="cm-view">
		<header class="cm-view__header">
			<h1>{{ t('charity', 'Updates') }}</h1>
			<div class="cm-view__actions">
				<NcButton type="primary" @click="openAddPanel">
					<template #icon>
						<PlusIcon :size="16" />
					</template>
					{{ t('charity', 'Add Update') }}
				</NcButton>
			</div>
		</header>

		<div v-if="updatesStore.loading" class="cm-view__loading">
			<NcLoadingIcon :size="32" />
		</div>

		<EntityTable v-else
			:columns="columns"
			:items="updatesStore.items"
			:actions="actions"
			:empty-text="t('charity', 'No updates found')"
			@row-click="openDetailPanel"
			@action="onAction" />
	</div>
</template>

<script>
import { NcButton, NcLoadingIcon } from '@nextcloud/vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import EntityTable from '../components/EntityTable.vue'
import { useUpdatesStore, useCasesStore, useUpdateTypesStore } from '../stores/entities.js'
import { useUiStore } from '../stores/ui.js'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'Updates',
	components: {
		NcButton,
		NcLoadingIcon,
		PlusIcon,
		EntityTable,
	},
	setup() {
		const updatesStore = useUpdatesStore()
		const casesStore = useCasesStore()
		const updateTypesStore = useUpdateTypesStore()
		const ui = useUiStore()
		return {
			updatesStore,
			casesStore,
			updateTypesStore,
			ui,
			t,
		}
	},
	data() {
		return {
			actions: [
				{ name: 'edit', label: t('charity', 'Edit'), icon: 'icon-edit' },
				{ name: 'delete', label: t('charity', 'Delete'), icon: 'icon-delete' },
			],
		}
	},
	computed: {
		columns() {
			return [
				{ key: 'id', label: t('charity', '#'), width: '8%', formatter: this.formatId },
				{ key: 'caseId', label: t('charity', 'Case'), width: '12%', formatter: this.formatCase },
				{ key: 'updateDate', label: t('charity', 'Update Date'), width: '15%', formatter: this.formatDate },
				{ key: 'updateTypeId', label: t('charity', 'Update Type'), width: '15%', formatter: this.formatUpdateType },
				{ key: 'updateBy', label: t('charity', 'Updated By'), width: '15%' },
				{ key: 'description', label: t('charity', 'Description'), width: '25%' },
			]
		},
	},
	async mounted() {
		await Promise.all([
			this.updatesStore.fetchAll(),
			this.casesStore.fetchAll(),
			this.updateTypesStore.fetchAll(),
		])
	},
	methods: {
		formatId(id) {
			return String(id).padStart(10, '0')
		},
		formatDate(date) {
			if (!date) return ''
			return new Date(date).toLocaleDateString()
		},
		formatCase(id) {
			if (!id) return ''
			return String(id).padStart(10, '0')
		},
		formatUpdateType(id) {
			const type = this.updateTypesStore.byId(id)
			return type?.title || ''
		},
		openAddPanel() {
			this.ui.openSlidePanel({ mode: 'add', entityType: 'cc_Update' })
		},
		openDetailPanel(item) {
			this.ui.openSlidePanel({ mode: 'detail', entityType: 'cc_Update', entityId: item.id })
		},
		openEditPanel(item) {
			this.ui.openSlidePanel({ mode: 'edit', entityType: 'cc_Update', entity: item })
		},
		onAction({ name, item }) {
			if (name === 'edit') this.openEditPanel(item)
			if (name === 'delete') this.deleteUpdate(item)
		},
		async deleteUpdate(item) {
			if (!confirm(t('charity', 'Are you sure you want to delete this update?'))) return
			try {
				await this.updatesStore.remove(item.id)
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
