<template>
	<div class="cm-view">
		<header class="cm-view__header">
			<h1>{{ t('charity', 'Assets') }}</h1>
			<div class="cm-view__actions">
				<NcButton type="secondary" @click="filtersVisible = !filtersVisible">
					{{ t('charity', 'Filters') }}
				</NcButton>
				<NcButton type="secondary" @click="exportAssets">
					<template #icon>
						<DownloadIcon :size="16" />
					</template>
					{{ t('charity', 'Export to Excel') }}
				</NcButton>
			</div>
		</header>

		<EntityFilter v-if="filtersVisible"
			:fields="filterFields"
			@filter="applyFilters"
			@clear="clearFilters" />

		<div v-if="assetsStore.loading" class="cm-view__loading">
			<NcLoadingIcon :size="32" />
		</div>

		<EntityTable v-else
			:columns="columns"
			:items="assetsStore.items"
			:actions="actions"
			:page-size="prefs.pageSize"
			:default-sort="prefs.sortFor('Assets')"
			:page-size-options="[10, 20, 50, 100]"
			:empty-text="t('charity', 'No assets found')"
			@action="onAction"
			@page-size-change="prefs.setRecordsPerPage"
			@sort-change="onSortChange">
			<template #cell-caseId="{ item }">
				<NcSelect :value="item.caseId"
					:options="caseOptionsInline"
					:reduce="v => v.id"
					:label="'_displayLabel'"
					:placeholder="t('charity', 'Assign case')"
					@input="caseId => assignCase(item, caseId)" />
			</template>
		</EntityTable>
	</div>
</template>

<script>
import { NcButton, NcLoadingIcon, NcSelect } from '@nextcloud/vue'
import EntityTable from '../components/EntityTable.vue'
import EntityFilter from '../components/EntityFilter.vue'
import { useAssetsStore, useCasesStore } from '../stores/entities.js'
import { usePrefsStore } from '../stores/prefs.js'
import { useUiStore } from '../stores/ui.js'
import { useUserStore } from '../stores/user.js'
import { translate as t } from '@nextcloud/l10n'
import { exportToCsv } from '../utils/export.js'
import DownloadIcon from 'vue-material-design-icons/Download.vue'
import { formatDate } from '../utils/date.js'

export default {
	name: 'Assets',
	components: {
		NcButton,
		NcLoadingIcon,
		NcSelect,
		EntityTable,
		EntityFilter,
		DownloadIcon,
	},
	setup() {
		const assetsStore = useAssetsStore()
		const casesStore = useCasesStore()
		const ui = useUiStore()
		const userStore = useUserStore()
		const prefs = usePrefsStore()
		return {
			assetsStore,
			casesStore,
			ui,
			userStore,
			prefs,
			t,
		}
	},
	data() {
		return {
			filtersVisible: false,
			filters: {},
		}
	},
	computed: {
		caseOptions() {
			return [{ _displayLabel: t('charity', 'All'), id: '' }, ...(this.casesStore.items || []).map(c => ({ ...c, _displayLabel: `${String(c.id).padStart(10, '0')} - ${c.firstName || ''} ${c.lastName || ''}`.trim() }))]
		},
		caseOptionsInline() {
			return [{ _displayLabel: t('charity', 'None'), id: null }, ...(this.casesStore.items || []).map(c => ({ ...c, _displayLabel: `${String(c.id).padStart(10, '0')} - ${c.firstName || ''} ${c.lastName || ''}`.trim() }))]
		},
		filterFields() {
			return [
				{ key: 'caseId', label: t('charity', 'Case'), type: 'select', options: this.caseOptions, optionLabel: '_displayLabel', optionValue: 'id' },
				{ key: 'assetType', label: t('charity', 'Asset Type'), type: 'select', options: this.assetTypeOptions, optionLabel: 'label', optionValue: 'value' },
				{ key: 'search', label: t('charity', 'Serial / Model'), type: 'text' },
			]
		},
		assetTypeOptions() {
			return [{ label: t('charity', 'All'), value: '' }, ...['Phone', 'Laptop', 'Tab', 'Medical', 'Other'].map(type => ({ label: type, value: type }))]
		},
		actions() {
			const base = [
				{ name: 'edit', label: t('charity', 'Edit'), icon: 'icon-edit' },
			]
			if (this.userStore.isAdminOrCharityAdmin) {
				base.push({ name: 'delete', label: t('charity', 'Delete'), icon: 'icon-delete' })
			}
			return base
		},
		columns() {
			return [
				{ key: 'assetType', label: t('charity', 'Asset Type'), width: '12%' },
				{ key: 'serial', label: t('charity', 'Serial'), width: '12%' },
				{ key: 'model', label: t('charity', 'Model'), width: '12%' },
				{ key: 'datePurchased', label: t('charity', 'Date Purchased'), width: '12%', formatter: this.formatDate },
				{ key: 'warranty', label: t('charity', 'Warranty'), width: '12%', formatter: this.formatDate },
				{ key: 'paidAmount', label: t('charity', 'Paid Amount'), width: '10%' },
				{ key: 'caseId', label: t('charity', 'Assigned Case'), width: '15%', formatter: this.formatCase },
				{ key: 'paymentReference', label: t('charity', 'Payment Reference'), width: '12%' },
			]
		},
	},
	watch: {
		'ui.slidePanelOpen'(open) {
			if (!open) {
				this.assetsStore.fetchAll(this.filters)
			}
		},
	},
	async mounted() {
		await Promise.all([
			this.assetsStore.fetchAll(),
			this.casesStore.fetchAll(),
		])
	},
	methods: {
		formatDate(date) {
			return formatDate(date)
		},
		formatCase(id) {
			if (!id) return ''
			const c = this.casesStore.byId(id)
			if (c) {
				return `${String(c.id).padStart(10, '0')} - ${c.firstName || ''} ${c.lastName || ''}`.trim()
			}
			return String(id).padStart(10, '0')
		},
		applyFilters(filters) {
			const params = {}
			if (filters.caseId) params.caseId = filters.caseId
			if (filters.assetType) params.assetType = filters.assetType
			if (filters.search) {
				params.search = filters.search
			}
			this.filters = filters
			this.assetsStore.fetchAll(params)
		},
		clearFilters() {
			this.filters = {}
			this.assetsStore.fetchAll()
		},
		exportAssets() {
			exportToCsv('assets.csv', this.columns, this.assetsStore.items)
		},
		openEditPanel(item) {
			this.ui.openSlidePanel({ mode: 'edit', entityType: 'cc_Asset', entity: item })
		},
		async assignCase(item, caseId) {
			const normalizedCaseId = caseId && typeof caseId === 'object' ? (caseId.id ?? null) : caseId
			try {
				await this.assetsStore.update(item.id, { caseId: normalizedCaseId })
			} catch (err) {
				console.error(err)
				alert(err.message)
			}
		},
		async deleteAsset(item) {
			if (!confirm(t('charity', 'Are you sure you want to delete this asset?'))) return
			try {
				await this.assetsStore.remove(item.id)
			} catch (err) {
				console.error(err)
				alert(err.message)
			}
		},
		onAction({ name, item }) {
			if (name === 'edit') this.openEditPanel(item)
			if (name === 'delete') this.deleteAsset(item)
		},
		onSortChange(sort) {
			this.prefs.setSort('Assets', sort)
		},
	},
}
</script>

<style scoped>
.cm-view {
    padding: 8px;
    min-height: 100%;
    display: flex;
    flex-direction: column;
}

.cm-view__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 8px;
}

.cm-view__header h1 {
    margin: 0;
    font-size: 20px;
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
