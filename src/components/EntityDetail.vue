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

				<div v-if="entityType === 'cc_Case'" class="cm-entity-detail__related">
					<h3>{{ t('charity', 'Payments') }}</h3>
					<div v-if="relatedLoading" class="cm-entity-detail__loading">
						<NcLoadingIcon :size="24" />
					</div>
					<table v-else-if="relatedPayments.length" class="cm-entity-detail__related-table">
						<thead>
							<tr>
								<th>{{ t('charity', 'Date') }}</th>
								<th>{{ t('charity', 'Type') }}</th>
								<th>{{ t('charity', 'Amount') }}</th>
								<th>{{ t('charity', 'Cashbook') }}</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="payment in relatedPayments" :key="payment.id" class="cm-entity-detail__clickable-row" @click="openPayment(payment.id)">
								<td>{{ formatDate(payment.paymentDate) }}</td>
								<td>{{ payment.paymentType }}</td>
								<td>{{ payment.paymentAmount }}</td>
								<td>{{ payment.paidBy }}</td>
							</tr>
						</tbody>
					</table>
					<div v-else class="cm-entity-detail__coming-soon">
						{{ t('charity', 'No payments for this case') }}
					</div>

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
							<tr v-for="update in relatedUpdates" :key="update.id" class="cm-entity-detail__clickable-row" @click="openUpdate(update.id)">
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
			</div>

			<!-- ATTACHMENTS -->
			<div v-else-if="activeTab === 'attachments'" class="cm-entity-detail__attachments">
				<h3>{{ t('charity', 'Attachments') }}</h3>

				<div v-if="attachmentsStore.loading" class="cm-entity-detail__loading">
					<NcLoadingIcon :size="24" />
				</div>

				<div v-else-if="attachments.length" class="cm-entity-detail__attachment-list">
					<div v-for="att in attachments" :key="att.id" class="cm-entity-detail__attachment-row">
						<a v-if="att.url" :href="att.url" target="_blank" class="cm-entity-detail__attachment-link">
							{{ att.name || att.data }}
						</a>
						<span v-else class="cm-entity-detail__attachment-name">{{ att.name || att.data }}</span>
						<span v-if="att.tag" class="cm-entity-detail__attachment-tag">{{ att.tag }}</span>
						<span class="cm-entity-detail__attachment-size">{{ formatFileSize(att.size) }}</span>
						<button class="cm-entity-detail__attachment-delete" @click="deleteAttachment(att)" :title="t('charity', 'Delete')">&times;</button>
					</div>
				</div>

				<div v-else class="cm-entity-detail__coming-soon">
					{{ t('charity', 'No attachments yet') }}
				</div>

				<div class="cm-entity-detail__attachment-upload">
					<div class="cm-entity-detail__upload-tag">
						<NcTextField v-model="uploadTag" :label="t('charity', 'Tag')" :show-trailing-button="false" />
					</div>
					<NcButton v-if="!uploadingFile" type="secondary" @click="triggerFilePicker">
						{{ t('charity', 'Upload Attachment') }}
					</NcButton>
					<div v-else class="cm-entity-detail__upload-progress">
						<NcLoadingIcon :size="20" />
						<span>{{ t('charity', 'Uploading...') }}</span>
					</div>
					<input ref="fileInput" type="file" class="hidden" @change="onFileSelected" />
				</div>
			</div>

			<!-- TEAM -->
			<div v-else-if="activeTab === 'team'" class="cm-entity-detail__team">
				<h3>{{ t('charity', 'Team') }}</h3>
				<div class="cm-entity-detail__coming-soon">
					{{ t('charity', 'Team management coming soon') }}
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
import { NcLoadingIcon, NcButton, NcTextField } from '@nextcloud/vue'
import { useCasesStore, usePaymentsStore, useUpdatesStore, useCaseTypesStore, useUpdateTypesStore, useCitiesStore, useAttachmentsStore } from '../stores/entities.js'
import { useUiStore } from '../stores/ui.js'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'EntityDetail',
	components: {
		NcLoadingIcon,
		NcButton,
		NcTextField,
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
		const attachmentsStore = useAttachmentsStore()
		const ui = useUiStore()
		return {
			stores,
			attachmentsStore,
			ui,
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
			uploadingFile: false,
			uploadTag: '',
		}
	},
	computed: {
		store() { return this.stores[this.entityType] },
		item() { return this.store?.byId(this.entityId) },
		attachments() {
			return this.attachmentsStore.forObject(this.entityType, this.entityId)
		},
		tabs() {
			const all = [
				{ name: 'summary', label: t('charity', 'Summary') },
				{ name: 'attachments', label: t('charity', 'Attachments') },
			]
			if (this.entityType === 'cc_Case') {
				all.push(
					{ name: 'team', label: t('charity', 'Team') },
				)
			}
			all.push(
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
				{ key: 'paymentAmount', label: t('charity', 'Amount') },
				{ key: 'paymentReference', label: t('charity', 'Payment Reference') },
					{ key: 'paidBy', label: t('charity', 'Cashbook') },
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
				if (this.entityType === 'cc_Case' && newVal) {
					this.loadPayments()
					this.loadUpdates()
				}
			},
		},
		activeTab(tab) {
			if (!this.entityId) return
			if (tab === 'summary' && this.entityType === 'cc_Case') {
				this.loadPayments()
				this.loadUpdates()
			}
			if (tab === 'attachments') this.loadAttachments()
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
					promises.push(this.stores.cc_UpdateType?.fetchAll())
					promises.push(this.stores.cc_City?.fetchAll())
					promises.push(this.loadPayments())
					promises.push(this.loadUpdates())
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
		async loadAttachments() {
			if (!this.entityId) return
			try {
				await this.attachmentsStore.fetchByObject(this.entityType, this.entityId)
			} catch (e) {
				console.error('Failed to load attachments', e)
			}
		},
		triggerFilePicker() {
			this.$refs.fileInput?.click()
		},
		async onFileSelected(event) {
			const file = event.target.files?.[0]
			if (!file) return
			this.uploadingFile = true
			try {
				const data = await this.readFileAsBase64(file)
				await this.attachmentsStore.upload(this.entityType, this.entityId, {
					name: file.name,
					data,
					size: file.size,
					tag: this.uploadTag || undefined,
				})
			} catch (err) {
				console.error(err)
				alert(err.message || t('charity', 'Upload failed'))
			} finally {
				this.uploadingFile = false
				this.uploadTag = ''
				this.$refs.fileInput.value = ''
			}
		},
		readFileAsBase64(file) {
			return new Promise((resolve, reject) => {
				const reader = new FileReader()
				reader.onload = () => {
					const result = reader.result
					resolve(result.split(',', 2)[1] || result)
				}
				reader.onerror = () => reject(new Error('Failed to read file'))
				reader.readAsDataURL(file)
			})
		},
		formatFileSize(size) {
			if (!size) return ''
			if (size < 1024) return size + ' B'
			if (size < 1048576) return (size / 1024).toFixed(1) + ' KB'
			return (size / 1048576).toFixed(1) + ' MB'
		},
		async deleteAttachment(att) {
			if (!confirm(t('charity', 'Delete this attachment?'))) return
			try {
				await this.attachmentsStore.remove(att.id)
			} catch (err) {
				console.error(err)
				alert(err.message)
			}
		},
		openPayment(id) {
			this.ui.closeSlidePanel()
			this.$router.push({ name: 'payments', query: { highlight: id } })
		},
		openUpdate(id) {
			this.ui.closeSlidePanel()
			this.$router.push({ name: 'updates', query: { highlight: id } })
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
			if (!id) return ''
			return String(id).padStart(10, '0')
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

.cm-entity-detail__related {
	margin-top: 24px;
}

.cm-entity-detail__related h3 {
	margin: 16px 0 8px;
	font-size: 15px;
	font-weight: 700;
}

.cm-entity-detail__related h3:first-child {
	margin-top: 0;
}

.cm-entity-detail__related-table th,
.cm-entity-detail__related-table td {
	text-align: left;
	padding: 6px 8px;
	border-bottom: 1px solid var(--color-border);
}

.cm-entity-detail__clickable-row {
	cursor: pointer;
	transition: background 0.1s;
}

.cm-entity-detail__clickable-row:hover {
	background: var(--color-background-hover);
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

.cm-entity-detail__attachment-list {
	display: flex;
	flex-direction: column;
	gap: 4px;
	margin-bottom: 16px;
}

.cm-entity-detail__attachment-row {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 8px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	font-size: 13px;
}

.cm-entity-detail__attachment-link {
	color: var(--color-primary-element);
	text-decoration: none;
	flex: 1;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.cm-entity-detail__attachment-link:hover {
	text-decoration: underline;
}

.cm-entity-detail__attachment-name {
	flex: 1;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}

.cm-entity-detail__attachment-size {
	color: var(--color-text-maxcontrast);
	font-size: 12px;
	white-space: nowrap;
}

.cm-entity-detail__attachment-delete {
	background: none;
	border: none;
	color: var(--color-error);
	cursor: pointer;
	font-size: 18px;
	padding: 0 4px;
	line-height: 1;
}

.cm-entity-detail__attachment-tag {
	font-size: 11px;
	color: var(--color-primary-element);
	background: rgba(var(--color-primary-element-rgb), 0.08);
	padding: 2px 6px;
	border-radius: 4px;
	white-space: nowrap;
}

.cm-entity-detail__attachment-upload {
	margin-top: 8px;
}

.cm-entity-detail__upload-tag {
	margin-bottom: 8px;
	max-width: 240px;
}

.cm-entity-detail__upload-progress {
	display: flex;
	align-items: center;
	gap: 8px;
	color: var(--color-text-maxcontrast);
	font-size: 13px;
}

.hidden {
	display: none;
}
</style>
