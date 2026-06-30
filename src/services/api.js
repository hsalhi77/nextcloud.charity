import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export async function get(url) {
    const response = await axios.get(generateUrl(`/apps/charity${url}`))
    return response.data
}

export async function post(url, data) {
    const response = await axios.post(generateUrl(`/apps/charity${url}`), data)
    return response.data
}

export async function put(url, data) {
    const response = await axios.put(generateUrl(`/apps/charity${url}`), data)
    return response.data
}

export async function del(url) {
    const response = await axios.delete(generateUrl(`/apps/charity${url}`))
    return response.data
}
