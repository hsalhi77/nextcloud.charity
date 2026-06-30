<template>
	<div class="cm-view">
		<header class="cm-view__header">
			<h1>{{ t('charity', 'Payments') }}</h1>
			<div class="cm-view__actions">
				<NcButton type="primary" @click="openAddPanel">
					<template #icon>
						<PlusIcon :size="16" />
					</template>
					{{ t('charity', 'Add Payment') }}
				</NcButton>
			</div>
		</header>

		<div v-if="paymentsStore.loading" class="cm-view__loading">
			<NcLoadingIcon :size="32" />
		</div>

		<EntityTable v-else
			:columns="columns"
			:items="paymentsStore.items"
			:actions="actions"
			:empty-text="t('charity', 'No payments found')"
			@row-click="openDetailPanel"
			@action="onAction" />
	</div>
</template>

<script>
import { NcButton, NcLoadingIcon } from '@nextcloud/vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import EntityTable from '../components/EntityTable.vue'
import { usePaymentsStore, useCasesStore } from '../stores/entities.js'
import { useUiStore } from '../stores/ui.js'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'Payments',
	components: {
		NcButton,
		NcLoadingIcon,
		PlusIcon,
		EntityTable,
	},
	setup() {
		const paymentsStore = usePaymentsStore()
		const casesStore = useCasesStore()
		const ui = useUiStore()
		return {
			paymentsStore,
			casesStore,
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
				{ key: 'caseId', label: t('charity', 'Case'), width: '20%', formatter: this.formatCase },
				{ key: 'paymentDate', label: t('charity', 'Payment Date'), width: '15%', formatter: this.formatDate },
				{ key: 'paymentReceipt', label: t('charity', 'Type'), width: '15%', formatter: this.formatType },
				{ key: 'paymentType', label: t('charity', 'Payment Type'), width: '20%' },
				{ key: 'paidBy', label: t('charity', 'Paid By'), width: '15%' },
			]
		},
	},
	async mounted() {
		await Promise.all([
			this.paymentsStore.fetchAll(),
			this.casesStore.fetchAll(),
		])
	},
	methods: {
		formatDate(date) {
			if (!date) return ''
			return new Date(date).toLocaleDateString()
		},
		formatType(value) {
			if (!value) return ''
			return String(value).charAt(0).toUpperCase() + String(value).slice(1)
		},
		formatCase(id) {
			const caseItem = this.casesStore.byId(id)
			if (caseItem) {
				return `${caseItem.firstName || ''} ${caseItem.lastName || ''}`.trim()
			}
			return id
		},
		openAddPanel() {
			this.ui.openSlidePanel({ mode: 'add', entityType: 'cc_Payment' })
		},
		openDetailPanel(item) {
			this.ui.openSlidePanel({ mode: 'detail', entityType: 'cc_Payment', entityId: item.id })
		},
		openEditPanel(item) {
			this.ui.openSlidePanel({ mode: 'edit', entityType: 'cc_Payment', entity: item })
		},
		onAction({ name, item }) {
			if (name === 'edit') this.openEditPanel(item)
			if (name === 'delete') this.deletePayment(item)
		},
		async deletePayment(item) {
			if (!confirm(t('charity', 'Are you sure you want to delete this payment?'))) return
			try {
				await this.paymentsStore.remove(item.id)
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
