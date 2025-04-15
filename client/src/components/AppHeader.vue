<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/authStore'

const router = useRouter()
const showDropdown = ref(false)
const userData = ref({})
const loading = ref(false)
const error = ref(null)
const authStore = useAuthStore()

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

  // Check if user is already logged in
  await authStore.checkAuth()
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

const getUserInitials = () => {
  if (!authStore.user || !authStore.user.name) return '?'
  return authStore.user.name
    .split(' ')
    .map((n) => n[0])
    .join('')
    .toUpperCase()
    .substring(0, 2)
}
</script>

<template>
  <header class="app-header">
    <div class="container-fluid">
      <div class="d-flex justify-content-between align-items-center">
        <router-link to="/" class="brand-link">
          <div class="d-flex align-items-center">
            <div class="logo-container">
              <i class="bi bi-cup-hot-fill"></i>
            </div>
            <h1 class="brand-name">Cafeteria System</h1>
          </div>
        </router-link>

        <div class="dropdown">
          <button
            class="btn dropdown-toggle user-menu-btn"
            type="button"
            id="userMenu"
            data-bs-toggle="dropdown"
            aria-expanded="false"
          >
            <div class="user-avatar" v-if="authStore.user">
              {{ getUserInitials() }}
            </div>
            <i class="bi bi-person-circle me-1" v-else></i>
            <span class="user-name">{{ authStore.user ? authStore.user.name : 'Menu' }}</span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
            <!-- Not logged in -->
            <li v-if="!authStore.isLoggedIn()">
              <router-link class="dropdown-item" to="/login">
                <i class="bi bi-box-arrow-in-right me-2"></i>Login
              </router-link>
            </li>

            <!-- Logged in (all users) -->
            <template v-if="authStore.isLoggedIn()">
              <li>
                <router-link class="dropdown-item" to="/profile">
                  <i class="bi bi-person me-2"></i>Profile
                </router-link>
              </li>

              <!-- Normal user items -->
              <template v-if="!authStore.isAdmin()">
                <li>
                  <router-link class="dropdown-item" to="/cart">
                    <i class="bi bi-cart me-2"></i>Cart
                  </router-link>
                </li>
                <li>
                  <router-link class="dropdown-item" to="/orders">
                    <i class="bi bi-bag me-2"></i>My Orders
                  </router-link>
                </li>
              </template>

              <!-- Admin items -->
              <template v-if="authStore.isAdmin()">
                <li>
                  <router-link class="dropdown-item" to="/dashboard">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                  </router-link>
                </li>
              </template>

              <li><hr class="dropdown-divider" /></li>
              <li>
                <a
                  @click="handleLogout"
                  class="dropdown-item"
                  href="#"
                  :class="{ disabled: authStore.loading }"
                >
                  <i class="bi bi-box-arrow-right me-2"></i>
                  {{ authStore.loading ? 'Logging out...' : 'Logout' }}
                </a>
              </li>
            </template>
          </ul>
        </div>
      </div>
    </div>
  </header>
</template>

<style scoped>
.app-header {
  background-color: #ffffff;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
  padding: 1rem 1rem;
  position: sticky;
  top: 0;
  width: 100%;
  z-index: 1000;
  transition: box-shadow 0.3s ease;
}

.brand-link {
  text-decoration: none;
  color: var(--dark-color);
  transition: transform 0.3s ease;
  display: inline-block;
}

.brand-link:hover {
  transform: translateY(-2px);
}

.logo-container {
  width: 40px;
  height: 40px;
  background-color: var(--primary-color);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  margin-right: 0.75rem;
  box-shadow: 0 4px 8px rgba(13, 110, 253, 0.25);
}

.brand-name {
  font-size: 1.25rem;
  font-weight: 700;
  margin: 0;
  color: var(--dark-color);
}

.user-menu-btn {
  display: flex;
  align-items: center;
  background-color: #f8f9fa;
  border: none;
  padding: 0.5rem 1rem;
  border-radius: 50px;
  transition: all 0.2s ease;
}

.user-menu-btn:hover {
  background-color: #e9ecef;
}

.user-menu-btn:focus {
  box-shadow: none;
}

.user-avatar {
  width: 32px;
  height: 32px;
  background-color: var(--primary-color);
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 600;
  margin-right: 0.5rem;
}

.user-name {
  max-width: 150px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  font-weight: 500;
}

.dropdown-menu {
  border: none;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  border-radius: 0.5rem;
  padding: 0.5rem;
  min-width: 200px;
}

.dropdown-item {
  border-radius: 0.25rem;
  padding: 0.5rem 1rem;
  font-weight: 500;
  transition: all 0.2s ease;
}

.dropdown-item:hover {
  background-color: rgba(13, 110, 253, 0.1);
  color: var(--primary-color);
}

.dropdown-item i {
  width: 20px;
  text-align: center;
}

.dropdown-divider {
  margin: 0.3rem 0;
  opacity: 0.1;
}

@media (max-width: 768px) {
  .brand-name {
    font-size: 1rem;
  }

  .user-menu-btn {
    padding: 0.4rem 0.8rem;
  }

  .user-name {
    max-width: 100px;
  }
}
</style>
