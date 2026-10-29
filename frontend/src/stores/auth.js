import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null)
  const token = ref(null)

  const isAuthenticated = computed(() => !!user.value && !!token.value)

  const isAdmin = computed(() => user.value?.role === 'admin')

  let cartStore = null
  const setCartStore = (store) => {
    cartStore = store
  }

  const login = async (credentials) => {
    try {
      const response = await fetch('/api/clients/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          email: credentials.email,
          mot_de_passe: credentials.password
        })
      })

      if (response.ok) {
        const userData = await response.json()
        user.value = userData
        token.value = userData.token

        localStorage.setItem('user', JSON.stringify(user.value))
        localStorage.setItem('token', token.value)

        if (cartStore) {
          cartStore.syncCartOnLogin()
        }

        return { success: true }
      } else {
        const errorData = await response.json()
        throw new Error(errorData.error || 'Identifiants incorrects')
      }
    } catch (error) {
      return { success: false, error: error.message }
    }
  }

  const register = async (userData) => {
    try {
      const response = await fetch('/api/clients', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          nom: userData.name,
          email: userData.email,
          mot_de_passe: userData.password,
          type_client: userData.clientType || 'particulier',
          adresse: userData.address || '',
          code_postal: userData.postalCode || '',
          ville: userData.city || '',
          telephone: userData.phone || ''
        })
      })

      if (response.ok) {
        const newUser = await response.json()
        user.value = newUser
        token.value = newUser.token

        localStorage.setItem('user', JSON.stringify(user.value))
        localStorage.setItem('token', token.value)

        if (cartStore) {
          cartStore.syncCartOnLogin()
        }

        return { success: true }
      } else {
        const errorData = await response.json()
        throw new Error(errorData.error || 'Erreur lors de l\'inscription')
      }
    } catch (error) {
      return { success: false, error: error.message }
    }
  }

  const logout = () => {
    if (cartStore) {
      cartStore.syncCartOnLogout()
    }

    user.value = null
    token.value = null
    localStorage.removeItem('user')
    localStorage.removeItem('token')
  }

  const loadUser = () => {
    const savedUser = localStorage.getItem('user')
    const savedToken = localStorage.getItem('token')

    if (savedUser && savedToken) {
      user.value = JSON.parse(savedUser)
      token.value = savedToken
    }
  }

  return {
    user,
    token,
    isAuthenticated,
    isAdmin,
    login,
    register,
    logout,
    loadUser,
    setCartStore
  }
})
