<template>
	<div class="cm-view">
		<header class="cm-view__header">
			<h1>{{ t('charity', 'Internal Transfers') }}</h1>
			<div class="cm-view__actions">
				<NcButton type="primary" @click="openAddPanel">
					<template #icon>
						<PlusIcon :size="16" />
					</template>
					{{ t('charity', 'Add Transfer') }}
				</NcButton>
				<NcButton type="secondary" @click="filtersVisible = !filtersVisible">
					{{ t('charity', 'Filters') }}
				</NcButton>
			</div>
		</header>

		<EntityFilter v-if="filtersVisible"
			:fields="filterFields"
			@filter="applyFilters"
			@clear="clearFilters" />

		<div v-if="transfersStore.loading" class="cm-view__loading">
			<NcLoadingIcon :size="32" />
		</div>

		<EntityTable v-else
			:columns="columns"
			:items="transfersStore.items"
			:actions="actions"
			:default-sort="{ key: 'id', direction: 'desc' }"
			:empty-text="t('charity', 'No transfers found')"
			@action="onAction" />
	</div>
</template>

<script>
import { NcButton, NcLoadingIcon } from '@nextcloud/vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import EntityTable from '../components/EntityTable.vue'
import EntityFilter from '../components/EntityFilter.vue'
import { useTransfersStore } from '../stores/entities.js'
import { useUiStore } from '../stores/ui.js'
import { useUserStore } from '../stores/user.js'
import { post } from '../services/api.js'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'Transfers',
	components: {
		NcButton,
		NcLoadingIcon,
		PlusIcon,
		EntityTable,
		EntityFilter,
	},
	setup() {
		const transfersStore = useTransfersStore()
		const ui = useUiStore()
		const userStore = useUserStore()
		return {
			transfersStore,
			ui,
			userStore,
			t,
		}
	},
	data() {
		return {
			filtersVisible: false,
			filters: {},
			users: [],
		}
	},
	computed: {
		userOptions() {
			return this.users || []
		},
		filterFields() {
			return [
				{ key: 'transferDate', label: t('charity', 'Transfer Date'), type: 'text', inputType: 'date' },
				{ key: 'ref', label: t('charity', 'Reference'), type: 'text' },
				{ key: 'amount', label: t('charity', 'Amount'), type: 'text', inputType: 'number' },
				{ key: 'paidFrom', label: t('charity', 'Paid From'), type: 'select', options: this.userOptions, optionLabel: 'displayName', optionValue: 'uid' },
				{ key: 'paidTo', label: t('charity', 'Paid To'), type: 'select', options: this.userOptions, optionLabel: 'displayName', optionValue: 'uid' },
			]
		},
		actions() {
			const base = []
			if (this.userStore.isAdminOrCharityAdmin) {
				base.push({ name: 'delete', label: t('charity', 'Delete'), icon: 'icon-delete' })
			}
			return base
		},
		columns() {
			return [
				{ key: 'id', label: t('charity', '#'), width: '8%', formatter: this.formatId },
				{ key: 'transferDate', label: t('charity', 'Date'), width: '12%', formatter: this.formatDate },
				{ key: 'ref', label: t('charity', 'Reference'), width: '15%' },
				{ key: 'description', label: t('charity', 'Description'), width: '25%' },
				{ key: 'amount', label: t('charity', 'Amount'), width: '12%' },
				{ key: 'paidFrom', label: t('charity', 'Paid From'), width: '14%', formatter: this.formatUser },
				{ key: 'paidTo', label: t('charity', 'Paid To'), width: '14%', formatter: this.formatUser },
			]
		},
	},
	async mounted() {
		await this.transfersStore.fetchAll()
		try {
			const result = await post('/team/usersByGroup', { params: { group: 'Charity Field' } })
			this.users = result || []
		} catch (e) {
			console.error('Failed to load users', e)
			this.users = []
		}
	},
	methods: {
		formatId(id) {
			if (id == null) return ''
			return String(id).padStart(10, '0')
		},
		formatDate(date) {
			if (!date) return ''
			return new Date(date).toLocaleDateString()
		},
		formatUser(uid) {
			if (!uid) return ''
			const user = (this.users || []).find(u => u.uid === uid)
			return user ? user.displayName : uid
		},
		applyFilters(filters) {
			this.filters = filters
			this.transfersStore.fetchAll(filters)
		},
		clearFilters() {
			this.filters = {}
			this.transfersStore.fetchAll()
		},
		openAddPanel() {
			this.ui.openSlidePanel({ mode: 'add', entityType: 'cc_Transfer' })
		},
		onAction({ name, item }) {
			if (name === 'delete') this.deleteTransfer(item)
		},
		async deleteTransfer(item) {
			if (!confirm(t('charity', 'Are you sure you want to delete this transfer?'))) return
			try {
				await this.transfersStore.remove(item.id)
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
