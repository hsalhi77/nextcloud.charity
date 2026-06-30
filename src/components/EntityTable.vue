<template>
	<div class="cm-table">
		<div class="cm-table__header cm-table__row">
			<div
				v-for="column in columns"
				:key="column.key"
				class="cm-table__cell"
				:style="{ width: column.width || 'auto' }">
				{{ column.label }}
			</div>
			<div class="cm-table__cell cm-table__cell--actions" />
		</div>

		<div
			v-for="(item, index) in items"
			:key="item.id || index"
			class="cm-table__row"
			:data-row-index="index"
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
					<button ref="triggerBtn" class="cm-table__actions-trigger" @click="toggleMenu(index, $event)" :aria-label="t('charity', 'Actions')">
						<DotsVerticalIcon :size="18" />
					</button>
				</div>
			</div>
		</div>

		<div v-if="!items.length" class="cm-table__empty">
			<NcEmptyContent :title="emptyText">
				<template #action>
					<slot name="emptyAction" />
				</template>
			</NcEmptyContent>
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
	},
	emits: ['row-click', 'action'],
	data() {
		return {
			menuVisible: false,
			menuActions: [],
			menuItem: null,
			menuIndex: -1,
			menuStyle: {},
		}
	},
	mounted() {
		document.addEventListener('click', this.onDocumentClick)
	},
	beforeDestroy() {
		document.removeEventListener('click', this.onDocumentClick)
	},
	methods: {
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
				disabled: this.actionsFilter ? !this.actionsFilter(this.items[index], a) : false,
			}))
			this.menuItem = this.items[index]
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
	-webkit-overflow-scrolling: touch;
}

.cm-table__row {
	display: flex;
	align-items: center;
	padding: 12px 44px 12px 16px;
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
