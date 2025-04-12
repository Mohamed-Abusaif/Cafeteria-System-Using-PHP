<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const email = ref('')
const password = ref('')
const rememberMe = ref(false)
const loading = ref(false)
const error = ref(null)
const showPassword = ref(false)

// Check if user is already logged in
onMounted(async () => {
  try {
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/auth/login.controller.php`,
      {
        method: 'GET',
        credentials: 'include', // Include cookies
      },
    )

    if (response.ok) {
      // User is already logged in, redirect to dashboard or home
      router.push('/dashboard')
    }
  } catch (err) {
    console.error('Error checking authentication:', err)
  }
})

async function handleLogin() {
  if (!email.value || !password.value) {
    error.value = 'Please enter both email and password'
    return
  }

  loading.value = true
  error.value = null

  try {
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/auth/login.controller.php`,
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          email: email.value,
          password: password.value,
        }),
        credentials: 'include', // Include cookies
      },
    )

    const data = await response.json()

    if (response.ok) {
      // Login successful
      router.push('/dashboard')
    } else {
      // Login failed
      error.value = data.message || 'Invalid email or password'
    }
  } catch (err) {
    error.value = 'Network error. Please try again.'
    console.error('Login error:', err)
  } finally {
    loading.value = false
  }
}

function togglePasswordVisibility() {
  showPassword.value = !showPassword.value
}
</script>

<template>
  <div class="login-page">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
          <div class="login-card card border-0 shadow-lg mt-5">
            <div class="card-body p-5">
              <div class="text-center mb-4">
                <h2 class="fw-bold">Welcome Back</h2>
                <p class="text-muted">Sign in to your account</p>
              </div>

              <form @submit.prevent="handleLogin">
                <!-- Error Alert -->
                <div v-if="error" class="alert alert-danger" role="alert">
                  {{ error }}
                </div>

                <!-- Email Field -->
                <div class="mb-3">
                  <label for="email" class="form-label">Email address</label>
                  <div class="input-group">
                    <span class="input-group-text">
                      <i class="bi bi-envelope"></i>
                    </span>
                    <input
                      type="email"
                      class="form-control"
                      id="email"
                      v-model="email"
                      placeholder="name@example.com"
                      required
                      autocomplete="email"
                    />
                  </div>
                </div>

                <!-- Password Field -->
                <div class="mb-4">
                  <label for="password" class="form-label">Password</label>
                  <div class="input-group">
                    <span class="input-group-text">
                      <i class="bi bi-lock"></i>
                    </span>
                    <input
                      :type="showPassword ? 'text' : 'password'"
                      class="form-control"
                      id="password"
                      v-model="password"
                      placeholder="Enter your password"
                      required
                      autocomplete="current-password"
                    />
                    <button
                      class="btn btn-outline-secondary"
                      type="button"
                      @click="togglePasswordVisibility"
                    >
                      <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                    </button>
                  </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                  <div class="form-check">
                    <input
                      type="checkbox"
                      class="form-check-input"
                      id="rememberMe"
                      v-model="rememberMe"
                    />
                    <label class="form-check-label" for="rememberMe">Remember me</label>
                  </div>
                  <a href="#" class="text-primary text-decoration-none">Forgot password?</a>
                </div>

                <!-- Login Button -->
                <button type="submit" class="btn btn-primary w-100 py-2" :disabled="loading">
                  <span
                    v-if="loading"
                    class="spinner-border spinner-border-sm me-2"
                    role="status"
                    aria-hidden="true"
                  ></span>
                  {{ loading ? 'Signing in...' : 'Sign In' }}
                </button>
              </form>

              <div class="mt-4 text-center">
                <p class="mb-0">
                  Don't have an account? <a href="#" class="text-primary">Sign up</a>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.login-page {
  min-height: 100vh;
  background: linear-gradient(to right, #f8f9fa 0%, #e9ecef 100%);
  display: flex;
  align-items: center;
}

.login-card {
  border-radius: 10px;
  overflow: hidden;
  transition: transform 0.3s ease;
}

.login-card:hover {
  transform: translateY(-5px);
}

.input-group-text {
  background-color: #f8f9fa;
}

.btn-primary {
  background-color: #0d6efd;
  border-color: #0d6efd;
  transition: all 0.3s ease;
}

.btn-primary:hover {
  background-color: #0b5ed7;
  border-color: #0a58ca;
  box-shadow: 0 5px 15px rgba(13, 110, 253, 0.2);
}
</style>
