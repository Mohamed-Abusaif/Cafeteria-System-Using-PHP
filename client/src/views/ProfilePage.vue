<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { Modal } from 'bootstrap'

const router = useRouter()
const userData = ref({})
const loading = ref(false)
const error = ref(null)
const successMessage = ref('')

// User info form
const username = ref('')
const userInfoErrors = ref({
  name: '',
})

// Password change form
const passwordData = ref({
  currentPassword: '',
  newPassword: '',
  confirmPassword: '',
})
const passwordErrors = ref({
  currentPassword: '',
  newPassword: '',
  confirmPassword: '',
})

// Image update
const newUserImage = ref(null)
const imagePreview = ref(null)
const imageModalRef = ref(null)
let imageModalInstance = null

onMounted(async () => {
  await fetchUserData()

  // Initialize the modal after component mount
  if (imageModalRef.value) {
    imageModalInstance = new Modal(imageModalRef.value)
  }
})

async function fetchUserData() {
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
    const resData = await response.json()
    if (resData && resData.data) {
      userData.value = resData.data
      username.value = resData.data.name
      imagePreview.value = resData.data.image
    } else {
      userData.value = null
      router.push('/login')
    }
  } catch (error) {
    console.error('Error fetching user data:', error)
    error.value = 'Failed to load user data'
  } finally {
    loading.value = false
  }
}

function openImageModal() {
  if (imageModalInstance) {
    imageModalInstance.show()
  } else {
    const modalElement = document.getElementById('updateImageModal')
    if (modalElement) new Modal(modalElement).show()
  }
}

function handleImageChange(event) {
  const file = event.target.files[0]
  if (file) {
    newUserImage.value = file
    const reader = new FileReader()
    reader.onload = (e) => {
      imagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  }
}

async function updateUserImage() {
  if (!newUserImage.value) {
    return
  }

  loading.value = true
  error.value = null

  const formData = new FormData()
  formData.append('image', newUserImage.value)

  try {
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/auth/changeImage.controller.php/${userData.value.id}`,
      {
        method: 'POST',
        body: formData,
        credentials: 'include',
      },
    )

    const result = await response.json()

    if (result.statusCode === 200) {
      if (imageModalInstance) {
        imageModalInstance.hide()
      }
      successMessage.value = 'Profile image updated successfully'
      setTimeout(() => {
        successMessage.value = ''
      }, 3000)
      fetchUserData()
    } else {
      throw new Error(result.message || 'Failed to update profile image')
    }
  } catch (err) {
    error.value = `Error updating image: ${err.message}`
  } finally {
    loading.value = false
    newUserImage.value = null
  }
}

function validateUserInfo() {
  userInfoErrors.value.name = ''
  if (!username.value.trim()) {
    userInfoErrors.value.name = 'Name is required'
    return false
  }
  return true
}

async function updateUserInfo() {
  if (!validateUserInfo()) return
  loading.value = true
  error.value = null

  try {
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/user.controller.php/${userData.value.id}`,
      {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          name: username.value,
        }),
        credentials: 'include',
      },
    )

    const result = await response.json()
    if (result.statusCode === 200) {
      successMessage.value = 'Profile updated successfully'
      setTimeout(() => {
        successMessage.value = ''
      }, 3000)
      fetchUserData()
    } else {
      throw new Error(result.message || 'Failed to update profile')
    }
  } catch (err) {
    error.value = `Error updating profile: ${err.message}`
  } finally {
    loading.value = false
  }
}

function validatePasswordChange() {
  passwordErrors.value = {
    currentPassword: '',
    newPassword: '',
    confirmPassword: '',
  }

  let isValid = true
  if (!passwordData.value.currentPassword) {
    passwordErrors.value.currentPassword = 'Current password is required'
    isValid = false
  } else if (passwordData.value.currentPassword.length < 8) {
    passwordErrors.value.currentPassword = 'Password must be at least 8 characters'
    isValid = false
  }

  if (!passwordData.value.newPassword) {
    passwordErrors.value.newPassword = 'New password is required'
    isValid = false
  } else if (passwordData.value.newPassword.length < 8) {
    passwordErrors.value.newPassword = 'Password must be at least 8 characters'
    isValid = false
  }

  if (!passwordData.value.confirmPassword) {
    passwordErrors.value.confirmPassword = 'Please confirm your new password'
    isValid = false
  } else if (passwordData.value.newPassword !== passwordData.value.confirmPassword) {
    passwordErrors.value.confirmPassword = 'Passwords do not match'
    isValid = false
  }

  return isValid
}

