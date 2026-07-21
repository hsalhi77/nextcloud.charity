import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

function unwrap(request) {
	return request.then(res => {
		const data = res.data
		if (data && typeof data === 'object' && 'message' in data) {
			if (data.message !== 'succes') {
				throw new Error(data.message || 'Request failed')
			}
			return data.data
		}
		return data
	})
}

export function get(url) {
	return unwrap(axios.get(generateUrl(`/apps/charity${url}`)))
}

export function post(url, data) {
	return unwrap(axios.post(generateUrl(`/apps/charity${url}`), data))
}

export function put(url, data) {
	return unwrap(axios.put(generateUrl(`/apps/charity${url}`), data))
}

export function del(url) {
	return unwrap(axios.delete(generateUrl(`/apps/charity${url}`)))
}
