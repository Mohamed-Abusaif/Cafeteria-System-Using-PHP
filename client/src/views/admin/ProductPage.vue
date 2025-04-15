<template>
  <div class="container page-content py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1>Product Management</h1>
      <button v-if="!showForm" class="btn btn-primary" @click="openCreateForm">
        <i class="bi bi-plus-circle me-2"></i>Add New Product
      </button>
    </div>

    <!-- Error messages -->
    <div v-if="errorMessage" class="alert alert-danger alert-dismissible fade show mb-4">
      {{ errorMessage }}
      <button
        @click="errorMessage = ''"
        type="button"
        class="btn-close"
        aria-label="Close"
      ></button>
    </div>

    <!-- Product form (create/edit) -->
    <ProductForm
      v-if="showForm"
      :product="selectedProduct"
      :categories="categories"
      :edit-mode="editMode"
      :loading="formLoading"
      @submit="handleFormSubmit"
      @cancel="closeForm"
    />

    <!-- Filters -->
    <ProductFilterBar :categories="categories" @filter="handleFilterChange" @reset="resetFilters" />

    <!-- Products List -->
    <ProductTable
      :products="products"
      :categories="categories"
      :pagination="pagination"
      :loading="loading"
      :error="error"
      @edit="openEditForm"
      @delete="openDeleteModal"
      @update-image="openImageUpdateModal"
      @page-change="changePage"
    />

    <!-- Image Update Modal -->
    <ImageUpdateModal
      ref="imageModalRef"
      :product="selectedProduct"
      :loading="imageUpdateLoading"
      :error="modalError"
      @update="updateProductImage"
    />

    <!-- Delete Confirmation Modal -->
    <DeleteConfirmModal
      ref="deleteModalRef"
      :product="selectedProduct"
      :loading="deleteLoading"
      :error="modalError"
      @confirm="deleteProduct"
    />
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useAuthStore } from '../../stores/authStore'
import ProductForm from '../../components/admin/ProductForm.vue'
import ProductFilterBar from '../../components/admin/ProductFilterBar.vue'
import ProductTable from '../../components/admin/ProductTable.vue'
import ImageUpdateModal from '../../components/admin/ImageUpdateModal.vue'
import DeleteConfirmModal from '../../components/admin/DeleteConfirmModal.vue'

const authStore = useAuthStore()
const baseUrl = import.meta.env.VITE_SERVER_URL || ''

// State variables
const products = ref([])
const categories = ref([])
const loading = ref(true)
const error = ref(null)
const errorMessage = ref('')
const modalError = ref('')

// Form state
const showForm = ref(false)
const editMode = ref(false)
const selectedProduct = ref({})
const formLoading = ref(false)

// Modal states
const imageUpdateLoading = ref(false)
const deleteLoading = ref(false)

// Refs for modal components
const imageModalRef = ref(null)
const deleteModalRef = ref(null)

// Pagination
const pagination = reactive({
  currentPage: 1,
  totalPages: 1,
  totalItems: 0,
  limit: 10,
})

// Fetch products with optional filters
const fetchProducts = async (filters = {}) => {
  loading.value = true
  error.value = null

  try {
    const params = new URLSearchParams({
      page: pagination.currentPage,
      limit: pagination.limit,
      ...filters,
    })

    const response = await fetch(
      `${baseUrl}/controllers/product.controller.php?${params.toString()}`,
      {
        credentials: 'include',
      },
    )

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`)

    const data = await response.json()
    if (data && data.status === 'success') {
      products.value = data.data.items || []
      pagination.totalPages = data.data.last_page || 1
      pagination.totalItems = data.data.total || 0
    } else {
      throw new Error(data.message || 'Failed to fetch products')
    }
  } catch (err) {
    error.value = err.message
    products.value = []
  } finally {
    loading.value = false
  }
}

// Fetch categories for dropdowns
const fetchCategories = async () => {
  try {
    const response = await fetch(`${baseUrl}/controllers/category.controller.php`, {
      credentials: 'include',
    })

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`)

    const data = await response.json()
    if (data && data.status === 'success') {
      categories.value = data.data.items || []
    } else {
      categories.value = []
    }
  } catch (err) {
    console.error('Error fetching categories:', err)
    categories.value = []
  }
}

