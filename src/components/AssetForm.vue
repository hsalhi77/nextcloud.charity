<template>
	<form ref="form" class="cm-asset-form" @submit.prevent="submit">
		<div v-for="field in fields" :key="field.key" class="cm-asset-form__field">
			<label :for="`field-${field.key}`">
				{{ field.label }}
				<span v-if="field.required" class="cm-asset-form__required">*</span>
			</label>

			<NcTextField v-if="field.type === 'text' || field.type === 'number'"
				:id="`field-${field.key}`"
				v-model="form[field.key]"
				:type="field.type"
				:required="field.required"
				:label="field.label"
				:disabled="field.disabled"
				:show-trailing-button="false" />

			<NcDateTimePicker v-else-if="field.type === 'date'"
				:id="`field-${field.key}`"
				v-model="form[field.key]"
				:type="'date'"
				:format="dateFormat"
				value-type="YYYY-MM-DD"
				:append-to-body="true"
				:required="field.required"
				:label="field.label" />

			<NcSelect v-else-if="field.type === 'select'"
				:id="`field-${field.key}`"
				v-model="form[field.key]"
				:options="field.options"
				:label="field.optionLabel || 'label'"
				:reduce="field.optionValue ? v => v[field.optionValue] : v => v"
				:required="field.required"
				:placeholder="field.label" />
		</div>
	</form>
</template>

<script>
import { NcTextField, NcSelect, NcDateTimePicker } from '@nextcloud/vue'
import { useUiStore } from '../stores/ui.js'
import { useAssetsStore, useCasesStore } from '../stores/entities.js'
import { translate as t } from '@nextcloud/l10n'
import { isValidDate, getDateFormatForLocale } from '../utils/date.js'

export default {
	name: 'AssetForm',
	components: {
		NcTextField,
		NcSelect,
		NcDateTimePicker,
	},
	props: {
		mode: { type: String, required: true },
		asset: { type: Object, default: null },
		paymentId: { type: Number, default: null },
	},
	setup() {
		const ui = useUiStore()
		const assetsStore = useAssetsStore()
		const casesStore = useCasesStore()
		return { ui, assetsStore, casesStore, t }
	},
	data() {
		return {
			form: {},
		}
	},
	computed: {
		dateFormat() {
			return getDateFormatForLocale()
		},
		assetTypeOptions() {
			return ['Phone', 'Laptop', 'Tab', 'Medical', 'Other'].map(type => ({ label: type, value: type }))
		},
		caseOptions() {
			return [{ _displayLabel: t('charity', 'None'), id: null }, ...(this.casesStore.items || []).map(c => ({ ...c, _displayLabel: `${String(c.id).padStart(10, '0')} - ${c.firstName || ''} ${c.lastName || ''}`.trim() }))]
		},
		fields() {
			return [
				{ key: 'assetType', label: t('charity', 'Asset Type'), type: 'select', options: this.assetTypeOptions, optionLabel: 'label', optionValue: 'label', required: true },
				{ key: 'datePurchased', label: t('charity', 'Date Purchased'), type: 'date', disabled: true },
				{ key: 'serial', label: t('charity', 'Serial'), type: 'text' },
				{ key: 'imei', label: t('charity', 'IMEI'), type: 'text' },
				{ key: 'warranty', label: t('charity', 'Warranty'), type: 'date' },
				{ key: 'model', label: t('charity', 'Model'), type: 'text' },
				{ key: 'processor', label: t('charity', 'Processor'), type: 'text' },
				{ key: 'paidAmount', label: t('charity', 'Paid Amount'), type: 'number', disabled: true },
				{ key: 'caseId', label: t('charity', 'Assigned Case'), type: 'select', options: this.caseOptions, optionLabel: '_displayLabel', optionValue: 'id' },
			]
		},
	},
	watch: {
		asset: {
			immediate: true,
			handler(asset) {
				this.form = asset ? { ...asset } : {}
				this.fields.forEach(field => {
					if (this.form[field.key] === undefined) {
						this.$set(this.form, field.key, null)
					}
				})
			},
		},
	},
	async mounted() {
		await this.casesStore.fetchAll()
	},
	methods: {
		formatDate(value) {
			if (!value) return null
			if (typeof value === 'string') return value
			if (value instanceof Date) return value.toISOString().slice(0, 10)
			return null
		},
		async submit() {
			const payload = { ...this.form }
			const dateFields = this.fields.filter(f => f.type === 'date')
			for (const field of dateFields) {
				const value = payload[field.key]
				if (!value && field.required) {
					alert(t('charity', '{field} is required', { field: field.label }))
					return
				}
				if (value && !isValidDate(value)) {
					alert(t('charity', '{field} is not a valid date', { field: field.label }))
					return
				}
			}
			payload.datePurchased = this.formatDate(payload.datePurchased)
			payload.warranty = this.formatDate(payload.warranty)
			try {
				if (this.mode === 'add') {
					payload.refPaymentId = this.paymentId
					await this.assetsStore.create(payload)
				} else {
					await this.assetsStore.update(payload.id, payload)
				}
				this.$emit('saved')
				this.ui.closeSlidePanel()
			} catch (err) {
				console.error(err)
				alert(err.message)
			}
		},
	},
}
</script>

<style scoped>
.cm-asset-form {
	display: flex;
	flex-direction: column;
	gap: 20px;
}

.cm-asset-form__field {
	display: flex;
	flex-direction: column;
	gap: 6px;
}

.cm-asset-form__field label {
	font-size: 14px;
	font-weight: 600;
	color: var(--color-text-maxcontrast);
}

.cm-asset-form__required {
	color: var(--color-error);
}
</style>
