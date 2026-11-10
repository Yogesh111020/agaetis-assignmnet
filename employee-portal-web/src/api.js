import axios from 'axios'

const client = axios.create({
  baseURL: (import.meta.env.VITE_API_URL || 'http://localhost:8000/api').replace(/\/$/, ''),
  headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
})

export async function request(path, options = {}) {
  try {
    const { body, ...config } = options
    const response = await client.request({
      ...config,
      url: path,
      data: body,
    })
    return response.status === 204 ? null : response.data
  } catch (cause) {
    if (axios.isCancel(cause)) {
      const error = new Error('Request canceled.')
      error.name = 'AbortError'
      throw error
    }
    if (!cause.response) {
      throw new Error('Cannot connect to the API. Check that Laravel is running on localhost:8000.')
    }
    const error = new Error(cause.response.data?.message || `Request failed (${cause.response.status}). Please try again.`)
    error.fields = cause.response.data?.errors || {}
    throw error
  }
}
