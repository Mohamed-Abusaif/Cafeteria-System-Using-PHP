<script setup>
import { ref, computed, onMounted, nextTick, watch } from 'vue'
import { Modal } from 'bootstrap'

const users = ref([])
const rooms = ref([])
const totalPages = ref(1)
const currentPage = ref(1)
const searchName = ref('')
const selectedRole = ref('All')
const isLoading = ref(true)
const error = ref(null)

const formUserName = ref('')
const formUserEmail = ref('')
const formUserPassword = ref('')
const formUserConfirmPassword = ref('')
const formUserRoomId = ref('')
const formUserGender = ref('')
const formUserRole = ref('user')
const addEditModalRef = ref(null)
let addEditModalInstance = null
const isEditing = ref(false)
const editingUserId = ref(null)

const deleteModalRef = ref(null)
let deleteModalInstance = null
const userToDelete = ref(null)

const errors = ref({
  name: '',
  email: '',
  password: '',
  confirmPassword: '',
  gender: '',
  role: ''
})

const modalTitle = computed(() => {
  return isEditing.value ? `Update User#${editingUserId.value}` : 'Add New User'
})

async function fetchUsers() {
  isLoading.value = true
  error.value = null
  try {
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/user.controller.php?page=${currentPage.value}&name=${searchName.value}&role=${selectedRole.value === 'All' ? '' : selectedRole.value}`,
      {
        method: 'GET',
        credentials: 'include',
      }
    )
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`)
    const data = await response.json()
    if (data && data.data) {
      users.value = data.data.data
      totalPages.value = data.data.total_pages
    } else {
      if (response.ok && data && Array.isArray(data.data)) {
        users.value = []
      } else {
        throw new Error(data.message || 'Failed to parse users data.')
      }
    }
  } catch (err) {
    error.value = err.message
    users.value = []
  } finally {
    isLoading.value = false
  }
}

async function fetchRooms() {
  try {
    const response = await fetch(`${import.meta.env.VITE_SERVER_URL}/controllers/room.controller.php`, {
      method: 'GET',
      credentials: 'include',
    })
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`)
    const data = await response.json()
    rooms.value = data.data
  } catch (err) {
    console.error('Error fetching rooms:', err)
  }
}

onMounted(async () => {
  await Promise.all([fetchUsers(), fetchRooms()])
  await nextTick()
  if (addEditModalRef.value) {
    addEditModalInstance = new Modal(addEditModalRef.value)
  }
  if (deleteModalRef.value) {
    deleteModalInstance = new Modal(deleteModalRef.value)
  }
})

function openAddModal() {
  isEditing.value = false
  editingUserId.value = null
  formUserName.value = ''
  formUserEmail.value = ''
  formUserPassword.value = ''
  formUserConfirmPassword.value = ''
  formUserRoomId.value = ''
  formUserGender.value = ''
  if (addEditModalInstance) {
    addEditModalInstance.show()
  } else {
    const modalElement = document.getElementById('addEditUserModal')
    if (modalElement) new Modal(modalElement).show()
  }
}

function openUpdateModal(user) {
  isEditing.value = true
  editingUserId.value = user.id
  formUserName.value = user.name
  formUserEmail.value = user.email
  formUserRole.value = user.role
  formUserRoomId.value = user.room_id || ''
  if (addEditModalInstance) {
    addEditModalInstance.show()
  } else {
    const modalElement = document.getElementById('addEditUserModal')
    if (modalElement) new Modal(modalElement).show()
  }
}

function openDeleteModal(user) {
  userToDelete.value = user
  if (deleteModalInstance) {
    deleteModalInstance.show()
  } else {
    const modalElement = document.getElementById('deleteConfirmationModal')
    if (modalElement) new Modal(modalElement).show()
  }
}

function validateForm() {
  errors.value = {
    name: '',
    email: '',
    password: '',
    confirmPassword: '',
    gender: '',
    role: ''
  }

  let isValid = true

  // Name validation
  if (!formUserName.value.trim()) {
    errors.value.name = 'Name is required'
    isValid = false
  }

  // Email validation
  if (!formUserEmail.value.trim()) {
    errors.value.email = 'Email is required'
    isValid = false
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formUserEmail.value)) {
    errors.value.email = 'Please enter a valid email'
    isValid = false
  }

  // Role validation
  if (!formUserRole.value) {
    errors.value.role = 'Role is required'
    isValid = false
  } else if (!['User', 'Admin'].includes(formUserRole.value)) {
    errors.value.role = 'Please select a valid role'
    isValid = false
  }

  // Only validate these fields when adding a new user
  if (!isEditing.value) {
    if (!formUserPassword.value) {
      errors.value.password = 'Password is required'
      isValid = false
    } else if (formUserPassword.value.length < 8) {
      errors.value.password = 'Password must be at least 8 characters'
      isValid = false
    }
    if (!formUserConfirmPassword.value) {
      errors.value.confirmPassword = 'Please confirm your password'
      isValid = false
    } else if (formUserPassword.value !== formUserConfirmPassword.value) {
      errors.value.confirmPassword = 'Passwords do not match'
      isValid = false
    }
    if (!formUserGender.value) {
      errors.value.gender = 'Gender is required'
      isValid = false
    }
    if (!formUserRole.value) {
      errors.value.role = 'Role is required'
      isValid = false
    }
  }

  return isValid
}

async function saveOrUpdateUser() {
  if (!validateForm()) {
    return
  }

  let url = `${import.meta.env.VITE_SERVER_URL}/controllers/user.controller.php`
  let method = 'POST'
  const userData = {
    name: formUserName.value,
    email: formUserEmail.value,
    password: formUserPassword.value,
    confirm_password: formUserConfirmPassword.value,
    room_id: formUserRoomId.value || null,
    gender: formUserGender.value,
    role: formUserRole.value
  }

  if (isEditing.value && editingUserId.value) {
    method = 'PATCH'
    url += `/${editingUserId.value}`
  }

  try {
    const response = await fetch(url, {
      method: method,
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(userData)
    })
    const result = await response.json()

    if (result && (result.statusCode === 200 || result.statusCode === 201)) {
      if (addEditModalInstance) {
        addEditModalInstance.hide()
      }
      await fetchUsers()
    } else {
      throw new Error(result.message || `Failed to ${isEditing.value ? 'update' : 'save'} user.`)
    }
  } catch (err) {
    alert(`Error ${isEditing.value ? 'updating' : 'saving'} user: ${err.message}`)
  }
}

async function confirmDelete() {
  if (!userToDelete.value) return

  try {
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/user.controller.php/${userToDelete.value.id}`,
      {
        method: 'DELETE',
        credentials: 'include',
      },
    )

    const result = await response.json()
    if (result && result.statusCode === 200) {
      if (deleteModalInstance) {
        deleteModalInstance.hide()
      }
      await fetchUsers()
    } else {
      throw new Error(result.message || 'Failed to delete user.')
    }
  } catch (err) {
    alert(`Error deleting user: ${err.message}`)
  } finally {
    userToDelete.value = null
  }
}

