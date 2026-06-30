<template>
	<form class="cm-entity-form" @submit.prevent="submit">
		<div v-if="loadingReferences" class="cm-entity-form__loading">
			<NcLoadingIcon :size="32" />
		</div>
		<div v-for="field in fields" :key="field.key" class="cm-entity-form__field">
			<label :for="`field-${field.key}`">
				{{ field.label }}
				<span v-if="field.required" class="cm-entity-form__required">*</span>
			</label>

			<NcTextField
				v-if="field.type === 'text' || field.type === 'number' || field.type === 'date'"
				:id="`field-${field.key}`"
				v-model="form[field.key]"
				:type="field.type"
				:required="field.required"
				:label="field.label"
				:show-trailing-button="false" />

			<NcTextArea
				v-else-if="field.type === 'textarea'"
				:id="`field-${field.key}`"
				v-model="form[field.key]"
				:required="field.required"
				:label="field.label" />

			<NcSelect
				v-else-if="field.type === 'select'"
				:id="`field-${field.key}`"
				v-model="form[field.key]"
				:options="field.options"
				:label="field.optionLabel || 'label'"
				:reduce="field.optionValue ? v => v[field.optionValue] : v => v"
				:placeholder="field.label" />
		</div>

		<div class="cm-entity-form__actions">
			<NcButton type="secondary" @click="ui.closeSlidePanel">
				{{ t('charity', 'Cancel') }}
			</NcButton>
			<NcButton type="primary" native-type="submit">
				{{ submitLabel }}
			</NcButton>
		</div>
	</form>
</template>

