<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const showDropdown = ref(false)
const userData = ref({})
const loading = ref(false)
const error = ref(null)

const toggleDropdown = () => {
  showDropdown.value = !showDropdown.value
}

onMounted(async () => {
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
    const resData = await response.json()
    if (resData && resData.data) {
      userData.value = resData.data
    } else {
      userData.value = null
    }
  } catch (error) {
    console.error('Error fetching user data:', error)
  }
})

async function handleLogout() {
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
      userData.value = {}
      router.push('/').then(() => {
        window.location.reload()
      })
    } else {
      const data = await response.json()
      error.value = data.message || 'Failed to logout. Please try again.'
      console.error('Logout failed:', data.message)
    }
  } catch (err) {
    error.value = 'Network error. Please try again.'
    console.error('Logout error:', err)
  } finally {
    loading.value = false
    showDropdown.value = false
  }
}
</script>

<template>
  <header class="navbar navbar-expand-lg navbar-light bg-light shadow-sm fixed-top">
    <div class="container-fluid">
      <router-link to="/" class="navbar-brand d-flex align-items-center">
        <i class="bi bi-cup-hot"></i>
        <span class="fw-bold ms-2">Cafeteria</span>
      </router-link>

      <div class="ms-auto dropdown">
        <button
          class="btn btn-outline-secondary dropdown-toggle"
          type="button"
          id="userDropdown"
          @click="toggleDropdown"
          aria-expanded="false"
        >
          <i class="bi bi-person"></i>
        </button>

        <ul
          class="dropdown-menu dropdown-menu-end"
          :class="{ show: showDropdown }"
          aria-labelledby="userDropdown"
          style="position: absolute; right: 0; left: auto"
        >
          <template v-if="!userData.id">
            <li>
              <router-link
                class="dropdown-item d-flex justify-content-between align-items-center"
                to="/login"
              >
                <span>Login</span>
                <i class="bi bi-box-arrow-in-right"></i>
              </router-link>
            </li>
          </template>

          <template v-else-if="userData.role === 'Admin'">
            <li>
              <router-link
                class="dropdown-item d-flex justify-content-between align-items-center"
                to="/profile"
              >
                <span>Profile</span>
                <i class="bi bi-person-bounding-box"></i>
              </router-link>
            </li>
            <li>
              <router-link
                class="dropdown-item d-flex justify-content-between align-items-center"
                to="/dashboard"
              >
                <span>Dashboard</span>
                <i class="bi bi-graph-up"></i>
              </router-link>
            </li>
            <li><hr class="dropdown-divider" /></li>
            <li>
              <a
                href="#"
                @click.prevent="handleLogout"
                class="dropdown-item text-danger d-flex justify-content-between align-items-center"
              >
                <span>Logout</span>
                <i class="bi bi-box-arrow-right"></i>
              </a>
            </li>
          </template>

          <template v-else-if="userData.role === 'User'">
            <li>
              <router-link
                class="dropdown-item d-flex justify-content-between align-items-center"
                to="/profile"
              >
                <span>Profile</span>
                <i class="bi bi-person-bounding-box"></i>
              </router-link>
            </li>
            <li>
              <router-link
                class="dropdown-item d-flex justify-content-between align-items-center"
                to="/cart"
              >
                <span>Cart</span>
                <i class="bi bi-cart-dash"></i>
              </router-link>
            </li>
            <li>
              <router-link
                class="dropdown-item d-flex justify-content-between align-items-center"
                to="/"
              >
                <span>Orders</span>
                <i class="bi bi-ui-checks"></i>
              </router-link>
            </li>
            <li><hr class="dropdown-divider" /></li>
            <li>
              <a
                href="#"
                @click.prevent="handleLogout"
                class="dropdown-item text-danger d-flex justify-content-between align-items-center"
              >
                <span>Logout</span>
                <i class="bi bi-box-arrow-right"></i>
              </a>
            </li>
          </template>
        </ul>
      </div>
    </div>
  </header>
</template>

<style scoped>
body {
  padding-top: 56px;
}
.dropdown-menu {
  margin-top: 0.5rem;
}
</style>
