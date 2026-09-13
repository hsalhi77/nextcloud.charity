import { getLocale } from '@nextcloud/l10n'

const locale = getLocale().replace(/_/g, '-')

/**
 * Parse a yyyy-mm-dd string into a local Date object (midnight, no timezone shift).
 *
 * @param {string|Date|null|undefined} value
 * @returns {Date|null}
 */
export function parseLocalDate(value) {
	if (!value) return null
	if (value instanceof Date) return value
	const normalized = String(value).trim()
	if (!/^\d{4}-\d{2}-\d{2}$/.test(normalized)) return null
	const [year, month, day] = normalized.split('-').map(Number)
	const date = new Date(year, month - 1, day)
	if (isNaN(date.getTime())) return null
	return date
}

/**
 * Format a date string or Date object for display using the user's Nextcloud locale.
 *
 * @param {string|Date|null|undefined} value
 * @returns {string}
 */
export function formatDate(value) {
	if (!value) return ''
	const date = typeof value === 'string' ? parseLocalDate(value) : value
	if (!date || isNaN(date.getTime())) return ''
	return date.toLocaleDateString(locale)
}

/**
 * Check whether a value is a valid yyyy-mm-dd date string that represents a real calendar date.
 *
 * @param {string|Date} value
 * @returns {boolean}
 */
export function isValidDate(value) {
	if (!value) return false
	if (value instanceof Date) return !isNaN(value.getTime())
	const normalized = String(value).trim()
	if (!/^\d{4}-\d{2}-\d{2}$/.test(normalized)) return false
	const [year, month, day] = normalized.split('-').map(Number)
	const date = new Date(year, month - 1, day)
	if (isNaN(date.getTime())) return false
	return date.getFullYear() === year && (date.getMonth() + 1) === month && date.getDate() === day
}

/**
 * Format a date for an HTML date input (always yyyy-mm-dd).
 *
 * @param {string|Date|null|undefined} value
 * @returns {string}
 */
export function formatDateForInput(value) {
	if (!value) return ''
	const date = typeof value === 'string' ? parseLocalDate(value) : value
	if (!date || isNaN(date.getTime())) return ''
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

/**
 * Derive a vue2-datepicker / moment.js format string from the user's Nextcloud locale.
 * e.g. en-GB -> 'DD/MM/YYYY', en-US -> 'M/D/YYYY', de -> 'DD.MM.YYYY'.
 *
 * @param {string} [localeName]
 * @returns {string}
 */
export function getDateFormatForLocale(localeName = locale) {
	const sample = new Date(2026, 10, 9)
	let format = ''
	try {
		const parts = new Intl.DateTimeFormat(localeName).formatToParts(sample)
		for (const part of parts) {
			if (part.type === 'year') {
				format += 'YYYY'
			} else if (part.type === 'month') {
				format += part.value.length === 2 ? 'MM' : 'M'
			} else if (part.type === 'day') {
				format += part.value.length === 2 ? 'DD' : 'D'
			} else {
				format += part.value
			}
		}
	} catch (e) {
		format = 'YYYY-MM-DD'
	}
	return format || 'YYYY-MM-DD'
}

const tokenPatterns = {
	YYYY: '(\\d{4})',
	MM: '(\\d{2})',
	DD: '(\\d{2})',
	M: '(\\d{1,2})',
	D: '(\\d{1,2})',
}

/**
 * Parse a date string that matches a vue2-datepicker / moment.js format.
 *
 * @param {string} value
 * @param {string} format
 * @returns {Date|null}
 */
export function parseDateByFormat(value, format) {
	if (!value || !format) return null
	let regexStr = format.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&')
	for (const [token, pattern] of Object.entries(tokenPatterns)) {
		regexStr = regexStr.split(token).join(pattern)
	}
	const match = String(value).match(new RegExp(`^${regexStr}$`))
	if (!match) return null
	const keys = format.match(/YYYY|MM|DD|M|D/g)
	if (!keys) return null
	const parts = {}
	keys.forEach((key, i) => {
		parts[key] = parseInt(match[i + 1], 10)
	})
	const year = parts.YYYY
	const month = parts.MM ?? parts.M
	const day = parts.DD ?? parts.D
	if (!year || !month || !day) return null
	const date = new Date(year, month - 1, day)
	if (isNaN(date.getTime())) return null
	if (date.getFullYear() !== year || date.getMonth() + 1 !== month || date.getDate() !== day) return null
	return date
}
