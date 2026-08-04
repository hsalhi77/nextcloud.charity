<template>
	<div class="cm-table">
		<div class="cm-table__header cm-table__row">
			<div
				v-for="column in columns"
				:key="column.key"
				class="cm-table__cell cm-table__cell--sortable"
				:style="{ width: column.width || 'auto' }"
				role="button"
				tabindex="0"
				:aria-sort="ariaSort(column)"
				@click="toggleSort(column)"
				@keydown.enter="toggleSort(column)"
				@keydown.space.prevent="toggleSort(column)">
				{{ column.label }}
				<span v-if="isSorted(column)" class="cm-table__sort-indicator">{{ sortDirection === 'asc' ? '▲' : '▼' }}</span>
			</div>
			<div class="cm-table__cell cm-table__cell--actions" />
		</div>

		<div
			v-for="(item, index) in paginatedItems"
			:key="item.id || index"
			class="cm-table__row"
			:data-row-index="originalIndex(index)"
			tabindex="0"
			role="button"
			@click="$emit('row-click', item)"
			@keydown.enter="$emit('row-click', item)"
			@keydown.space.prevent="$emit('row-click', item)">
			<div
				v-for="column in columns"
				:key="column.key"
				class="cm-table__cell"
				:style="{ width: column.width || 'auto' }">
				<span v-if="column.badge" class="cm-table__badge" :class="badgeClass(item, column)">
					{{ formatValue(item, column) }}
				</span>
				<template v-else>{{ formatValue(item, column) }}</template>
			</div>
			<div class="cm-table__cell cm-table__cell--actions" @click.stop>
				<div v-if="actions.length" class="cm-table__actions-dropdown">
					<button ref="triggerBtn" class="cm-table__actions-trigger" @click="toggleMenu(originalIndex(index), $event)" :aria-label="t('charity', 'Actions')">
						<DotsVerticalIcon :size="18" />
					</button>
				</div>
			</div>
		</div>

		<div v-if="!sortedItems.length" class="cm-table__empty">
			<NcEmptyContent :title="emptyText">
				<template #action>
					<slot name="emptyAction" />
				</template>
			</NcEmptyContent>
		</div>

		<div v-if="totalPages > 1" class="cm-table__pagination">
			<span class="cm-table__pagination-info">
				{{ t('charity', 'Showing {start}–{end} of {total}', { start: pageStart, end: pageEnd, total: sortedItems.length }) }}
			</span>
			<div class="cm-table__pagination-controls">
				<button
					class="cm-table__pagination-btn"
					:disabled="currentPage === 1"
					:aria-label="t('charity', 'Previous page')"
					@click="goToPage(currentPage - 1)">‹</button>
				<span class="cm-table__pagination-page">{{ currentPage }} / {{ totalPages }}</span>
				<button
					class="cm-table__pagination-btn"
					:disabled="currentPage === totalPages"
					:aria-label="t('charity', 'Next page')"
					@click="goToPage(currentPage + 1)">›</button>
			</div>
		</div>

		<div v-if="menuVisible" class="cm-table__context-menu" :style="menuStyle" @click.stop>
			<button
				v-for="action in menuActions"
				:key="action.name"
				class="cm-table__actions-item"
				:class="{ 'cm-table__actions-item--disabled': action.disabled }"
				:disabled="action.disabled"
				@click="!action.disabled && execAction(action.name)">
				{{ action.label }}
			</button>
		</div>
	</div>
</template>

