<template>
	<div class="cm-table-placeholder">
		<div v-for="item in items" :key="item.id">
			{{ item }}
		</div>
	</div>
</template>

<script>
export default {
	name: 'EntityTable',
	props: {
		columns: { type: Array, required: true },
		items: { type: Array, required: true },
		actions: { type: Array, default: () => [] },
		actionsFilter: { type: Function, default: null },
		emptyText: { type: String, default: '' },
	},
}
</script>

<style scoped>
.cm-table-placeholder {
	padding: 24px;
}
</style>
