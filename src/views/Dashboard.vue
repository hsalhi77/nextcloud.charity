<template>
	<div class="cm-view">
		<header class="cm-view__header">
			<h1>{{ t('charity', 'Dashboard') }}</h1>
		</header>

		<div v-if="loading" class="cm-view__loading">
			<NcLoadingIcon :size="32" />
		</div>

		<div v-else-if="error" class="cm-dashboard__error">
			{{ error }}
		</div>

		<div v-else class="cm-dashboard__grid">
			<div class="cm-dashboard__card cm-dashboard__card--wide">
				<h2>{{ t('charity', 'Activity by Charity Field') }}</h2>
				<BarChart v-if="stats.activityByField && stats.activityByField.length" :data="stats.activityByField" />
				<div v-else class="cm-dashboard__empty">
					{{ t('charity', 'No activity in the last 6 months') }}
				</div>
			</div>

			<div class="cm-dashboard__card">
				<h2>{{ t('charity', 'Cases') }}</h2>
				<div class="cm-dashboard__big-number">{{ stats.totalCases }}</div>
				<ul v-if="nonZeroCasesByType.length" class="cm-dashboard__breakdown">
					<li v-for="type in nonZeroCasesByType" :key="type.id">
						{{ type.title }}: {{ type.count }}
					</li>
				</ul>
				<div v-else class="cm-dashboard__empty">
					{{ t('charity', 'No cases by type') }}
				</div>
			</div>

			<div class="cm-dashboard__card">
				<h2>{{ t('charity', 'Payout Ratio') }}</h2>
				<div class="cm-dashboard__big-number">{{ formatPercent(stats.payoutRatio) }}</div>
				<div class="cm-dashboard__details">
					<div>{{ t('charity', 'Receipt') }}: {{ formatAmount(stats.totalReceipts) }}</div>
					<div>{{ t('charity', 'Payment') }}: {{ formatAmount(stats.totalPayments) }}</div>
					<div>{{ t('charity', 'Expense Payment') }}: {{ formatAmount(stats.totalExpensePayments) }}</div>
				</div>
			</div>

			<div class="cm-dashboard__card cm-dashboard__card--wide">
				<h2>{{ t('charity', 'City Stats') }}</h2>
				<table class="cm-dashboard__table">
					<thead>
						<tr>
							<th>{{ t('charity', 'City') }}</th>
							<th>{{ t('charity', 'Cases') }}</th>
							<th>{{ t('charity', 'Paid Amount') }}</th>
						</tr>
					</thead>
					<tbody>
						<tr v-for="city in stats.cityStats" :key="city.id">
							<td>{{ city.name }}</td>
							<td>{{ city.caseCount }}</td>
							<td>{{ formatAmount(city.paidAmount) }}</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</template>

<script>
import { NcLoadingIcon } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'
import { get } from '../services/api.js'
import BarChart from '../components/BarChart.vue'

export default {
	name: 'Dashboard',
	components: {
		NcLoadingIcon,
		BarChart,
	},
	setup() {
		return { t }
	},
	data() {
		return {
			loading: true,
			error: null,
			stats: {
				totalCases: 0,
				casesByType: [],
				totalReceipts: 0,
				totalPayments: 0,
				totalExpensePayments: 0,
				payoutRatio: 0,
				cityStats: [],
			},
		}
	},
	computed: {
		nonZeroCasesByType() {
			return (this.stats.casesByType || []).filter(type => type.count > 0)
		},
	},
	async mounted() {
		try {
			this.stats = await get('/dashboard/stats')
		} catch (err) {
			this.error = err.message || t('charity', 'Failed to load dashboard')
			console.error(err)
		} finally {
			this.loading = false
		}
	},
	methods: {
		formatAmount(amount) {
			return (parseFloat(amount) || 0).toFixed(2)
		},
		formatPercent(ratio) {
			return ((parseFloat(ratio) || 0) * 100).toFixed(1) + '%'
		},
	},
}
</script>

<style scoped>
.cm-view {
	padding: 16px;
	height: 100%;
	display: flex;
	flex-direction: column;
	overflow-y: auto;
}

.cm-view__header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	margin-bottom: 12px;
}

.cm-view__header h1 {
	margin: 0;
	font-size: 20px;
	font-weight: 700;
}

.cm-view__loading {
	display: flex;
	justify-content: center;
	padding: 48px;
}

.cm-dashboard__grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
	gap: 12px;
	align-content: start;
}

.cm-dashboard__card {
	background: var(--color-main-background);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
	padding: 12px;
}

.cm-dashboard__card--wide {
	grid-column: 1 / -1;
}

.cm-dashboard__card h2 {
	margin: 0 0 8px;
	font-size: 14px;
	font-weight: 700;
	color: var(--color-text-maxcontrast);
}

.cm-dashboard__big-number {
	font-size: 28px;
	font-weight: 700;
	margin-bottom: 8px;
}

.cm-dashboard__breakdown {
	list-style: none;
	padding: 0;
	margin: 0;
}

.cm-dashboard__breakdown li {
	padding: 2px 0;
	font-size: 13px;
}

.cm-dashboard__details {
	display: flex;
	flex-direction: column;
	gap: 4px;
	font-size: 13px;
	color: var(--color-text-maxcontrast);
}

.cm-dashboard__table {
	width: 100%;
	border-collapse: collapse;
}

.cm-dashboard__table th,
.cm-dashboard__table td {
	text-align: left;
	padding: 6px;
	border-bottom: 1px solid var(--color-border);
	font-size: 13px;
}

.cm-dashboard__table th {
	font-weight: 700;
	color: var(--color-text-maxcontrast);
}

.cm-dashboard__error {
	padding: 24px;
	color: var(--color-error);
}

.cm-dashboard__empty {
	padding: 12px;
	text-align: center;
	color: var(--color-text-maxcontrast);
	font-size: 13px;
}
</style>
