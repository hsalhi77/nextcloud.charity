<template>
	<NcModal size="large" :title="t('charity', 'Asset Purchase Assets')" @close="discard">
		<div class="cm-asset-wizard">
			<div class="cm-asset-wizard__summary">
				<h3>{{ t('charity', 'Payment Amount') }}: {{ formattedExpected }}</h3>
				<p>{{ t('charity', 'Asset Total') }}: {{ formattedTotal }}</p>
			</div>

			<div v-if="mismatch" class="cm-asset-wizard__warning">
				<p>{{ t('charity', 'Asset total does not match payment amount. Please fix the amounts or discard the payment.') }}</p>
				<NcButton type="error" @click="discard">
					{{ t('charity', 'Discard Payment') }}
				</NcButton>
			</div>

			<table class="cm-asset-wizard__table">
				<thead>
					<tr>
						<th>{{ t('charity', 'Asset Type') }}</th>
						<th>{{ t('charity', 'Paid Amount') }}</th>
						<th>{{ t('charity', 'Model') }}</th>
						<th>{{ t('charity', 'Serial') }}</th>
						<th />
					</tr>
				</thead>
				<tbody>
					<tr v-for="(asset, index) in assets" :key="index">
						<td>
							<NcSelect v-model="asset.assetType"
								:options="assetTypeOptions"
								:placeholder="t('charity', 'Asset Type')"
								:clearable="false" />
						</td>
						<td>
							<NcTextField v-model="asset.paidAmount"
								type="number"
								:placeholder="t('charity', 'Amount')" />
						</td>
						<td>
							<NcTextField v-model="asset.model" :placeholder="t('charity', 'Model')" />
						</td>
						<td>
							<NcTextField v-model="asset.serial" :placeholder="t('charity', 'Serial')" />
						</td>
						<td>
							<NcButton type="tertiary" @click="removeAsset(index)">
								<template #icon>
									<DeleteIcon :size="20" />
								</template>
							</NcButton>
						</td>
					</tr>
				</tbody>
			</table>

			<div class="cm-asset-wizard__actions">
				<NcButton type="secondary" @click="addAsset">
					{{ t('charity', 'Add Asset') }}
				</NcButton>
			</div>

			<div class="cm-asset-wizard__footer">
				<NcButton type="secondary" @click="discard">
					{{ t('charity', 'Discard') }}
				</NcButton>
				<NcButton type="primary" :disabled="!canSave" @click="save">
					{{ t('charity', 'Save Payment & Assets') }}
				</NcButton>
			</div>
		</div>
	</NcModal>
</template>

<script>
import { NcModal, NcButton, NcTextField, NcSelect } from '@nextcloud/vue'
import DeleteIcon from 'vue-material-design-icons/Delete.vue'
import { usePaymentsStore } from '../stores/entities.js'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'AssetPurchaseWizard',
	components: {
		NcModal,
		NcButton,
		NcTextField,
		NcSelect,
		DeleteIcon,
	},
	props: {
		payment: { type: Object, required: true },
	},
	emits: ['saved', 'discard'],
	setup() {
		const paymentsStore = usePaymentsStore()
		return { paymentsStore, t }
	},
	data() {
		return {
			assets: [this.emptyAsset()],
		}
	},
	computed: {
		assetTypeOptions() {
			return ['Phone', 'Laptop', 'Tab', 'Medical', 'Other'].map(type => ({ label: type, value: type }))
		},
		expectedTotal() {
			return parseFloat(this.payment.paymentAmount || 0)
		},
		formattedExpected() {
			return this.expectedTotal.toFixed(2)
		},
		assetTotal() {
			return this.assets.reduce((sum, asset) => sum + (parseFloat(asset.paidAmount) || 0), 0)
		},
		formattedTotal() {
			return this.assetTotal.toFixed(2)
		},
		mismatch() {
			return Math.round(this.assetTotal * 100) !== Math.round(this.expectedTotal * 100)
		},
		canSave() {
			if (this.assets.length === 0) return false
			if (this.mismatch) return false
			return this.assets.every(asset => asset.assetType && asset.paidAmount !== '' && asset.paidAmount !== null)
		},
	},
	methods: {
		emptyAsset() {
			return { assetType: null, paidAmount: null, model: '', serial: '' }
		},
		addAsset() {
			this.assets.push(this.emptyAsset())
		},
		removeAsset(index) {
			this.assets.splice(index, 1)
			if (this.assets.length === 0) {
				this.addAsset()
			}
		},
		prepareAssets() {
			return this.assets.map(asset => ({
				assetType: asset.assetType?.value ?? asset.assetType,
				paidAmount: parseFloat(asset.paidAmount) || 0,
				model: asset.model,
				serial: asset.serial,
			}))
		},
		async save() {
			if (this.mismatch) {
				alert(t('charity', 'Asset total does not match payment amount.'))
				return
			}
			try {
				await this.paymentsStore.createWithAssets({
					payment: this.payment,
					assets: this.prepareAssets(),
				})
				this.$emit('saved')
			} catch (err) {
				console.error(err)
				alert(err.message)
			}
		},
		discard() {
			this.$emit('discard')
		},
	},
}
</script>

<style scoped>
.cm-asset-wizard {
	padding: 20px;
	min-width: 600px;
	max-width: 900px;
}

.cm-asset-wizard__summary {
	margin-bottom: 16px;
}

.cm-asset-wizard__summary h3,
.cm-asset-wizard__summary p {
	margin: 4px 0;
}

.cm-asset-wizard__warning {
	background: var(--color-warning-light);
	border: 1px solid var(--color-warning);
	border-radius: var(--border-radius);
	padding: 12px;
	margin-bottom: 16px;
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
}

.cm-asset-wizard__table {
	width: 100%;
	border-collapse: collapse;
	margin-bottom: 12px;
}

.cm-asset-wizard__table th,
.cm-asset-wizard__table td {
	text-align: left;
	padding: 8px;
	border-bottom: 1px solid var(--color-border);
}

.cm-asset-wizard__actions {
	margin-bottom: 20px;
}

.cm-asset-wizard__footer {
	display: flex;
	justify-content: flex-end;
	gap: 8px;
}
</style>