async function changePassword() {
  if (!validatePasswordChange()) return
  loading.value = true
  error.value = null

  try {
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/auth/changePassword.controller.php/${userData.value.id}`,
      {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          current_password: passwordData.value.currentPassword,
          new_password: passwordData.value.newPassword,
          confirm_password: passwordData.value.confirmPassword,
        }),
        credentials: 'include',
      },
    )

    const result = await response.json()
    if (result.statusCode === 200) {
      successMessage.value = 'Password changed successfully'
      passwordData.value = {
        currentPassword: '',
        newPassword: '',
        confirmPassword: '',
      }
      setTimeout(() => {
        successMessage.value = ''
      }, 3000)
    } else {
      throw new Error(result.message || 'Failed to change password')
    }
  } catch (err) {
    error.value = `Error changing password: ${err.message}`
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="profile-container my-5">
    <div class="container">
      <!-- Loading spinner -->
      <div v-if="loading" class="text-center my-5">
        <div class="spinner-border text-primary" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>

      <!-- Error alert -->
      <div v-if="error" class="alert alert-danger" role="alert">
        {{ error }}
      </div>

      <!-- Success message -->
      <div
        v-if="successMessage"
        class="alert alert-success alert-dismissible fade show"
        role="alert"
      >
        {{ successMessage }}
        <button
          type="button"
          class="btn-close"
          @click="successMessage = ''"
          aria-label="Close"
        ></button>
      </div>

      <div v-if="!loading && userData.id" class="row">
        <div class="col-md-6 mb-4">
          <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
              <h4 class="mb-0">Profile Information</h4>
            </div>
            <div class="card-body">
              <!-- Profile Image -->
              <div class="text-center mb-4 position-relative">
                <div class="profile-image-container mx-auto" @click="openImageModal">
                  <img
                    :src="imagePreview || 'https://via.placeholder.com/150'"
                    alt="Profile image"
                    class="rounded-circle profile-image"
                  />
                  <div class="image-overlay rounded-circle">
                    <i class="bi bi-camera fs-3"></i>
                  </div>
                </div>
                <small class="text-muted d-block mt-2">Click on the image to change</small>
              </div>

              <form @submit.prevent="updateUserInfo">
                <!-- Name -->
                <div class="mb-3">
                  <label for="username" class="form-label">Name</label>
                  <input
                    type="text"
                    class="form-control"
                    :class="{ 'is-invalid': userInfoErrors.name }"
                    id="username"
                    v-model="username"
                  />
                  <div class="invalid-feedback" v-if="userInfoErrors.name">
                    {{ userInfoErrors.name }}
                  </div>
                </div>

                <!-- Email (read-only) -->
                <div class="mb-3">
                  <label for="email" class="form-label">Email</label>
                  <input
                    type="email"
                    class="form-control"
                    id="email"
                    :value="userData.email"
                    readonly
                  />
                  <small class="text-muted">Email cannot be changed</small>
                </div>

                <!-- Room (read-only) -->
                <div class="mb-3">
                  <label for="room" class="form-label">Room</label>
                  <input
                    type="text"
                    class="form-control"
                    id="room"
                    :value="userData.room?.name || 'Not assigned'"
                    readonly
                  />
                </div>

                <!-- Role (read-only) -->
                <div class="mb-3">
                  <label for="role" class="form-label">Role</label>
                  <input
                    type="text"
                    class="form-control"
                    id="role"
                    :value="userData.role"
                    readonly
                  />
                </div>

                <button type="submit" class="btn btn-primary w-100" :disabled="loading">
                  <span
                    v-if="loading"
                    class="spinner-border spinner-border-sm me-2"
                    role="status"
                    aria-hidden="true"
                  ></span>
                  Update Profile
                </button>
              </form>
            </div>
          </div>
        </div>

        <div class="col-md-6">
          <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
              <h4 class="mb-0">Change Password</h4>
            </div>
            <div class="card-body">
              <form @submit.prevent="changePassword">
                <!-- Current Password -->
                <div class="mb-3">
                  <label for="currentPassword" class="form-label">Current Password</label>
                  <input
                    type="password"
                    class="form-control"
                    :class="{ 'is-invalid': passwordErrors.currentPassword }"
                    id="currentPassword"
                    v-model="passwordData.currentPassword"
                  />
                  <div class="invalid-feedback" v-if="passwordErrors.currentPassword">
                    {{ passwordErrors.currentPassword }}
                  </div>
                </div>

                <!-- New Password -->
                <div class="mb-3">
                  <label for="newPassword" class="form-label">New Password</label>
                  <input
                    type="password"
                    class="form-control"
                    :class="{ 'is-invalid': passwordErrors.newPassword }"
                    id="newPassword"
                    v-model="passwordData.newPassword"
                  />
                  <div class="invalid-feedback" v-if="passwordErrors.newPassword">
                    {{ passwordErrors.newPassword }}
                  </div>
                  <small class="text-muted">Password must be at least 8 characters</small>
                </div>

                <!-- Confirm Password -->
                <div class="mb-3">
                  <label for="confirmPassword" class="form-label">Confirm New Password</label>
                  <input
                    type="password"
                    class="form-control"
                    :class="{ 'is-invalid': passwordErrors.confirmPassword }"
                    id="confirmPassword"
                    v-model="passwordData.confirmPassword"
                  />
                  <div class="invalid-feedback" v-if="passwordErrors.confirmPassword">
                    {{ passwordErrors.confirmPassword }}
                  </div>
                </div>

                <button type="submit" class="btn btn-primary w-100" :disabled="loading">
                  <span
                    v-if="loading"
                    class="spinner-border spinner-border-sm me-2"
                    role="status"
                    aria-hidden="true"
                  ></span>
                  Change Password
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Image Update Modal -->
    <div
      class="modal fade"
      id="updateImageModal"
      tabindex="-1"
      aria-labelledby="updateImageModalLabel"
      aria-hidden="true"
      ref="imageModalRef"
    >
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="updateImageModalLabel">Update Profile Image</h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="updateUserImage">
              <div class="mb-3">
                <label for="newImage" class="form-label">New Image</label>
                <input
                  type="file"
                  class="form-control"
                  id="newImage"
                  accept="image/*"
                  @change="handleImageChange"
                  required
                />
              </div>

              <div v-if="imagePreview" class="mb-3 text-center">
                <p class="mb-1">Image Preview:</p>
                <img
                  :src="imagePreview"
                  alt="Profile preview"
                  class="img-thumbnail"
                  style="max-height: 200px"
                />
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button
              type="button"
              class="btn btn-primary"
              @click="updateUserImage"
              :disabled="loading"
            >
              <span
                v-if="loading"
                class="spinner-border spinner-border-sm me-2"
                role="status"
                aria-hidden="true"
              ></span>
              Update Image
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.profile-container {
  min-height: 100vh;
}

.profile-image-container {
  position: relative;
  width: 150px;
  height: 150px;
  cursor: pointer;
}

.profile-image {
  width: 150px;
  height: 150px;
  object-fit: cover;
  border: 3px solid #f8f9fa;
}

.image-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s ease;
}

.image-overlay i {
  color: white;
}

.profile-image-container:hover .image-overlay {
  opacity: 1;
}

.card {
  border-radius: 10px;
  overflow: hidden;
}

.card-header {
  background-color: #0d6efd;
}

.btn-primary {
  background-color: #0d6efd;
  border-color: #0d6efd;
}

.btn-primary:hover {
  background-color: #0b5ed7;
  border-color: #0a58ca;
}
</style>
