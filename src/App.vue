<template>
	<NcContent app-name="charity" class="charity-app">
		<NcAppNavigation>
			<template #list>
				<NcAppNavigationItem :to="{ name: 'cases' }" :name="t('charity', 'Cases')" :active="isActive('cases')">
					<template #icon>
						<LayersIcon :size="20" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem :to="{ name: 'payments' }" :name="t('charity', 'Payments')" :active="isActive('payments')">
					<template #icon>
						<CashIcon :size="20" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem :to="{ name: 'updates' }" :name="t('charity', 'Updates')" :active="isActive('updates')">
					<template #icon>
						<BellIcon :size="20" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem v-if="userStore.isAdmin" :to="{ name: 'settings' }" :name="t('charity', 'Settings')" :active="isActive('settings')">
					<template #icon>
						<CogIcon :size="20" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem v-if="userStore.isAdminOrCharityAdmin" :to="{ name: 'city' }" :name="t('charity', 'City')" :active="isActive('city')">
					<template #icon>
						<MapMarkerIcon :size="20" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem v-if="userStore.isAdmin" :to="{ name: 'casetype' }" :name="t('charity', 'Case Types')" :active="isActive('casetype')">
					<template #icon>
						<TagIcon :size="20" />
					</template>
				</NcAppNavigationItem>
				<NcAppNavigationItem v-if="userStore.isAdmin" :to="{ name: 'updatetype' }" :name="t('charity', 'Update Types')" :active="isActive('updatetype')">
					<template #icon>
						<ClipboardListIcon :size="20" />
					</template>
				</NcAppNavigationItem>
			</template>
		</NcAppNavigation>

		<NcAppContent>
			<router-view />
			<SlidePanel v-if="ui.slidePanelOpen" />
		</NcAppContent>
	</NcContent>
</template>

<script>
import {
	NcAppContent,
	NcAppNavigation,
	NcAppNavigationItem,
	NcContent,
} from '@nextcloud/vue'
import LayersIcon from 'vue-material-design-icons/Layers.vue'
import CashIcon from 'vue-material-design-icons/Cash.vue'
import BellIcon from 'vue-material-design-icons/Bell.vue'
import CogIcon from 'vue-material-design-icons/Cog.vue'
import MapMarkerIcon from 'vue-material-design-icons/MapMarker.vue'
import TagIcon from 'vue-material-design-icons/Tag.vue'
import ClipboardListIcon from 'vue-material-design-icons/ClipboardList.vue'
import SlidePanel from './components/SlidePanel.vue'
import { useUiStore } from './stores/ui.js'
import { useUserStore } from './stores/user.js'
import { translate as t } from '@nextcloud/l10n'

export default {
	name: 'App',
	components: {
		NcAppContent,
		NcAppNavigation,
		NcAppNavigationItem,
		NcContent,
		LayersIcon,
		CashIcon,
		BellIcon,
		CogIcon,
		MapMarkerIcon,
		TagIcon,
		ClipboardListIcon,
		SlidePanel,
	},
	setup() {
		const ui = useUiStore()
		const userStore = useUserStore()
		return { ui, userStore, t }
	},
	methods: {
		isActive(name) {
			return this.$route.name === name
		},
	},
}
</script>

<style scoped>
.charity-app {
	height: 100%;
}
</style>
