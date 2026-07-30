<template>
	<div class="cm-bar-chart">
		<div class="cm-bar-chart__plot">
			<div class="cm-bar-chart__y-axis">
				<span v-for="tick in yTicks" :key="tick" class="cm-bar-chart__y-tick">{{ tick }}</span>
			</div>
			<div class="cm-bar-chart__months">
				<div v-for="month in data" :key="month.month" class="cm-bar-chart__month">
					<div class="cm-bar-chart__bars">
						<div
							v-for="user in month.users"
							:key="user.uid"
							class="cm-bar-chart__user-bar"
							:title="userTooltip(user)">
							<template v-for="series in seriesConfig" :key="series.key">
								<div
									v-if="user[series.key] > 0"
									class="cm-bar-chart__segment"
									:style="segmentStyle(user[series.key], series.color)"
									:title="segmentTooltip(user, series)">
								</div>
							</template>
						</div>
						<div class="cm-bar-chart__grid-lines">
							<div
								v-for="tick in yTicks"
								:key="tick"
								class="cm-bar-chart__grid-line"
								:style="gridLineStyle(tick)">
							</div>
						</div>
					</div>
					<div class="cm-bar-chart__user-labels">
						<span v-for="user in month.users" :key="user.uid" class="cm-bar-chart__user-label" :title="user.displayName">
							{{ initials(user.displayName) }}
						</span>
					</div>
					<div class="cm-bar-chart__month-label">{{ month.month }}</div>
				</div>
			</div>
		</div>
		<div class="cm-bar-chart__legend">
			<span v-for="series in seriesConfig" :key="series.key" class="cm-bar-chart__legend-item">
				<span class="cm-bar-chart__legend-dot" :style="{ backgroundColor: series.color }"></span>
				{{ series.label }}
			</span>
		</div>
	</div>
</template>

<script>
export default {
	name: 'BarChart',
	props: {
		data: {
			type: Array,
			required: true,
		},
	},
	data() {
		return {
			seriesConfig: [
				{ key: 'cases', label: t('charity', 'Cases'), color: 'var(--color-primary)' },
				{ key: 'payments', label: t('charity', 'Payments'), color: 'var(--color-success)' },
				{ key: 'updates', label: t('charity', 'Updates'), color: 'var(--color-warning)' },
			],
		}
	},
	computed: {
		maxValue() {
			let max = 0
			for (const month of this.data) {
				for (const user of month.users) {
					const total = user.cases + user.payments + user.updates
					if (total > max) {
						max = total
					}
				}
			}
			return max
		},
		yStep() {
			if (this.maxValue <= 1) {
				return 1
			}
			if (this.maxValue <= 5) {
				return 1
			}
			return Math.ceil(this.maxValue / 5)
		},
		yMax() {
			return Math.max(Math.ceil(this.maxValue / this.yStep) * this.yStep, 1)
		},
		yTicks() {
			const ticks = []
			for (let v = this.yMax; v >= 0; v -= this.yStep) {
				ticks.push(v)
			}
			return ticks
		},
	},
	methods: {
		segmentTooltip(user, series) {
			return `${user.displayName} – ${series.label}: ${user[series.key]}`
		},
		userTooltip(user) {
			const total = user.cases + user.payments + user.updates
			return `${user.displayName} – ${t('charity', 'Total')}: ${total}`
		},
		initials(name) {
			if (!name) {
				return ''
			}
			const parts = name.trim().split(/\s+/)
			if (parts.length >= 2) {
				return (parts[0][0] + parts[1][0]).toUpperCase()
			}
			return name.substring(0, 2).toUpperCase()
		},
		segmentStyle(value, color) {
			const height = (value / this.yMax) * 100
			return {
				height: `${height}%`,
				backgroundColor: color,
			}
		},
		gridLineStyle(tick) {
			return {
				bottom: `${(tick / this.yMax) * 100}%`,
			}
		},
	},
}
</script>

<style scoped>
.cm-bar-chart {
	display: flex;
	flex-direction: column;
	gap: 8px;
	overflow: visible;
}

.cm-bar-chart__plot {
	display: flex;
	gap: 8px;
	overflow: visible;
}

.cm-bar-chart__y-axis {
	display: flex;
	flex-direction: column;
	justify-content: space-between;
	height: 80px;
	padding-bottom: 3px;
	padding-top: 2px;
}

.cm-bar-chart__y-tick {
	font-size: 10px;
	color: var(--color-text-maxcontrast);
	text-align: right;
	line-height: 1;
}

.cm-bar-chart__months {
	display: flex;
	flex: 1;
	gap: 12px;
	overflow-x: auto;
}

.cm-bar-chart__month {
	display: flex;
	flex-direction: column;
	flex: 1;
	min-width: 70px;
	gap: 1px;
}

.cm-bar-chart__bars {
	position: relative;
	display: flex;
	align-items: flex-end;
	justify-content: center;
	gap: 4px;
	height: 80px;
	border-bottom: 1px solid var(--color-border);
	padding-bottom: 2px;
}

.cm-bar-chart__user-bar {
	width: 16px;
	display: flex;
	flex-direction: column-reverse;
	justify-content: flex-start;
	gap: 1px;
	height: 100%;
	border-radius: 2px;
	overflow: hidden;
	z-index: 1;
}

.cm-bar-chart__segment {
	width: 100%;
	min-height: 1px;
	transition: height 0.2s ease;
}

.cm-bar-chart__grid-lines {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	pointer-events: none;
	z-index: 0;
}

.cm-bar-chart__grid-line {
	position: absolute;
	left: 0;
	right: 0;
	border-top: 1px dashed var(--color-border);
}

.cm-bar-chart__user-labels {
	display: flex;
	justify-content: center;
	gap: 4px;
}

.cm-bar-chart__user-label {
	width: 16px;
	font-size: 9px;
	color: var(--color-text-maxcontrast);
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
	text-align: center;
}

.cm-bar-chart__month-label {
	text-align: center;
	font-size: 11px;
	font-weight: 700;
	color: var(--color-text-main);
}

.cm-bar-chart__legend {
	display: flex;
	gap: 12px;
	justify-content: center;
}

.cm-bar-chart__legend-item {
	display: inline-flex;
	align-items: center;
	gap: 4px;
	font-size: 11px;
	color: var(--color-text-maxcontrast);
}

.cm-bar-chart__legend-dot {
	width: 8px;
	height: 8px;
	border-radius: 50%;
}
</style>
