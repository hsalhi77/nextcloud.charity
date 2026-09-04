/**
 * Export tabular data to a CSV file that opens in Excel.
 *
 * @param {string} filename - e.g. 'payments.csv'
 * @param {Array} columns - array of { key, label, formatter }
 * @param {Array} items - row data
 */
export function exportToCsv(filename, columns, items) {
	const headers = columns.map(c => c.label).join(',')

	const escape = (value) => {
		if (value === null || value === undefined) return ''
		const str = String(value)
		if (str.includes(',') || str.includes('"') || str.includes('\n') || str.includes('\r')) {
			return `"${str.replace(/"/g, '""')}"`
		}
		return str
	}

	const rows = items.map(item => {
		return columns.map(col => {
			let value = item[col.key]
			if (col.formatter) {
				value = col.formatter(value, item)
			}
			return escape(value)
		}).join(',')
	})

	const csv = [headers, ...rows].join('\r\n')
	// UTF-8 BOM helps Excel open Arabic/non-ASCII characters correctly
	const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' })
	const link = document.createElement('a')
	link.href = URL.createObjectURL(blob)
	link.download = filename
	link.style.visibility = 'hidden'
	document.body.appendChild(link)
	link.click()
	document.body.removeChild(link)
}
