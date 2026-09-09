import { getLocale } from '@nextcloud/l10n'

const locale = getLocale().replace(/_/g, '-')

/**
 * Format a date string or Date object for display using the user's Nextcloud locale.
 *
 * @param {string|Date|null|undefined} value
 * @returns {string}
 */
export function formatDate(value) {
	if (!value) return ''
	const date = typeof value === 'string' ? new Date(value) : value
	if (isNaN(date.getTime())) return ''
	return date.toLocaleDateString(locale)
}

/**
 * Check whether a value is a valid yyyy-mm-dd date string that represents a real calendar date.
 *
 * @param {string} value
 * @returns {boolean}
 */
export function isValidDate(value) {
	if (!value) return false
	const normalized = String(value).trim()
	if (!/^\d{4}-\d{2}-\d{2}$/.test(normalized)) return false
	const date = new Date(normalized)
	if (isNaN(date.getTime())) return false
	const [year, month, day] = normalized.split('-')
	const parsedYear = String(date.getFullYear())
	const parsedMonth = String(date.getMonth() + 1).padStart(2, '0')
	const parsedDay = String(date.getDate()).padStart(2, '0')
	return parsedYear === year && parsedMonth === month && parsedDay === day
}

/**
 * Format a date for an HTML date input (always yyyy-mm-dd).
 *
 * @param {string|Date|null|undefined} value
 * @returns {string}
 */
export function formatDateForInput(value) {
	if (!value) return ''
	const date = typeof value === 'string' ? new Date(value) : value
	if (isNaN(date.getTime())) return ''
	const year = date.getFullYear()
	const month = String(date.getMonth() + 1).padStart(2, '0')
	const day = String(date.getDate()).padStart(2, '0')
	return `${year}-${month}-${day}`
}

/**
 * Today's date as yyyy-mm-dd, useful for max="..." on date inputs.
 *
 * @returns {string}
 */
export function todayInputValue() {
	return formatDateForInput(new Date())
}