<script>
import { NcButton, NcTextField, NcTextArea, NcSelect, NcLoadingIcon } from '@nextcloud/vue'
import { useUiStore } from '../stores/ui.js'
import { useCasesStore, usePaymentsStore, useUpdatesStore, useCitiesStore, useCaseTypesStore, useUpdateTypesStore } from '../stores/entities.js'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'EntityForm',
	components: {
		NcButton,
		NcTextField,
		NcTextArea,
		NcSelect,
		NcLoadingIcon,
	},
	props: {
		mode: { type: String, required: true },
		entityType: { type: String, required: true },
		entity: { type: Object, default: null },
	},
	setup() {
		const ui = useUiStore()
		const stores = {
			cc_Case: useCasesStore(),
			cc_Payment: usePaymentsStore(),
			cc_Update: useUpdatesStore(),
			cc_City: useCitiesStore(),
			cc_CaseType: useCaseTypesStore(),
			cc_UpdateType: useUpdateTypesStore(),
		}
		return { ui, stores, t }
	},
	async mounted() {
		this.loadingReferences = true
		await this.loadReferenceStores()
		this.loadingReferences = false
	},
	data() {
		return {
			form: {},
			loadingReferences: false,
		}
	},
	computed: {
		store() {
			return this.stores[this.entityType]
		},
		submitLabel() {
			return this.mode === 'add' ? t('charity', 'Create') : t('charity', 'Save Changes')
		},
		fields() {
			switch (this.entityType) {
			case 'cc_Case':
				return [
					{ key: 'firstName', label: t('charity', 'First Name'), type: 'text', required: true },
					{ key: 'lastName', label: t('charity', 'Last Name'), type: 'text', required: true },
					{ key: 'idNumber', label: t('charity', 'ID Number'), type: 'text' },
					{ key: 'caseTypeId', label: t('charity', 'Case Type'), type: 'select', options: this.stores.cc_CaseType?.items || [], optionLabel: 'title', optionValue: 'id' },
					{ key: 'dateAdded', label: t('charity', 'Date Added'), type: 'date' },
					{ key: 'dob', label: t('charity', 'Date of Birth'), type: 'date' },
					{ key: 'referredBy', label: t('charity', 'Referred By'), type: 'text' },
					{ key: 'cityId', label: t('charity', 'City'), type: 'select', options: this.stores.cc_City?.items || [], optionLabel: 'title', optionValue: 'id' },
					{ key: 'town', label: t('charity', 'Town'), type: 'text' },
					{ key: 'location', label: t('charity', 'Location'), type: 'text' },
					{ key: 'dependants', label: t('charity', 'Dependants'), type: 'number' },
					{ key: 'description', label: t('charity', 'Description'), type: 'textarea' },
					{ key: 'recommendation', label: t('charity', 'Recommendation'), type: 'textarea' },
				]
			case 'cc_Payment':
				return [
					{ key: 'caseId', label: t('charity', 'Case'), type: 'select', options: this.stores.cc_Case?.items || [], optionLabel: 'firstName', optionValue: 'id', required: true },
					{ key: 'paymentDate', label: t('charity', 'Payment Date'), type: 'date', required: true },
					{ key: 'paymentType', label: t('charity', 'Payment Type'), type: 'text' },
					{ key: 'paymentReceipt', label: t('charity', 'Payment Receipt'), type: 'text' },
					{ key: 'paidBy', label: t('charity', 'Paid By'), type: 'text' },
				]
			case 'cc_Update':
				return [
					{ key: 'caseId', label: t('charity', 'Case'), type: 'select', options: this.stores.cc_Case?.items || [], optionLabel: 'firstName', optionValue: 'id', required: true },
					{ key: 'updateDate', label: t('charity', 'Update Date'), type: 'date', required: true },
					{ key: 'updateTypeId', label: t('charity', 'Update Type'), type: 'select', options: this.stores.cc_UpdateType?.items || [], optionLabel: 'title', optionValue: 'id' },
					{ key: 'updateBy', label: t('charity', 'Updated By'), type: 'text' },
					{ key: 'description', label: t('charity', 'Description'), type: 'textarea' },
				]
			case 'cc_City':
				return [
					{ key: 'title', label: t('charity', 'Title'), type: 'text', required: true },
				]
			case 'cc_CaseType':
				return [
					{ key: 'title', label: t('charity', 'Title'), type: 'text', required: true },
				]
			case 'cc_UpdateType':
				return [
					{ key: 'title', label: t('charity', 'Title'), type: 'text', required: true },
				]
			default:
				return []
			}
		},
	},
	watch: {
		entity: {
			immediate: true,
			handler(entity) {
				this.form = entity ? { ...entity } : {}
				this.fields.forEach(field => {
					if (this.form[field.key] === undefined) {
						this.$set(this.form, field.key, null)
					}
				})
			},
		},
	},
	methods: {
		async loadReferenceStores() {
			const needed = {
				cc_Case: ['cc_CaseType', 'cc_City'],
				cc_Payment: ['cc_Case'],
				cc_Update: ['cc_Case', 'cc_UpdateType'],
				cc_City: [],
				cc_CaseType: [],
				cc_UpdateType: [],
			}[this.entityType] || []
			await Promise.all(needed.map(async key => {
				if (this.stores[key]) {
					await this.stores[key].fetchAll()
				}
			}))
		},
		async submit() {
			const payload = { ...this.form }
			try {
				if (this.mode === 'add') {
					await this.store.create(payload)
				} else {
					await this.store.update(payload.id, payload)
				}
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
.cm-entity-form {
	position: relative;
	display: flex;
	flex-direction: column;
	gap: 20px;
}

.cm-entity-form__field {
	display: flex;
	flex-direction: column;
	gap: 6px;
}

.cm-entity-form__field label {
	font-size: 14px;
	font-weight: 600;
	color: var(--color-text-maxcontrast);
}

.cm-entity-form__required {
	color: var(--color-error);
}

.cm-entity-form__actions {
	display: flex;
	justify-content: flex-end;
	gap: 8px;
	margin-top: 8px;
}

.cm-entity-form__loading {
	position: absolute;
	inset: 0;
	background: var(--color-main-background);
	opacity: 0.8;
	display: flex;
	align-items: center;
	justify-content: center;
	z-index: 10;
}
</style>
