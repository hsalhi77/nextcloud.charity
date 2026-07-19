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

		<div v-if="!paymentsStore.loading" class="cm-payments-footer">
			<div class="cm-payments-footer__row">
				<span class="cm-payments-footer__label">{{ t('charity', 'Receipt') }}</span>
				<span class="cm-payments-footer__value">{{ formatAmount(totals.receipt) }}</span>
			</div>
			<div class="cm-payments-footer__row">
				<span class="cm-payments-footer__label">{{ t('charity', 'Payment') }}</span>
				<span class="cm-payments-footer__value">{{ formatAmount(totals.payment) }}</span>
			</div>
			<div class="cm-payments-footer__row">
				<span class="cm-payments-footer__label">{{ t('charity', 'Expense Payment') }}</span>
				<span class="cm-payments-footer__value">{{ formatAmount(totals.expensePayment) }}</span>
			</div>
			<div class="cm-payments-footer__row cm-payments-footer__row--balance">
				<span class="cm-payments-footer__label">{{ t('charity', 'Balance') }}</span>
				<span class="cm-payments-footer__value">{{ formatAmount(totals.balance) }}</span>
			</div>
		</div>
	</div>
</template>

<script>
import { NcButton, NcLoadingIcon } from '@nextcloud/vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import EntityTable from '../components/EntityTable.vue'
import EntityFilter from '../components/EntityFilter.vue'
import { usePaymentsStore, useCasesStore } from '../stores/entities.js'
import { useUiStore } from '../stores/ui.js'
import { useUserStore } from '../stores/user.js'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'Payments',
	components: {
		NcButton,
		NcLoadingIcon,
		PlusIcon,
		EntityTable,
		EntityFilter,
	},
	setup() {
		const paymentsStore = usePaymentsStore()
		const casesStore = useCasesStore()
		const ui = useUiStore()
		const userStore = useUserStore()
		return {
			paymentsStore,
			casesStore,
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
		totals() {
			const items = this.paymentsStore.items || []
			const receipt = items
				.filter(p => p.paymentType === 'Receipt')
				.reduce((sum, p) => sum + (parseFloat(p.paymentAmount) || 0), 0)
			const payment = items
				.filter(p => p.paymentType === 'Payment')
				.reduce((sum, p) => sum + (parseFloat(p.paymentAmount) || 0), 0)
			const expensePayment = items
				.filter(p => p.paymentType === 'Expense Payment')
				.reduce((sum, p) => sum + (parseFloat(p.paymentAmount) || 0), 0)
			return {
				receipt,
				payment,
				expensePayment,
				balance: receipt - payment - expensePayment,
			}
		},
		caseOptions() {
			return this.casesStore.items.map(c => ({
				id: c.id,
				title: `${String(c.id).padStart(10, '0')} - ${c.firstName || ''} ${c.lastName || ''}`.trim(),
			}))
		},
		filterFields() {
			return [
				{ key: 'caseId', label: t('charity', 'Case'), type: 'select', options: this.caseOptions, optionLabel: 'title', optionValue: 'id' },
				{ key: 'paymentDate', label: t('charity', 'Payment Date'), type: 'text', inputType: 'date' },
				{ key: 'paymentType', label: t('charity', 'Payment Type'), type: 'text' },
				{ key: 'paymentAmount', label: t('charity', 'Amount'), type: 'text', inputType: 'number' },
			]
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
				{ key: 'id', label: t('charity', '#'), width: '8%', formatter: this.formatId },
				{ key: 'caseId', label: t('charity', 'Case'), width: '15%', formatter: this.formatCase },
				{ key: 'paymentDate', label: t('charity', 'Payment Date'), width: '15%', formatter: this.formatDate },
				{ key: 'paymentType', label: t('charity', 'Type'), width: '15%' },
				{ key: 'paymentAmount', label: t('charity', 'Amount'), width: '12%' },
				{ key: 'paymentReference', label: t('charity', 'Payment Reference'), width: '15%' },
				{ key: 'paidBy', label: t('charity', 'Cashbook'), width: '13%' },
			]
		},
	},
	async mounted() {
		await Promise.all([
			this.paymentsStore.fetchAll(),
			this.casesStore.fetchAll(),
		])
		if (this.$route.query.highlight) {
			const id = Number(this.$route.query.highlight)
			const item = this.paymentsStore.items.find(i => i.id === id)
			if (item) this.openDetailPanel(item)
			this.$router.replace({ name: 'payments' })
		}
	},
	methods: {
		formatAmount(amount) {
			return (parseFloat(amount) || 0).toFixed(2)
		},
		applyFilters(filters) {
			this.filters = filters
			this.paymentsStore.fetchAll(filters)
		},
		clearFilters() {
			this.filters = {}
			this.paymentsStore.fetchAll()
		},
		formatDate(date) {
			if (!date) return ''
			return new Date(date).toLocaleDateString()
		},
		formatId(id) {
			return String(id).padStart(10, '0')
		},
		formatCase(id) {
			if (!id) return ''
			return String(id).padStart(10, '0')
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

.cm-payments-footer {
    display: flex;
    justify-content: flex-end;
    gap: 24px;
    padding: 16px 0;
    border-top: 1px solid var(--color-border);
    margin-top: 8px;
}

.cm-payments-footer__row {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    min-width: 100px;
}

.cm-payments-footer__label {
    font-size: 12px;
    color: var(--color-text-maxcontrast);
    font-weight: 600;
}

.cm-payments-footer__value {
    font-size: 16px;
    font-weight: 700;
}

.cm-payments-footer__row--balance .cm-payments-footer__value {
    color: var(--color-primary-element);
}
</style>
