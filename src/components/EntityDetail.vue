<template>
	<div class="cm-entity-detail">
		<div class="cm-entity-detail__tabs" role="tablist">
			<button
				v-for="tab in tabs"
				:key="tab.name"
				class="cm-entity-detail__tab"
				:class="{ 'cm-entity-detail__tab--active': activeTab === tab.name }"
				:aria-selected="activeTab === tab.name"
				role="tab"
				@click="activeTab = tab.name">
				{{ tab.label }}
			</button>
		</div>

		<div v-if="loading" class="cm-entity-detail__loading">
			<NcLoadingIcon :size="24" />
		</div>

		<div v-else-if="item" class="cm-entity-detail__content">
			<!-- SUMMARY -->
			<div v-if="activeTab === 'summary'" class="cm-entity-detail__summary">
				<dl>
					<div v-for="field in summaryFields" :key="field.key" class="cm-entity-detail__row">
						<dt>{{ field.label }}</dt>
						<dd>{{ formatValue(item, field) }}</dd>
					</div>
				</dl>
			</div>

			<!-- PAYMENTS -->
			<div v-else-if="activeTab === 'payments'" class="cm-entity-detail__payments">
				<h3>{{ t('charity', 'Payments') }}</h3>
				<div v-if="relatedLoading" class="cm-entity-detail__loading">
					<NcLoadingIcon :size="24" />
				</div>
				<table v-else-if="relatedPayments.length" class="cm-entity-detail__related-table">
					<thead>
						<tr>
							<th>{{ t('charity', 'Date') }}</th>
							<th>{{ t('charity', 'Type') }}</th>
							<th>{{ t('charity', 'Paid By') }}</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="payment in relatedPayments" :key="payment.id">
							<td>{{ formatDate(payment.paymentDate) }}</td>
							<td>{{ payment.paymentType }}</td>
							<td>{{ payment.paidBy }}</td>
						</tr>
					</tbody>
				</table>
				<div v-else class="cm-entity-detail__coming-soon">
					{{ t('charity', 'No payments for this case') }}
				</div>
			</div>

			<!-- UPDATES -->
			<div v-else-if="activeTab === 'updates'" class="cm-entity-detail__updates">
				<h3>{{ t('charity', 'Updates') }}</h3>
				<div v-if="relatedLoading" class="cm-entity-detail__loading">
					<NcLoadingIcon :size="24" />
				</div>
				<table v-else-if="relatedUpdates.length" class="cm-entity-detail__related-table">
					<thead>
						<tr>
							<th>{{ t('charity', 'Date') }}</th>
							<th>{{ t('charity', 'Type') }}</th>
							<th>{{ t('charity', 'Updated By') }}</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="update in relatedUpdates" :key="update.id">
							<td>{{ formatDate(update.updateDate) }}</td>
							<td>{{ formatUpdateType(update.updateTypeId) }}</td>
							<td>{{ update.updateBy }}</td>
						</tr>
					</tbody>
				</table>
				<div v-else class="cm-entity-detail__coming-soon">
					{{ t('charity', 'No updates for this case') }}
				</div>
			</div>

			<!-- TEAM -->
			<div v-else-if="activeTab === 'team'" class="cm-entity-detail__team">
				<h3>{{ t('charity', 'Team') }}</h3>
				<div class="cm-entity-detail__coming-soon">
					{{ t('charity', 'Team management coming soon') }}
				</div>
			</div>

			<!-- ATTACHMENTS -->
			<div v-else-if="activeTab === 'attachments'" class="cm-entity-detail__attachments">
				<h3>{{ t('charity', 'Attachments') }}</h3>
				<div class="cm-entity-detail__coming-soon">
					{{ t('charity', 'Attachments coming soon') }}
				</div>
			</div>

			<!-- COMMENTS -->
			<div v-else-if="activeTab === 'comments'" class="cm-entity-detail__comments">
				<h3>{{ t('charity', 'Comments') }}</h3>
				<div class="cm-entity-detail__coming-soon">
					{{ t('charity', 'Comments coming soon') }}
				</div>
			</div>

			<!-- ACTIVITY -->
			<div v-else-if="activeTab === 'activity'" class="cm-entity-detail__activity">
				<h3>{{ t('charity', 'Activity') }}</h3>
				<div class="cm-entity-detail__coming-soon">
					{{ t('charity', 'Activity coming soon') }}
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import { NcLoadingIcon } from '@nextcloud/vue'
import { useCasesStore, usePaymentsStore, useUpdatesStore, useCaseTypesStore, useUpdateTypesStore, useCitiesStore } from '../stores/entities.js'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'EntityDetail',
	components: {
		NcLoadingIcon,
	},
	props: {
		entityType: { type: String, required: true },
		entityId: { type: Number, default: null },
	},
	setup() {
		const stores = {
			cc_Case: useCasesStore(),
			cc_Payment: usePaymentsStore(),
			cc_Update: useUpdatesStore(),
			cc_City: useCitiesStore(),
			cc_CaseType: useCaseTypesStore(),
			cc_UpdateType: useUpdateTypesStore(),
		}
		return {
			stores,
			t,
		}
	},
	data() {
		return {
			activeTab: 'summary',
			loading: false,
			relatedLoading: false,
			relatedPayments: [],
			relatedUpdates: [],
		}
	},
	computed: {
		store() { return this.stores[this.entityType] },
		item() { return this.store?.byId(this.entityId) },
		tabs() {
			const all = [
				{ name: 'summary', label: t('charity', 'Summary') },
			]
			if (this.entityType === 'cc_Case') {
				all.push(
					{ name: 'payments', label: t('charity', 'Payments') },
					{ name: 'updates', label: t('charity', 'Updates') },
					{ name: 'team', label: t('charity', 'Team') },
				)
			}
			all.push(
				{ name: 'attachments', label: t('charity', 'Attachments') },
				{ name: 'comments', label: t('charity', 'Comments') },
				{ name: 'activity', label: t('charity', 'Activity') },
			)
			return all
		},
		summaryFields() {
			switch (this.entityType) {
			case 'cc_Case':
				return [
					{ key: 'firstName', label: t('charity', 'First Name') },
					{ key: 'lastName', label: t('charity', 'Last Name') },
					{ key: 'idNumber', label: t('charity', 'ID Number') },
					{ key: 'caseTypeId', label: t('charity', 'Case Type'), formatter: this.formatCaseType },
					{ key: 'dateAdded', label: t('charity', 'Date Added'), formatter: this.formatDate },
					{ key: 'dob', label: t('charity', 'Date of Birth'), formatter: this.formatDate },
					{ key: 'referredBy', label: t('charity', 'Referred By') },
					{ key: 'phone', label: t('charity', 'Phone') },
					{ key: 'email', label: t('charity', 'Email') },
					{ key: 'address', label: t('charity', 'Address') },
					{ key: 'cityId', label: t('charity', 'City'), formatter: this.formatCity },
					{ key: 'description', label: t('charity', 'Description') },
					{ key: 'recommendation', label: t('charity', 'Recommendation') },
				]
			case 'cc_Payment':
				return [
					{ key: 'caseId', label: t('charity', 'Case'), formatter: this.formatCase },
					{ key: 'paymentDate', label: t('charity', 'Payment Date'), formatter: this.formatDate },
					{ key: 'paymentType', label: t('charity', 'Payment Type') },
					{ key: 'paymentReceipt', label: t('charity', 'Payment Receipt') },
					{ key: 'paidBy', label: t('charity', 'Paid By') },
				]
			case 'cc_Update':
				return [
					{ key: 'caseId', label: t('charity', 'Case'), formatter: this.formatCase },
					{ key: 'updateDate', label: t('charity', 'Update Date'), formatter: this.formatDate },
					{ key: 'updateTypeId', label: t('charity', 'Update Type'), formatter: this.formatUpdateType },
					{ key: 'updateBy', label: t('charity', 'Updated By') },
					{ key: 'description', label: t('charity', 'Description') },
				]
			case 'cc_City':
				return [
					{ key: 'title', label: t('charity', 'Title') },
				]
			case 'cc_CaseType':
				return [
					{ key: 'title', label: t('charity', 'Title') },
				]
			case 'cc_UpdateType':
				return [
					{ key: 'title', label: t('charity', 'Title') },
				]
			default:
				return []
			}
		},
	},
	watch: {
		entityId: {
			immediate: true,
			handler(newVal, oldVal) {
				if (newVal !== oldVal) {
					this.activeTab = 'summary'
					this.relatedPayments = []
					this.relatedUpdates = []
				}
				this.loadItem()
			},
		},
		activeTab(tab) {
			if (!this.entityId) return
			if (tab === 'payments') this.loadPayments()
			if (tab === 'updates') this.loadUpdates()
		},
	},
	methods: {
		async loadItem() {
			if (!this.entityId) return
			this.loading = true
			try {
				await this.store.fetchOne(this.entityId)
				const promises = []
				if (this.entityType === 'cc_Case') {
					promises.push(this.stores.cc_CaseType?.fetchAll())
					promises.push(this.stores.cc_City?.fetchAll())
				}
				if (this.entityType === 'cc_Payment' || this.entityType === 'cc_Update') {
					promises.push(this.stores.cc_Case?.fetchAll())
				}
				if (this.entityType === 'cc_Update') {
					promises.push(this.stores.cc_UpdateType?.fetchAll())
				}
				await Promise.all(promises)
			} finally {
				this.loading = false
			}
		},
		async loadPayments() {
			if (this.entityType !== 'cc_Case' || !this.entityId) return
			this.relatedLoading = true
			try {
				await this.stores.cc_Payment.fetchAll()
				this.relatedPayments = this.stores.cc_Payment.items.filter(p => p.caseId === this.entityId)
			} finally {
				this.relatedLoading = false
			}
		},
		async loadUpdates() {
			if (this.entityType !== 'cc_Case' || !this.entityId) return
			this.relatedLoading = true
			try {
				await this.stores.cc_Update.fetchAll()
				this.relatedUpdates = this.stores.cc_Update.items.filter(u => u.caseId === this.entityId)
			} finally {
				this.relatedLoading = false
			}
		},
		formatValue(item, field) {
			let value = item[field.key]
			if (field.formatter) value = field.formatter(value)
			return value ?? ''
		},
		formatDate(date) {
			if (!date) return ''
			return new Date(date).toLocaleDateString()
		},
		formatCaseType(id) {
			const type = this.stores.cc_CaseType?.byId(id)
			return type?.title || ''
		},
		formatCity(id) {
			const city = this.stores.cc_City?.byId(id)
			return city?.title || ''
		},
		formatCase(id) {
			const caseItem = this.stores.cc_Case?.byId(id)
			if (caseItem) {
				return `${caseItem.firstName || ''} ${caseItem.lastName || ''}`.trim()
			}
			return id
		},
		formatUpdateType(id) {
			const type = this.stores.cc_UpdateType?.byId(id)
			return type?.title || ''
		},
	},
}
</script>

