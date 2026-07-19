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
				<NcButton type="secondary" @click="filtersVisible = !filtersVisible">
					{{ t('charity', 'Filters') }}
				</NcButton>
			</div>
		</header>

		<EntityFilter
			v-if="filtersVisible"
			:fields="filterFields"
			@filter="applyFilters"
			@clear="clearFilters" />

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
import EntityFilter from '../components/EntityFilter.vue'
import { useCasesStore, useCaseTypesStore, useCitiesStore } from '../stores/entities.js'
import { useUiStore } from '../stores/ui.js'
import { useUserStore } from '../stores/user.js'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'Cases',
	components: {
		NcButton,
		NcLoadingIcon,
		PlusIcon,
		EntityTable,
		EntityFilter,
	},
	setup() {
		const casesStore = useCasesStore()
		const caseTypesStore = useCaseTypesStore()
		const citiesStore = useCitiesStore()
		const ui = useUiStore()
		const userStore = useUserStore()
		return {
			casesStore,
			caseTypesStore,
			citiesStore,
			ui,
			userStore,
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
		filterFields() {
			return [
				{ key: 'name', label: t('charity', 'Name'), type: 'text' },
				{ key: 'cityId', label: t('charity', 'City'), type: 'select', options: this.citiesStore.items, optionLabel: 'title', optionValue: 'id' },
				{ key: 'caseTypeId', label: t('charity', 'Case Type'), type: 'select', options: this.caseTypesStore.items, optionLabel: 'title', optionValue: 'id' },
				{ key: 'owner', label: t('charity', 'Owner'), type: 'text' },
				{ key: 'referredBy', label: t('charity', 'Referred By'), type: 'text' },
			]
		},
		actions() {
			const base = [
				{ name: 'addPayment', label: t('charity', '[+] Payment') },
				{ name: 'addUpdate', label: t('charity', '[+] Update') },
				{ name: 'edit', label: t('charity', 'Edit'), icon: 'icon-edit' },
			]
			if (this.userStore.isAdminOrCharityAdmin) {
				base.push({ name: 'delete', label: t('charity', 'Delete'), icon: 'icon-delete' })
			}
			return base
		},
		columns() {
			return [
				{ key: 'id', label: t('charity', '#'), width: '8%', formatter: this.formatId },
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
			this.citiesStore.fetchAll(),
		])
	},
	methods: {
		applyFilters(filters) {
			this.filters = filters
			this.casesStore.fetchAll(filters)
		},
		clearFilters() {
			this.filters = {}
			this.casesStore.fetchAll()
		},
		formatId(id) {
			if (id == null) return ''
			return String(id).padStart(10, '0')
		},
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
		openAddPaymentPanel(item) {
			this.ui.openSlidePanel({ mode: 'add', entityType: 'cc_Payment', entity: { caseId: item.id } })
		},
		openAddUpdatePanel(item) {
			this.ui.openSlidePanel({ mode: 'add', entityType: 'cc_Update', entity: { caseId: item.id } })
		},
		onAction({ name, item }) {
			if (name === 'addPayment') this.openAddPaymentPanel(item)
			if (name === 'addUpdate') this.openAddUpdatePanel(item)
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
