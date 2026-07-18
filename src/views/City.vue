<template>
	<div class="cm-view">
		<header class="cm-view__header">
			<h1>{{ t('charity', 'Cities') }}</h1>
			<div class="cm-view__actions">
				<NcButton type="primary" @click="openAddPanel">
					<template #icon>
						<PlusIcon :size="16" />
					</template>
					{{ t('charity', 'Add City') }}
				</NcButton>
			</div>
		</header>

		<div v-if="citiesStore.loading" class="cm-view__loading">
			<NcLoadingIcon :size="32" />
		</div>

		<EntityTable v-else
			:columns="columns"
			:items="citiesStore.items"
			:actions="actions"
			:empty-text="t('charity', 'No cities found')"
			@row-click="openEditPanel"
			@action="onAction" />
	</div>
</template>

<script>
import { NcButton, NcLoadingIcon } from '@nextcloud/vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'
import EntityTable from '../components/EntityTable.vue'
import { useCitiesStore } from '../stores/entities.js'
import { useUiStore } from '../stores/ui.js'
import { useUserStore } from '../stores/user.js'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'City',
	components: {
		NcButton,
		NcLoadingIcon,
		PlusIcon,
		EntityTable,
	},
	setup() {
		const citiesStore = useCitiesStore()
		const ui = useUiStore()
		const userStore = useUserStore()
		return {
			citiesStore,
			ui,
			userStore,
			t,
		}
	},
	computed: {
		columns() {
			return [
				{ key: 'title', label: t('charity', 'Title') },
			]
		},
		actions() {
			const base = [
				{ name: 'edit', label: t('charity', 'Edit'), icon: 'icon-edit' },
			]
			if (this.userStore.isAdmin) {
				base.push({ name: 'delete', label: t('charity', 'Delete'), icon: 'icon-delete' })
			}
			return base
		},
	},
	async mounted() {
		await this.citiesStore.fetchAll()
	},
	methods: {
		openAddPanel() {
			this.ui.openSlidePanel({ mode: 'add', entityType: 'cc_City' })
		},
		openEditPanel(item) {
			this.ui.openSlidePanel({ mode: 'edit', entityType: 'cc_City', entity: item })
		},
		onAction({ name, item }) {
			if (name === 'edit') this.openEditPanel(item)
			if (name === 'delete') this.deleteCity(item)
		},
		async deleteCity(item) {
			if (!confirm(t('charity', 'Are you sure you want to delete this city?'))) return
			try {
				await this.citiesStore.remove(item.id)
			} catch (err) {
				console.error(err)
				alert(err.message)
			}
		},
	},
}
</script>

<style scoped>
.cm-view {
    padding: 24px;
    height: 100%;
    display: flex;
    flex-direction: column;
}

.cm-view__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.cm-view__header h1 {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
}

.cm-view__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.cm-view__loading {
    display: flex;
    justify-content: center;
    padding: 48px;
}
</style>
