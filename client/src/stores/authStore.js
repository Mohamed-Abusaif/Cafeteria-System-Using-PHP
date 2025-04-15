import { defineStore } from 'pinia'
import { ref } from 'vue'
import { useRouter } from 'vue-router'

export const useAuthStore = defineStore('auth', () => {
  const router = useRouter()
  const user = ref(null)
  const loading = ref(false)
  const error = ref(null)

  async function checkAuth() {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(
        `${import.meta.env.VITE_SERVER_URL}/controllers/auth/login.controller.php`,
        {
          method: 'GET',
          credentials: 'include',
          headers: {
            'Content-Type': 'application/json',
          },
        },
      )

      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`)

      const result = await response.json()
      if (result && result.data) {
        user.value = result.data
      } else {
        user.value = null
      }
    } catch (err) {
      console.error('Auth check error:', err)
      error.value = err.message
      user.value = null
    } finally {
      loading.value = false
    }

    return user.value
  }

  async function login(email, password) {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(
        `${import.meta.env.VITE_SERVER_URL}/controllers/auth/login.controller.php`,
        {
          method: 'POST',
          credentials: 'include',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ email, password }),
        },
      )

      const result = await response.json()

      if (response.ok && result.data) {
        user.value = result.data
        return { success: true, data: result.data }
      } else {
        error.value = result.message || 'Login failed'
        return { success: false, message: error.value }
      }
    } catch (err) {
      console.error('Login error:', err)
      error.value = 'Network error. Please try again.'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  async function logout() {
    loading.value = true
    error.value = null

    try {
      const response = await fetch(
        `${import.meta.env.VITE_SERVER_URL}/controllers/auth/logout.controller.php`,
        {
          method: 'POST',
          credentials: 'include',
        },
      )

      if (response.ok) {
        user.value = null
        router.push('/login')
        return { success: true }
      } else {
        const result = await response.json()
        error.value = result.message || 'Logout failed'
        return { success: false, message: error.value }
      }
    } catch (err) {
      console.error('Logout error:', err)
      error.value = 'Network error. Please try again.'
      return { success: false, message: error.value }
    } finally {
      loading.value = false
    }
  }

  function isAdmin() {
    return user.value?.role === 'Admin'
  }

  function isLoggedIn() {
    return !!user.value
  }

  return {
    user,
    loading,
    error,
    login,
    logout,
    checkAuth,
    isAdmin,
    isLoggedIn,
  }
})