// Form handlers
const openCreateForm = () => {
  selectedProduct.value = {}
  editMode.value = false
  showForm.value = true
}

const openEditForm = (product) => {
  selectedProduct.value = { ...product }
  editMode.value = true
  showForm.value = true
}

const closeForm = () => {
  showForm.value = false
}

const handleFormSubmit = async ({ formData, isEditing, id, hasNewImage }) => {
  formLoading.value = true

  try {
    let url = `${baseUrl}/controllers/product.controller.php`
    let method = 'POST'

    if (isEditing && id) {
      url += `/${id}`
      method = 'PATCH'
    }

    // Handle different formats for PATCH with/without new image
    const options = {
      method,
      credentials: 'include',
      body:
        isEditing && !hasNewImage
          ? JSON.stringify({
              name: formData.get('name'),
              price: formData.get('price'),
              category_id: formData.get('category_id'),
              availability: formData.get('availability'),
            })
          : formData,
      headers: isEditing && !hasNewImage ? { 'Content-Type': 'application/json' } : {},
    }

    const response = await fetch(url, options)
    const result = await response.json()

    if (result.status === 'success') {
      closeForm()
      fetchProducts()
    } else {
      throw new Error(result.message || 'Failed to save product')
    }
  } catch (err) {
    errorMessage.value = `Error ${isEditing ? 'updating' : 'creating'} product: ${err.message}`
  } finally {
    formLoading.value = false
  }
}

// Image update modal
const openImageUpdateModal = (product) => {
  selectedProduct.value = product
  modalError.value = ''

  if (imageModalRef.value) {
    imageModalRef.value.show()
  }
}

const updateProductImage = async ({ id, formData }) => {
  if (!id || !formData) return

  imageUpdateLoading.value = true
  modalError.value = ''

  try {
    const response = await fetch(`${baseUrl}/controllers/changeImage.controller.php/${id}`, {
      method: 'POST',
      credentials: 'include',
      body: formData,
    })

    const result = await response.json()
    if (result.status === 'success') {
      if (imageModalRef.value) {
        imageModalRef.value.hide()
      }
      fetchProducts()
    } else {
      throw new Error(result.message || 'Failed to update image')
    }
  } catch (err) {
    modalError.value = `Error updating image: ${err.message}`
  } finally {
    imageUpdateLoading.value = false
  }
}

// Delete modal
const openDeleteModal = (product) => {
  selectedProduct.value = product
  modalError.value = ''

  if (deleteModalRef.value) {
    deleteModalRef.value.show()
  }
}

const deleteProduct = async (id) => {
  if (!id) return

  deleteLoading.value = true
  modalError.value = ''

  try {
    const response = await fetch(`${baseUrl}/controllers/product.controller.php/${id}`, {
      method: 'DELETE',
      credentials: 'include',
    })

    const result = await response.json()
    if (result.status === 'success') {
      if (deleteModalRef.value) {
        deleteModalRef.value.hide()
      }
      fetchProducts()
    } else {
      throw new Error(result.message || 'Failed to delete product')
    }
  } catch (err) {
    modalError.value = `Error deleting product: ${err.message}`
  } finally {
    deleteLoading.value = false
  }
}

// Filter and pagination handlers
const handleFilterChange = (filters) => {
  pagination.currentPage = 1 // Reset to first page when filtering
  fetchProducts(filters)
}

const resetFilters = () => {
  pagination.currentPage = 1
  fetchProducts({})
}

const changePage = (page) => {
  if (page < 1 || page > pagination.totalPages) return
  pagination.currentPage = page
  fetchProducts()
}

onMounted(async () => {
  // Verify user is authenticated and authorized (admin)
  if (!authStore.isAdmin()) {
    window.location.href = '/#/login'
    return
  }

  await Promise.all([fetchCategories(), fetchProducts()])
})
</script>

<style scoped>
.alert {
  animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
