<template>
	<div class="cm-view">
		<div class="cm-view__header">
			<h2>{{ t('charity', 'Settings') }}</h2>
		</div>

		<div class="cm-settings__setting">
			<NcCheckboxRadioSwitch :checked="createTeamForCase"
				@update:checked="toggleCreateTeamForCase">
				{{ t('charity', 'Create Team for every Case') }}
			</NcCheckboxRadioSwitch>
		</div>

		<div v-if="loading" class="cm-view__loading">
			<NcLoadingIcon :size="32" />
		</div>

		<div v-else class="cm-settings">
			<table class="cm-settings__table">
				<thead>
					<tr>
						<th>{{ t('charity', 'User') }}</th>
						<th>{{ t('charity', 'Group') }}</th>
						<th>{{ t('charity', 'Enabled') }}</th>
					</tr>
				</thead>
				<tbody>
					<tr v-for="user in users" :key="user.uid">
						<td>{{ user.displayName || user.uid }}</td>
						<td>{{ user.group || '' }}</td>
						<td>
							<NcCheckboxRadioSwitch :checked="user.enabled"
								:disabled="user.uid === currentUserId"
								@update:checked="toggleEnabled(user.uid)" />
						</td>
					</tr>
				</tbody>
			</table>
			<div v-if="!users.length" class="cm-view__empty">
				{{ t('charity', 'No users found') }}
			</div>
		</div>
	</div>
</template>

<script>
import { translate as t } from '@nextcloud/l10n'
import { getCurrentUser } from '@nextcloud/auth'
import { get, post } from '../services/api.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'

/**
 * Unwrap the backend envelope when it exists
 * @param {any} res - The response or raw data
 * @return {any} The unwrapped data
 */
function unwrap(res) {
	if (res && typeof res === 'object' && 'data' in res) {
		return res.data
	}
	return res
}

export default {
	name: 'Settings',
	components: { NcLoadingIcon, NcCheckboxRadioSwitch },
	setup() {
		return {
			t,
			currentUserId: getCurrentUser()?.uid,
		}
	},
	data() {
		return {
			loading: false,
			createTeamForCase: true,
			users: [],
		}
	},
	async mounted() {
		await Promise.all([this.loadConfig(), this.loadUsers()])
	},
	methods: {
		async loadConfig() {
			try {
				const config = unwrap(await get('/api/v1.0/config'))
				this.createTeamForCase = config.createTeamForCase !== false
			} catch (err) {
				console.error(err)
			}
		},
		async toggleCreateTeamForCase(val) {
			this.createTeamForCase = val
			try {
				await post('/api/v1.0/config/createTeamForCase', { value: val ? '1' : '0' })
			} catch (err) {
				console.error(err)
				this.createTeamForCase = !val
			}
		},
		async loadUsers() {
			this.loading = true
			try {
				const users = unwrap(await post('/team/searchUsers', { params: { search: '' } }))
				this.users = (users || []).map(u => ({
					uid: u.uid,
					displayName: u.displayName,
					group: (u.groups || []).join(', '),
					enabled: u.enabled,
				}))
			} catch (err) {
				console.error(err)
			}
			this.loading = false
		},
		async toggleEnabled(uid) {
			try {
				const res = unwrap(await post('/team/toggleUserEnabled', { params: { uid } }))
				const user = this.users.find(u => u.uid === uid)
				if (user) user.enabled = res.enabled
			} catch (err) {
				console.error(err)
			}
		},
	},
}
</script>

<style scoped>
.cm-settings {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.cm-settings__setting {
    margin-bottom: 16px;
    padding: 12px 16px;
    border: 1px solid var(--color-border);
    border-radius: var(--border-radius-large);
    background: var(--color-main-background);
}

.cm-settings__table {
    width: 100%;
    border-collapse: collapse;
}

.cm-settings__table th,
.cm-settings__table td {
    text-align: left;
    padding: 6px 8px;
    border-bottom: 1px solid var(--color-border);
}

.cm-settings__table th {
    font-size: 12px;
    font-weight: 600;
    color: var(--color-text-maxcontrast);
}

.cm-view__empty {
    padding: 8px;
    color: var(--color-text-maxcontrast);
    font-style: italic;
}

.cm-view__header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
}

.cm-view__header h2 {
    margin: 0;
    font-size: 24px;
    font-weight: 700;
}

.cm-view__loading {
    display: flex;
    justify-content: center;
    padding: 48px;
}
</style>
