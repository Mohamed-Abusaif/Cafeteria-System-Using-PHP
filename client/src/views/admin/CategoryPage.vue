<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { Modal } from 'bootstrap'

const categories = ref([])
const isLoading = ref(true)
const error = ref(null)

const formCategoryName = ref('')
const addEditModalRef = ref(null)
let addEditModalInstance = null
const isEditing = ref(false)
const editingCategoryId = ref(null)

const deleteModalRef = ref(null)
let deleteModalInstance = null
const categoryToDelete = ref(null)

const modalTitle = computed(() => {
  return isEditing.value ? `Update Category#${editingCategoryId.value}` : 'Add New Category'
})

async function fetchCategories() {
  isLoading.value = true
  error.value = null
  try {
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/category.controller.php`,
      {
        method: 'GET',
        credentials: 'include',
      },
    )
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`)
    const data = await response.json()
    if (data && data.data) {
      categories.value = data.data
    } else {
      if (response.ok && data && Array.isArray(data.data)) {
        categories.value = []
      } else {
        throw new Error(data.message || 'Failed to parse categories data.')
      }
    }
  } catch (err) {
    error.value = err.message
    categories.value = []
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  await fetchCategories()
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
  editingCategoryId.value = null
  formCategoryName.value = ''
  if (addEditModalInstance) {
    addEditModalInstance.show()
  } else {
    const modalElement = document.getElementById('addEditCategoryModal')
    if (modalElement) new Modal(modalElement).show()
  }
}

function openUpdateModal(category) {
  isEditing.value = true
  editingCategoryId.value = category.id
  formCategoryName.value = category.name
  if (addEditModalInstance) {
    addEditModalInstance.show()
  } else {
    const modalElement = document.getElementById('addEditCategoryModal')
    if (modalElement) new Modal(modalElement).show()
  }
}

function openDeleteModal(category) {
  categoryToDelete.value = category
  if (deleteModalInstance) {
    deleteModalInstance.show()
  } else {
    const modalElement = document.getElementById('deleteConfirmationModal')
    if (modalElement) new Modal(modalElement).show()
  }
}

async function saveOrUpdateCategory() {
  if (!formCategoryName.value) {
    alert('Category name is required.')
    return
  }

  let url = `${import.meta.env.VITE_SERVER_URL}/controllers/category.controller.php`
  let method = 'POST'
  const categoryData = {
    name: formCategoryName.value,
  }

  if (isEditing.value && editingCategoryId.value) {
    method = 'PATCH'
    url += `/${editingCategoryId.value}`
  }

  try {
    const response = await fetch(url, {
      method: method,
      credentials: 'include',
      body: JSON.stringify(categoryData),
    })
    const result = await response.json()

    if (result && (result.statusCode === 200 || result.statusCode === 201)) {
      if (addEditModalInstance) {
        addEditModalInstance.hide()
      }
      await fetchCategories()
    } else {
      throw new Error(
        result.message || `Failed to ${isEditing.value ? 'update' : 'save'} category.`,
      )
    }
  } catch (err) {
    alert(`Error ${isEditing.value ? 'updating' : 'saving'} category: ${err.message}`)
  }
}

async function confirmDelete() {
  if (!categoryToDelete.value) return

  try {
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/category.controller.php/${categoryToDelete.value.id}`,
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
      await fetchCategories()
    } else {
      throw new Error(result.message || 'Failed to delete category.')
    }
  } catch (err) {
    alert(`Error deleting category: ${err.message}`)
  } finally {
    categoryToDelete.value = null
  }
}
</script>

<template>
  <div class="category-container page-content">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-3 pt-2">
      <h2 class="mb-0">Categories Details</h2>
      <!-- Button now calls openAddModal -->
      <button type="button" class="btn btn-primary" @click="openAddModal">
        <i class="bi bi-plus-circle me-1"></i> Add Category
      </button>
    </div>

    <!-- Loading and Error Display -->
    <div v-if="isLoading" class="text-center p-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>
    <div v-else-if="error" class="alert alert-danger" role="alert">
      Failed to load categories: {{ error }}
    </div>

    <!-- Table Section -->
    <div v-else class="table-responsive">
      <div v-if="!categories || categories.length === 0" class="alert alert-info">
        No categories found.
      </div>
      <table v-else class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th scope="col" style="width: 10%">#</th>
            <th scope="col" style="width: 30%">Name</th>
            <th scope="col" style="width: 20%">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="category in categories" :key="category.id">
            <th scope="row">{{ category.id }}</th>
            <td>{{ category.name }}</td>
            <td>
              <div class="btn-group">
                <button
                  type="button"
                  class="btn btn-sm btn-warning me-1"
                  @click="openUpdateModal(category)"
                >
                  <i class="bi bi-pencil-square"></i> Update
                </button>
                <button
                  type="button"
                  class="btn btn-sm btn-danger"
                  @click="openDeleteModal(category)"
                >
                  <i class="bi bi-trash"></i> Delete
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Add/Edit Category Modal -->
    <div
      class="modal fade"
      id="addEditCategoryModal"
      tabindex="-1"
      aria-labelledby="addEditCategoryModalLabel"
      aria-hidden="true"
      ref="addEditModalRef"
    >
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addEditCategoryModalLabel">{{ modalTitle }}</h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveOrUpdateCategory">
              <div class="mb-3">
                <label for="categoryName" class="form-label">Category Name</label>
                <input
                  type="text"
                  class="form-control"
                  id="categoryName"
                  v-model="formCategoryName"
                  required
                />
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" @click="saveOrUpdateCategory">
              Save Changes
            </button>
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
            Are you sure you want to delete category "{{ categoryToDelete?.name }}"?
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
.category-container {
  width: 100%;
  max-width: 100%;
  padding: 0;
  margin: 0;
}

.table-responsive {
  width: 100%;
  overflow-x: auto;
}

/* Add any additional styling here */
</style>
