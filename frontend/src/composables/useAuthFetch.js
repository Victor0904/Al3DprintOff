import { useAuthStore } from '../stores/auth'

export function useAuthFetch() {
  const authStore = useAuthStore()

  const apiFetch = (url, options = {}) => {
    const isFormData = options.body instanceof FormData

    // Ne pas forcer Content-Type pour FormData : le navigateur le gère avec le bon boundary
    const headers = isFormData
      ? { ...(options.headers || {}) }
      : { 'Content-Type': 'application/json', ...(options.headers || {}) }

    if (authStore.token) {
      headers['Authorization'] = `Bearer ${authStore.token}`
    }

    return fetch(url, {
      ...options,
      headers,
    })
  }

  return { apiFetch }
}
