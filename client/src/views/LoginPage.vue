<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref(null)
const emailError = ref('')
const passwordError = ref('')
const showPassword = ref(false)

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

      router.push('/')
    }
  } catch (err) {
    console.error('Error checking authentication:', err)
  }
})

async function handleLogin() {
  // Reset errors
  error.value = null
  emailError.value = ''
  passwordError.value = ''

  // Validate email format
  if (!email.value) {
    emailError.value = 'Email is required'
    return
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)) {
    emailError.value = 'Please enter a valid email address'
    return
  }

  // Validate password
  if (!password.value) {
    passwordError.value = 'Password is required'
    return
  } else if (password.value.length < 8) {
    passwordError.value = 'Password must be at least 8 characters'
    return
  }

  loading.value = true

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
        credentials: 'include',
      },
    )

    const data = await response.json()
    if (response.ok) {
      router.push('/').then(() => {
        window.location.reload();
      })
    } else {
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
                <div v-if="error" class="alert alert-danger" role="alert">
                  {{ error }}
                </div>

                <!-- Email Field -->
                <div class="mb-3">
                  <label for="email" class="form-label">Email address</label>
                  <div class="input-group" :class="{ 'is-invalid': emailError }">
                    <span class="input-group-text">
                      <i class="bi bi-envelope"></i>
                    </span>
                    <input
                      type="email"
                      class="form-control"
                      :class="{ 'is-invalid': emailError }"
                      id="email"
                      v-model="email"
                      placeholder="name@example.com"
                      required
                      autocomplete="email"
                    />
                  </div>
                  <div class="invalid-feedback" v-if="emailError">{{ emailError }}</div>
                </div>

                <!-- Password Field -->
                <div class="mb-4">
                  <label for="password" class="form-label">Password</label>
                  <div class="input-group" :class="{ 'is-invalid': passwordError }">
                    <span class="input-group-text">
                      <i class="bi bi-lock"></i>
                    </span>
                    <input
                      :type="showPassword ? 'text' : 'password'"
                      class="form-control"
                      :class="{ 'is-invalid': passwordError }"
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
                  <div class="invalid-feedback" v-if="passwordError">{{ passwordError }}</div>
                </div>

                <!-- Forgot Password -->
                <div class="d-flex justify-content-end align-items-center mb-4">
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
