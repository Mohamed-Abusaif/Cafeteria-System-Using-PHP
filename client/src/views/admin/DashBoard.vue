<script setup>
import { onMounted, ref } from 'vue'
import LogoutButton from '../../components/LogoutButton.vue'

const data = ref({})
const isLoading = ref(true)
const error = ref(null)

onMounted(async () => {
  isLoading.value = true
  error.value = null
  try {
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/dashboard.controller.php`,
    )
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`)
    const resData = await response.json()
    if (resData && resData.data) {
      data.value = resData.data
    } else {
      throw new Error(resData.message || 'Failed to load dashboard data.')
    }
  } catch (err) {
    error.value = err.message
    data.value = {}
  } finally {
    isLoading.value = false
  }
})
</script>

<template>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="mb-0">Dashboard</h2>
      <LogoutButton buttonClass="btn btn-outline-danger" />
    </div>

    <div v-if="isLoading" class="text-center py-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
      <p class="mt-2">Loading data...</p>
    </div>

    <div v-else-if="error" class="alert alert-danger text-center">
      <i class="bi bi-exclamation-triangle-fill me-2"></i>
      {{ error }}
      <button @click="$emit('retry')" class="btn btn-sm btn-outline-danger ms-3">Retry</button>
    </div>

    <div v-else class="row g-4">
      <div class="col-md-4 col-sm-6" v-for="(count, key) in data" :key="key">
        <div class="card h-100 border-light shadow-sm hover-effect">
          <div class="card-header bg-transparent border-light">
            <h5 class="card-title text-capitalize mb-0 fw-semibold text-primary">
              <i class="bi bi-grid me-2"></i>{{ key }}
            </h5>
          </div>
          <div class="card-body text-center d-flex flex-column">
            <p class="display-5 fw-bold text-dark mb-4 mt-2">{{ count }}</p>
            <div class="mt-auto">
              <router-link
                class="btn btn-outline-primary rounded-pill px-4"
                :to="`/dashboard/${key}`"
              >
                <i class="bi bi-arrow-right-circle me-2"></i>
                View {{ key }}
              </router-link>
            </div>
          </div>
          <div class="card-footer bg-transparent border-light text-muted small">
            <i class="bi bi-info-circle me-1"></i>
            Last updated: {{ new Date().toLocaleDateString() }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.hover-effect {
  transition: all 0.3s ease;
  border-radius: 10px;
}

.hover-effect:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.card {
  border-width: 2px;
}

.card-header {
  padding: 1rem 1.25rem;
}

.card-footer {
  padding: 0.75rem 1.25rem;
}

.display-5 {
  font-size: 2.5rem;
}

.btn-outline-primary {
  border-width: 2px;
}

.text-primary {
  color: #0d6efd !important;
}
</style>