<script>
import { NcEmptyContent } from '@nextcloud/vue'
import DotsVerticalIcon from 'vue-material-design-icons/DotsVertical.vue'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'EntityTable',
	components: {
		NcEmptyContent,
		DotsVerticalIcon,
	},
	setup() { return { t } },
	props: {
		columns: { type: Array, required: true },
		items: { type: Array, required: true },
		actions: { type: Array, default: () => [] },
		actionsFilter: { type: Function, default: null },
		emptyText: { type: String, default: '' },
		pageSize: { type: Number, default: 20 },
		defaultSort: { type: Object, default: null },
	},
	emits: ['row-click', 'action'],
	data() {
		return {
			menuVisible: false,
			menuActions: [],
			menuItem: null,
			menuIndex: -1,
			menuStyle: {},
			sortKey: this.defaultSort ? this.defaultSort.key : null,
			sortDirection: this.defaultSort ? this.defaultSort.direction : 'asc',
			currentPage: 1,
		}
	},
	computed: {
		sortedItems() {
			if (!this.sortKey) return this.items
			const dir = this.sortDirection === 'asc' ? 1 : -1
			const column = this.columns.find(c => c.key === this.sortKey)
			if (!column) return this.items
			return [...this.items].sort((a, b) => {
				const va = this.sortValue(a, column)
				const vb = this.sortValue(b, column)
				if (va === vb) return 0
				if (va === null || va === undefined || va === '') return 1
				if (vb === null || vb === undefined || vb === '') return -1
				if (typeof va === 'number' && typeof vb === 'number') return (va - vb) * dir
				return String(va).localeCompare(String(vb), undefined, { numeric: true, sensitivity: 'base' }) * dir
			})
		},
		totalPages() {
			return Math.max(1, Math.ceil(this.sortedItems.length / this.pageSize))
		},
		paginatedItems() {
			const start = (this.currentPage - 1) * this.pageSize
			return this.sortedItems.slice(start, start + this.pageSize)
		},
		pageStart() {
			return this.sortedItems.length ? (this.currentPage - 1) * this.pageSize + 1 : 0
		},
		pageEnd() {
			return Math.min(this.currentPage * this.pageSize, this.sortedItems.length)
		},
	},
	watch: {
		sortedItems() {
			if (this.currentPage > this.totalPages) this.currentPage = this.totalPages
		},
	},
	mounted() {
		document.addEventListener('click', this.onDocumentClick)
	},
	beforeDestroy() {
		document.removeEventListener('click', this.onDocumentClick)
	},
	methods: {
		sortValue(item, column) {
			let value = column.keyPath
				? column.keyPath.split('.').reduce((obj, key) => obj?.[key], item)
				: item[column.key]
			if (value === null || value === undefined) return ''
			if (typeof value === 'string' && value.trim() !== '' && !isNaN(Number(value))) {
				return Number(value)
			}
			return value
		},
		isSorted(column) {
			return this.sortKey === column.key
		},
		ariaSort(column) {
			if (!this.isSorted(column)) return 'none'
			return this.sortDirection === 'asc' ? 'ascending' : 'descending'
		},
		toggleSort(column) {
			if (!column.key) return
			if (this.sortKey === column.key) {
				this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc'
			} else {
				this.sortKey = column.key
				this.sortDirection = 'asc'
			}
			this.currentPage = 1
		},
		goToPage(page) {
			this.currentPage = Math.min(Math.max(1, page), this.totalPages)
		},
		originalIndex(index) {
			return (this.currentPage - 1) * this.pageSize + index
		},
		formatValue(item, column) {
			let value = item[column.key]
			if (column.formatter) {
				return column.formatter(value, item)
			}
			if (column.keyPath) {
				value = column.keyPath.split('.').reduce((obj, key) => obj?.[key], item)
			}
			if (value === null || value === undefined) return ''
			return value
		},
		badgeClass(item, column) {
			const value = String(item[column.key] ?? '').toLowerCase()
			return `cm-table__badge--${value.replace(/\s+/g, '-')}`
		},
		toggleMenu(index, event) {
			if (this.menuVisible && this.menuIndex === index) {
				this.menuVisible = false
				return
			}
			const rect = event.currentTarget.getBoundingClientRect()
			const menuWidth = 160
			const topSpace = rect.bottom + 4
			const bottomSpace = window.innerHeight - topSpace
			const left = Math.max(8, Math.min(rect.right - menuWidth, window.innerWidth - menuWidth - 8))
			const top = bottomSpace < 200 ? Math.max(8, rect.top - 200 + 28) : topSpace
			this.menuStyle = {
				position: 'fixed',
				top: `${top}px`,
				left: `${left}px`,
				zIndex: 1000,
			}
			this.menuActions = this.actions.map(a => ({
				...a,
				disabled: this.actionsFilter ? !this.actionsFilter(this.sortedItems[index], a) : false,
			}))
			this.menuItem = this.sortedItems[index]
			this.menuIndex = index
			this.menuVisible = true
		},
		execAction(name) {
			this.menuVisible = false
			this.$emit('action', { name, item: this.menuItem, index: this.menuIndex })
		},
		onDocumentClick() {
			this.menuVisible = false
		},
	},
}
</script>

