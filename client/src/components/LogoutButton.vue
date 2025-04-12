<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const props = defineProps({
  buttonClass: {
    type: String,
    default: 'btn btn-outline-danger',
  },
  buttonText: {
    type: String,
    default: 'Logout',
  },
  showIcon: {
    type: Boolean,
    default: true,
  },
  redirectTo: {
    type: String,
    default: '/login',
  },
})

const router = useRouter()
const loading = ref(false)
const error = ref(null)

async function handleLogout() {
  loading.value = true
  error.value = null

  try {
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/auth/logout.controller.php`,
      {
        method: 'POST',
        credentials: 'include', // Include cookies
      },
    )

    if (response.ok) {
      // Redirect to login page after successful logout
      router.push(props.redirectTo)
    } else {
      // Handle logout failure
      const data = await response.json()
      error.value = data.message || 'Failed to logout. Please try again.'
      console.error('Logout failed:', data.message)
    }
  } catch (err) {
    error.value = 'Network error. Please try again.'
    console.error('Logout error:', err)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <button @click="handleLogout" :class="buttonClass" :disabled="loading" type="button">
    <span
      v-if="loading"
      class="spinner-border spinner-border-sm me-1"
      role="status"
      aria-hidden="true"
    ></span>
    <i v-if="showIcon && !loading" class="bi bi-box-arrow-right me-1"></i>
    {{ loading ? 'Logging out...' : buttonText }}
  </button>

  <div v-if="error" class="mt-2 text-danger small">
    {{ error }}
  </div>
</template>