// Add watch for search and role changes
watch([searchName, selectedRole], () => {
  currentPage.value = 1
  fetchUsers()
})

// Add watch for page changes
watch(currentPage, () => {
  fetchUsers()
})
</script>

<template>
  <div class="full-width-container mt-2">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-3 px-3">
      <h2 class="mb-0">Users Details</h2>
      <button type="button" class="btn btn-primary" @click="openAddModal">
        <i class="bi bi-plus-circle me-1"></i> Add User
      </button>
    </div>

    <!-- Search and Filter Section -->
    <div class="row mb-3 px-3 align-items-center">
      <div class="col-md-6">
        <div class="input-group">
          <input
            type="text"
            class="form-control"
            placeholder="Search by name..."
            v-model="searchName"
          />
          <span class="input-group-text">
            <i class="bi bi-search"></i>
          </span>
        </div>
      </div>

      <div class="col-md-6 d-flex justify-content-end">
        <div class="form-check form-check-inline me-3">
          <input
            class="form-check-input"
            type="radio"
            name="roleFilter"
            id="roleAll"
            value="All"
            v-model="selectedRole"
          />
          <label class="form-check-label" for="roleAll">All</label>
        </div>
        <div class="form-check form-check-inline me-3">
          <input
            class="form-check-input"
            type="radio"
            name="roleFilter"
            id="roleUser"
            value="User"
            v-model="selectedRole"
          />
          <label class="form-check-label" for="roleUser">User</label>
        </div>
        <div class="form-check form-check-inline">
          <input
            class="form-check-input"
            type="radio"
            name="roleFilter"
            id="roleAdmin"
            value="Admin"
            v-model="selectedRole"
          />
          <label class="form-check-label" for="roleAdmin">Admin</label>
        </div>
      </div>
    </div>

    <!-- Loading and Error Display -->
    <div v-if="isLoading" class="text-center p-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>
    <div v-else-if="error" class="alert alert-danger mx-3" role="alert">
      Failed to load users: {{ error }}
    </div>

    <!-- Table Section -->
    <div v-else class="full-width-table px-3">
      <div v-if="!users || users.length === 0" class="alert alert-info mx-3">No users found.</div>
      <table v-else class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th scope="col" style="width: 10%">Image</th>
            <th scope="col" style="width: 30%">Name</th>
            <th scope="col" style="width: 40%">E-mail</th>
            <th scope="col" style="width: 20%">Role</th>
            <th scope="col" style="width: 20%">Room</th>
            <th scope="col" style="width: 20%">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="user in users" :key="user.id">
            <th scope="row">
              <img :src="user.image" alt="User Image" class="img-fluid rounded-circle" style="width: 50px; height: 50px;">
            </th>
            <td>{{ user.name }}</td>
            <td>{{ user.email }}</td>
            <td>{{ user.role }}</td>
            <td>{{ user.room ? user.room.name : 'N/A' }}</td>
            <td>
              <div class="btn-group">
                <button
                  type="button"
                  class="btn btn-sm btn-warning me-1"
                  @click="openUpdateModal(user)"
                >
                  <i class="bi bi-pencil-square"></i> Update
                </button>
                <button type="button" class="btn btn-sm btn-danger" @click="openDeleteModal(user)">
                  <i class="bi bi-trash"></i> Delete
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="totalPages > 0" class="d-flex justify-content-center mt-3">
      <nav aria-label="Page navigation">
        <ul class="pagination">
          <li class="page-item" :class="{ disabled: currentPage === 1 }">
            <a class="page-link" href="#" @click.prevent="currentPage--">Previous</a>
          </li>
          <li
            v-for="page in totalPages"
            :key="page"
            class="page-item"
            :class="{ active: currentPage === page }"
          >
            <a class="page-link" href="#" @click.prevent="currentPage = page">{{ page }}</a>
          </li>
          <li class="page-item" :class="{ disabled: currentPage === totalPages }">
            <a class="page-link" href="#" @click.prevent="currentPage++">Next</a>
          </li>
        </ul>
      </nav>
    </div>

    <!-- Add/Edit User Modal -->
    <div
      class="modal fade"
      id="addEditUserModal"
      tabindex="-1"
      aria-labelledby="addEditUserModalLabel"
      aria-hidden="true"
      ref="addEditModalRef"
    >
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addEditUserModalLabel">{{ modalTitle }}</h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveOrUpdateUser">
              <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': errors.name }"
                  id="name"
                  v-model="formUserName"
                  :disabled="isEditing"
                />
                <div class="invalid-feedback">{{ errors.name }}</div>
              </div>
              <div class="mb-3">
                <label for="email" class="form-label">E-mail</label>
                <input
                  type="email"
                  class="form-control"
                  :class="{ 'is-invalid': errors.email }"
                  id="email"
                  v-model="formUserEmail"
                  :disabled="isEditing"
                />
                <div class="invalid-feedback">{{ errors.email }}</div>
              </div>
              <template v-if="!isEditing">
                <div class="mb-3">
                  <label for="password" class="form-label">Password</label>
                  <input
                    type="password"
                    class="form-control"
                    :class="{ 'is-invalid': errors.password }"
                    id="password"
                    v-model="formUserPassword"
                  />
                  <div class="invalid-feedback">{{ errors.password }}</div>
                </div>
                <div class="mb-3">
                  <label for="confirm_password" class="form-label">Confirm Password</label>
                  <input
                    type="password"
                    class="form-control"
                    :class="{ 'is-invalid': errors.confirmPassword }"
                    id="confirm_password"
                    v-model="formUserConfirmPassword"
                  />
                  <div class="invalid-feedback">{{ errors.confirmPassword }}</div>
                </div>
                <div class="mb-3">
                  <label for="gender" class="form-label">Gender</label>
                  <select
                    class="form-select"
                    :class="{ 'is-invalid': errors.gender }"
                    id="gender"
                    v-model="formUserGender"
                  >
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                  </select>
                  <div class="invalid-feedback">{{ errors.gender }}</div>
                </div>
              </template>
              <div class="mb-3">
                <label for="role" class="form-label">Role</label>
                <select
                  class="form-select"
                  :class="{ 'is-invalid': errors.role }"
                  id="role"
                  v-model="formUserRole"
                >
                  <option value="User">User</option>
                  <option value="Admin">Admin</option>
                </select>
                <div class="invalid-feedback">{{ errors.role }}</div>
              </div>
              <div class="mb-3">
                <label for="room_id" class="form-label">Room</label>
                <select class="form-select" id="room_id" v-model="formUserRoomId">
                  <option value="">N/A</option>
                  <option v-for="room in rooms" :key="room.id" :value="room.id">
                    {{ room.name }}
                  </option>
                </select>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button><button type="button" class="btn btn-primary" @click="saveOrUpdateUser">Save Changes</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div
      class="modal fade"
      id="deleteConfirmationModal"
      tabindex="-1"
      aria-labelledby="deleteConfirmationModalLabel"
      aria-hidden="true"
      ref="deleteModalRef"
    >
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="deleteConfirmationModalLabel">Confirm Delete</h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body">
            Are you sure you want to delete user "{{ userToDelete?.email }}"?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger" @click="confirmDelete">Delete</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.full-width-container {
  width: 90%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem;
}

.full-width-table {
  width: 100%;
  overflow-x: auto;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  border-radius: 8px;
  background-color: white;
  margin: 0 auto;
}

table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}

thead {
  background-color: #f8f9fa;
}

th,
td {
  padding: 12px 15px;
}

.table-hover tbody tr:hover {
  background-color: rgba(0, 123, 255, 0.05);
}

.pagination {
  margin-bottom: 0;
}
</style>