<style scoped>
.cm-table {
	width: 100%;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
	overflow-x: auto;
}

.cm-table__row {
	display: flex;
	align-items: center;
	padding: 2px 44px 2px 16px;
	border-bottom: 1px solid var(--color-border);
	cursor: pointer;
	white-space: nowrap;
	position: relative;
}

.cm-table__row:last-child {
	border-bottom: none;
}

.cm-table__row:hover {
	background: var(--color-background-hover);
}

.cm-table__header {
	background: var(--color-background-dark);
	font-weight: 600;
	cursor: default;
}

.cm-table__cell {
	flex: 1;
	padding: 0 16px;
	min-width: 120px;
}

.cm-table__cell:first-child {
	padding-inline-start: 0;
}

.cm-table__cell--sortable {
	cursor: pointer;
	user-select: none;
}

.cm-table__cell--sortable:hover {
	color: var(--color-primary-element);
}

.cm-table__sort-indicator {
	margin-inline-start: 4px;
	font-size: 10px;
	color: var(--color-primary-element);
}

.cm-table__cell--actions {
	position: absolute;
	inset-inline-end: 4px;
	top: 50%;
	transform: translateY(-50%);
	display: flex;
	justify-content: flex-end;
}

.cm-table__empty {
	padding: 48px;
}

.cm-table__pagination {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
	padding: 4px 12px;
	border-top: 1px solid var(--color-border);
	font-size: 12px;
	color: var(--color-text-maxcontrast);
}

.cm-table__pagination-info {
	white-space: nowrap;
}

.cm-table__pagination-controls {
	display: flex;
	align-items: center;
	gap: 8px;
}

.cm-table__pagination-btn {
	display: flex;
	align-items: center;
	justify-content: center;
	min-width: 26px;
	height: 26px;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius);
	background: var(--color-main-background);
	color: var(--color-main-text);
	font-size: 14px;
	line-height: 1;
	cursor: pointer;
}

.cm-table__pagination-btn:disabled {
	opacity: 0.4;
	cursor: default;
}

.cm-table__pagination-page {
	min-width: 40px;
	text-align: center;
}

.cm-table__badge {
	display: inline-block;
	padding: 2px 10px;
	border-radius: 999px;
	font-size: 12px;
	font-weight: 600;
	text-transform: capitalize;
	background: var(--color-background-dark);
	color: var(--color-text-maxcontrast);
}

.cm-table__badge--open {
	background: rgba(var(--cm-success-rgb, 21, 128, 61), 0.12);
	color: var(--cm-success, #15803D);
}

.cm-table__badge--inprogress,
.cm-table__badge--in-progress {
	background: rgba(var(--cm-accent-rgb, 201, 162, 39), 0.12);
	color: var(--cm-warning, #B45309);
}

.cm-table__badge--closed {
	background: rgba(var(--cm-muted-rgb, 100, 116, 139), 0.12);
	color: var(--cm-muted, #64748B);
}

.cm-table__actions-dropdown {
}

.cm-table__actions-trigger {
	display: flex;
	align-items: center;
	justify-content: center;
	width: 28px;
	height: 28px;
	border: none;
	border-radius: var(--border-radius);
	background: transparent;
	color: var(--color-text-maxcontrast);
	cursor: pointer;
}

.cm-table__actions-trigger:hover {
	background: var(--color-background-hover);
	color: var(--color-main-text);
}

.cm-table__context-menu {
	min-width: 140px;
	background: var(--color-main-background);
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-large);
	box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
	overflow: hidden;
}

.cm-table__actions-item {
	display: block;
	width: 100%;
	padding: 8px 14px;
	border: none;
	background: transparent;
	font-size: 13px;
	text-align: left;
	color: var(--color-main-text);
	cursor: pointer;
}

.cm-table__actions-item:hover {
	background: var(--color-background-hover);
}

.cm-table__actions-item--disabled {
	opacity: 0.4;
	cursor: default;
}

.cm-table__actions-item--disabled:hover {
	background: transparent;
}
</style>
