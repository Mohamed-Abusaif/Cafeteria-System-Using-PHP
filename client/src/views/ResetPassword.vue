<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()
const token = route.query.token

const password = ref('')
const confirmPassword = ref('')
const errors = ref({
  password: '',
  confirmPassword: '',
  general: ''
})
const resetPasswordLoading = ref(false)

const validateForm = () => {
  errors.value = { password: '', confirmPassword: '', general: '' }
  let isValid = true

  if (!password.value) {
    errors.value.password = 'Password is required'
    isValid = false
  } else if (password.value.length < 8) {
    errors.value.password = 'Password must be at least 8 characters'
    isValid = false
  }

  if (!confirmPassword.value) {
    errors.value.confirmPassword = 'Please confirm your password'
    isValid = false
  } else if (password.value !== confirmPassword.value) {
    errors.value.confirmPassword = 'Passwords do not match'
    isValid = false
  }

  return isValid
}

const resetPassword = async () => {
  if (!validateForm()) return
  resetPasswordLoading.value = true

  try {
    const response = await fetch(`${import.meta.env.VITE_SERVER_URL}/controllers/auth/forgetPassword.controller.php`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        reset_token: token,
        password: password.value,
        confirm_password: confirmPassword.value,
      }),
    })

    if (response.ok) {
      router.push({ path: '/login', query: { resetSuccess: 'true' } })
    } else {
      const data = await response.json()
      if (data.error) {
        errors.value.general = data.error
      }
    }
  } catch (error) {
    console.error('Error resetting password:', error)
    errors.value.general = 'Failed to reset password. Please try again.'
  } finally {
    resetPasswordLoading.value = false
  }
}

onMounted(() => {
  if (!token) {
    router.push('/login')
  }
})
</script>

<template>
  <div class="reset-password-page">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
          <div class="reset-password-card card border-0 shadow-lg mt-5">
            <div class="card-body p-5">
              <h2 class="text-center mb-4">Reset Password</h2>

              <div v-if="errors.general" class="alert alert-danger" role="alert">
                {{ errors.general }}
              </div>

              <form @submit.prevent="resetPassword">
                <div class="mb-3">
                  <label for="password" class="form-label">New Password</label>
                  <input
                    type="password"
                    class="form-control"
                    :class="{ 'is-invalid': errors.password }"
                    id="password"
                    v-model="password"
                    required
                  >
                  <div class="invalid-feedback" v-if="errors.password">
                    {{ errors.password }}
                  </div>
                </div>

                <div class="mb-3">
                  <label for="confirmPassword" class="form-label">Confirm Password</label>
                  <input
                    type="password"
                    class="form-control"
                    :class="{ 'is-invalid': errors.confirmPassword }"
                    id="confirmPassword"
                    v-model="confirmPassword"
                    required
                  >
                  <div class="invalid-feedback" v-if="errors.confirmPassword">
                    {{ errors.confirmPassword }}
                  </div>
                </div>

                <button type="submit" class="btn btn-primary w-100" :disabled="resetPasswordLoading">
                  {{ resetPasswordLoading ? 'Resetting...' : 'Reset Password' }}
                </button>

                <div class="text-center mt-3">
                  <a href="/login" class="text-decoration-none">Back to Login</a>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.reset-password-card {
  border-radius: 10px;
}

.btn-primary {
  padding: 10px;
  border-radius: 5px;
  transition: all 0.3s;
}

.btn-primary:hover:not(:disabled) {
  transform: translateY(-2px);
}

.btn-primary:disabled {
  cursor: not-allowed;
}
</style>