<style scoped>
.cm-entity-detail__tabs {
	display: flex;
	gap: 2px;
	border-bottom: 1px solid var(--color-border);
	margin: 2px 0 8px;
	overflow-x: scroll;
	overflow-y: hidden;
	scrollbar-width: thin;
	padding-block-start: 2px;
	padding-block-end: 0;
}

.cm-entity-detail__tab {
	flex-shrink: 0;
	white-space: nowrap;
	background: transparent;
	border: none;
	padding-block: 6px;
	padding-inline: 10px;
	font-size: 12px;
	font-weight: 600;
	color: var(--color-text-maxcontrast);
	cursor: pointer;
	border-block-end: 3px solid transparent;
	border-radius: 6px 6px 0 0;
	transition: color 0.15s, border-color 0.15s, background 0.15s;
}

.cm-entity-detail__tab:hover {
	background: var(--color-background-hover);
	color: var(--color-main-text);
}

.cm-entity-detail__tab--active {
	color: var(--cm-accent, var(--color-primary-element));
	border-block-end-color: var(--cm-accent, var(--color-primary-element));
	background: rgba(var(--cm-accent-rgb, var(--color-primary-element-rgb)), 0.08);
}

.cm-entity-detail__loading {
	display: flex;
	justify-content: center;
	padding: 24px;
}

.cm-entity-detail__content h3 {
	margin: 0 0 8px;
	font-size: 15px;
	font-weight: 700;
}

.cm-entity-detail__row {
	display: flex;
	justify-content: space-between;
	padding: 2px 0;
	border-bottom: 1px solid var(--color-border);
}

.cm-entity-detail__row dt {
	font-size: 12px;
	color: var(--color-text-maxcontrast);
}

.cm-entity-detail__row dd {
	margin: 0;
	font-size: 12px;
	text-align: end;
}

.cm-entity-detail__related-table {
	width: 100%;
	border-collapse: collapse;
}

.cm-entity-detail__related-table th,
.cm-entity-detail__related-table td {
	text-align: left;
	padding: 6px 8px;
	border-bottom: 1px solid var(--color-border);
}

.cm-entity-detail__related-table th {
	font-size: 12px;
	font-weight: 600;
	color: var(--color-text-maxcontrast);
}

.cm-entity-detail__coming-soon {
	padding: 24px;
	text-align: center;
	color: var(--color-text-maxcontrast);
}
</style>
