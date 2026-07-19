<template>
	<div class="cm-filter">
		<div class="cm-filter__fields">
			<div v-for="field in fields" :key="field.key" class="cm-filter__field">
				<label :for="`filter-${field.key}`">{{ field.label }}</label>
				<NcTextField
					v-if="field.type !== 'select'"
					:id="`filter-${field.key}`"
					v-model="values[field.key]"
					:label="field.label"
					:type="field.inputType || 'text'"
					:show-trailing-button="false" />
				<NcSelect
					v-else
					:id="`filter-${field.key}`"
					v-model="values[field.key]"
					:options="fieldOptions(field)"
					:label="field.optionLabel || 'label'"
					:reduce="field.optionValue ? v => v[field.optionValue] : v => v"
					:placeholder="field.label" />
			</div>
		</div>
		<div class="cm-filter__actions">
			<NcButton type="secondary" @click="clear">
				{{ t('charity', 'Clear') }}
			</NcButton>
			<NcButton type="primary" @click="apply">
				{{ t('charity', 'Apply') }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import { NcButton, NcTextField, NcSelect } from '@nextcloud/vue'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'EntityFilter',
	components: {
		NcButton,
		NcTextField,
		NcSelect,
	},
	props: {
		fields: { type: Array, required: true },
	},
	emits: ['filter', 'clear'],
	data() {
		return {
			values: {},
			t,
		}
	},
	methods: {
		fieldOptions(field) {
			if (Array.isArray(field.options)) {
				if (field.optionLabel && field.optionValue) return field.options
				return field.options.map(o => ({ label: o, value: o }))
			}
			return []
		},
		apply() {
			this.$emit('filter', { ...this.values })
		},
		clear() {
			this.values = {}
			this.$emit('clear')
		},
	},
}
</script>

<style scoped>
.cm-filter {
	background: var(--color-main-background);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
	padding: 16px;
	margin-bottom: 16px;
}

.cm-filter__fields {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
	gap: 16px;
	margin-bottom: 16px;
}

.cm-filter__field {
	display: flex;
	flex-direction: column;
	gap: 4px;
}

.cm-filter__field label {
	font-size: 13px;
	font-weight: 600;
	color: var(--color-text-maxcontrast);
}

.cm-filter__actions {
	display: flex;
	justify-content: flex-end;
	gap: 8px;
}
</style>
