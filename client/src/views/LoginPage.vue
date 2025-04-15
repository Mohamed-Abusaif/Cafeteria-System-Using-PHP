<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Modal } from 'bootstrap'

const router = useRouter()
const email = ref('')
const password = ref('')
const loading = ref(false)
const error = ref(null)
const emailError = ref('')
const passwordError = ref('')
const showPassword = ref(false)

// Forgot password modal
const forgotEmail = ref('')
const forgotEmailError = ref('')
const forgotPasswordLoading = ref(false)
const forgotPasswordSuccess = ref(false)
const forgotPasswordError = ref(null)
const forgotPasswordModalRef = ref(null)
let forgotPasswordModalInstance = null

onMounted(async () => {
  try {
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/auth/login.controller.php`,
      {
        method: 'GET',
        credentials: 'include',
      },
    )

    if (response.ok) {
      router.push('/')
    }

    // Initialize modal
    if (forgotPasswordModalRef.value) {
      forgotPasswordModalInstance = new Modal(forgotPasswordModalRef.value)
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
        window.location.reload()
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

function openForgotPasswordModal() {
  forgotEmail.value = ''
  forgotEmailError.value = ''
  forgotPasswordSuccess.value = false
  forgotPasswordError.value = null

  if (forgotPasswordModalInstance) {
    forgotPasswordModalInstance.show()
  } else {
    const modalElement = document.getElementById('forgotPasswordModal')
    if (modalElement) new Modal(modalElement).show()
  }
}

function validateForgotEmail() {
  forgotEmailError.value = ''

  if (!forgotEmail.value) {
    forgotEmailError.value = 'Email is required'
    return false
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(forgotEmail.value)) {
    forgotEmailError.value = 'Please enter a valid email address'
    return false
  }

  return true
}

async function sendResetLink() {
  if (!validateForgotEmail()) return

  forgotPasswordLoading.value = true
  forgotPasswordError.value = null

  try {
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/auth/forgetPassword.controller.php`,
      {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          email: forgotEmail.value,
        }),
      },
    )

    const data = await response.json()
    if (response.ok) {
      forgotPasswordSuccess.value = true
    } else {
      forgotPasswordError.value = data.message || 'Failed to send reset link. Please try again.'
    }
  } catch (err) {
    forgotPasswordError.value = 'Network error. Please try again.'
    console.error('Forgot password error:', err)
  } finally {
    forgotPasswordLoading.value = false
  }
}
</script>

<template>
  <div class="login-page page-content">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
          <div class="card login-card">
            <div class="card-body p-4 p-lg-5">
              <div class="text-center mb-4">
                <div class="logo-container mx-auto mb-3">
                  <i class="bi bi-cup-hot-fill"></i>
                </div>
                <h2 class="card-title">Welcome Back</h2>
                <p class="text-muted">Sign in to your account</p>
              </div>

              <div v-if="error" class="alert alert-danger" role="alert">
                {{ error }}
              </div>

              <form @submit.prevent="handleLogin">
                <div class="mb-3">
                  <label for="email" class="form-label">Email Address</label>
                  <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input
                      type="email"
                      class="form-control"
                      id="email"
                      v-model="email"
                      required
                      placeholder="Enter your email"
                      :disabled="loading"
                    />
                  </div>
                </div>

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

                <div class="d-flex justify-content-end align-items-center mb-4">
                  <a
                    href="#"
                    class="text-primary text-decoration-none"
                    @click.prevent="openForgotPasswordModal"
                    >Forgot password?</a
                  >
                </div>

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

  <!-- Forgot Password Modal -->
  <div
    class="modal fade"
    id="forgotPasswordModal"
    tabindex="-1"
    aria-labelledby="forgotPasswordModalLabel"
    aria-hidden="true"
    ref="forgotPasswordModalRef"
  >
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="forgotPasswordModalLabel">Reset Password</h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
          ></button>
        </div>
        <div class="modal-body">
          <div v-if="forgotPasswordSuccess" class="alert alert-success">
            Password reset link has been sent to your email. Please check your inbox.
          </div>

          <div v-else>
            <div v-if="forgotPasswordError" class="alert alert-danger">
              {{ forgotPasswordError }}
            </div>

            <div class="mb-3">
              <label for="forgotEmail" class="form-label">Email address</label>
              <input
                type="email"
                class="form-control"
                :class="{ 'is-invalid': forgotEmailError }"
                id="forgotEmail"
                v-model="forgotEmail"
                placeholder="name@example.com"
              />
              <div class="invalid-feedback" v-if="forgotEmailError">
                {{ forgotEmailError }}
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          <button
            v-if="!forgotPasswordSuccess"
            type="button"
            class="btn btn-primary"
            @click="sendResetLink"
            :disabled="forgotPasswordLoading"
          >
            <span
              v-if="forgotPasswordLoading"
              class="spinner-border spinner-border-sm me-2"
              role="status"
              aria-hidden="true"
            ></span>
            {{ forgotPasswordLoading ? 'Sending...' : 'Send Reset Link' }}
          </button>
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
