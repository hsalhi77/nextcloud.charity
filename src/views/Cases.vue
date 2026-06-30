<template>
	<div class="cm-view">
		<header class="cm-view__header">
			<h1>{{ t('charity', 'Cases') }}</h1>
			<div class="cm-view__actions">
				<NcButton type="primary" @click="openAddPanel">
					<template #icon>
						<PlusIcon :size="16" />
					</template>
					{{ t('charity', 'Add Case') }}
				</NcButton>
			</div>
		</header>

		<div v-if="casesStore.loading" class="cm-view__loading">
			<NcLoadingIcon :size="32" />
		</div>

		<EntityTable v-else
			:columns="columns"
			:items="casesStore.items"
			:actions="actions"
			:empty-text="t('charity', 'No cases found')"
			@row-click="openDetailPanel"
			@action="onAction" />
	</div>
</template>

<script>
import { NcButton, NcLoadingIcon } from '@nextcloud/vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import EntityTable from '../components/EntityTable.vue'
import { useCasesStore, useCaseTypesStore } from '../stores/entities.js'
import { useUiStore } from '../stores/ui.js'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'Cases',
	components: {
		NcButton,
		NcLoadingIcon,
		PlusIcon,
		EntityTable,
	},
	setup() {
		const casesStore = useCasesStore()
		const caseTypesStore = useCaseTypesStore()
		const ui = useUiStore()
		return {
			casesStore,
			caseTypesStore,
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
				{ key: 'firstName', label: t('charity', 'First Name'), width: '15%' },
				{ key: 'lastName', label: t('charity', 'Last Name'), width: '15%' },
				{ key: 'idNumber', label: t('charity', 'ID#'), width: '10%' },
				{ key: 'caseTypeId', label: t('charity', 'Case Type'), width: '15%', formatter: this.formatCaseType },
				{ key: 'dateAdded', label: t('charity', 'Date Added'), width: '15%', formatter: this.formatDate },
				{ key: 'referredBy', label: t('charity', 'Referred By'), width: '15%' },
			]
		},
	},
	async mounted() {
		await Promise.all([
			this.casesStore.fetchAll(),
			this.caseTypesStore.fetchAll(),
		])
	},
	methods: {
		formatDate(date) {
			if (!date) return ''
			return new Date(date).toLocaleDateString()
		},
		formatCaseType(id) {
			const type = this.caseTypesStore.byId(id)
			return type?.title || ''
		},
		openAddPanel() {
			this.ui.openSlidePanel({ mode: 'add', entityType: 'cc_Case' })
		},
		openDetailPanel(item) {
			this.ui.openSlidePanel({ mode: 'detail', entityType: 'cc_Case', entityId: item.id })
		},
		openEditPanel(item) {
			this.ui.openSlidePanel({ mode: 'edit', entityType: 'cc_Case', entity: item })
		},
		onAction({ name, item }) {
			if (name === 'edit') this.openEditPanel(item)
			if (name === 'delete') this.deleteCase(item)
		},
		async deleteCase(item) {
			if (!confirm(t('charity', 'Are you sure you want to delete this case?'))) return
			try {
				await this.casesStore.remove(item.id)
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
