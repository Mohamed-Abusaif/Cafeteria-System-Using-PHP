<template>
  <div class="container page-content py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h1>Product Management</h1>
      <button v-if="!showForm" class="btn btn-primary" @click="openCreateForm">
        <i class="bi bi-plus-circle me-2"></i>Add New Product
      </button>
    </div>

    <!-- Success/Error messages -->
    <div
      v-if="errorMessage"
      class="alert alert-dismissible fade show mb-4"
      :class="{
        'alert-danger': !errorMessage.includes('successfully'),
        'alert-success': errorMessage.includes('successfully')
      }"
      role="alert"
    >
      {{ errorMessage }}
      <button
        @click="errorMessage = ''"
        type="button"
        class="btn-close"
        aria-label="Close"
      ></button>
    </div>

    <div v-if="error && !errorMessage" class="alert alert-danger alert-dismissible fade show mb-4">
      Failed to load products: {{ error }}
      <button
        @click="error = ''"
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
import { useRouter } from 'vue-router'
import ProductForm from '../../components/admin/ProductForm.vue'
import ProductFilterBar from '../../components/admin/ProductFilterBar.vue'
import ProductTable from '../../components/admin/ProductTable.vue'
import ImageUpdateModal from '../../components/admin/ImageUpdateModal.vue'
import DeleteConfirmModal from '../../components/admin/DeleteConfirmModal.vue'

const authStore = useAuthStore()
const router = useRouter()
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
  errorMessage.value = ""

  try {
    // First, check if user is still authenticated
    await authStore.checkAuth()
    if (!authStore.isLoggedIn()) {
      throw new Error('Authentication required. Please log in.')
    }

    const params = new URLSearchParams({
      page: pagination.currentPage,
      limit: pagination.limit,
      ...filters,
    })

    const url = `${baseUrl}/controllers/product.controller.php?${params.toString()}`
    console.log("Fetching products from:", url)

    const response = await fetch(url, {
      credentials: 'include',
      headers: {
        'Accept': 'application/json'
      }
    })

    console.log("Response status:", response.status, response.statusText)

    if (!response.ok) {
      const errorText = await response.text()
      console.error("Error response:", errorText)
      try {
        const errorData = JSON.parse(errorText)
        throw new Error(errorData.message || `Server error (${response.status})`)
      } catch (e) {
        throw new Error(`Server error (${response.status}): ${errorText.substring(0, 100)}`)
      }
    }

    const responseText = await response.text()
    console.log("Raw response length:", responseText.length)
    console.log("Raw response preview:", responseText.substring(0, 200))

    // If response is empty, handle it gracefully
    if (!responseText.trim()) {
      console.error("Empty response received from server")
      products.value = []
      return
    }

    let data
    try {
      data = JSON.parse(responseText)
      console.log("Parsed data:", data)
    } catch (e) {
      console.error("JSON parse error:", e)
      throw new Error("Invalid JSON response from server: " + e.message)
    }

    // Handle the specific structure from the example
    if (data && data.message === "ok" && data.data && data.data.data && Array.isArray(data.data.data)) {
      console.log("Found the expected response structure with message='ok'")

      // Set products directly to the array
      products.value = data.data.data

      // Update pagination
      pagination.totalPages = data.data.total_pages || 1
      pagination.currentPage = data.data.current_page || 1
      pagination.totalItems = data.data.total || 0
      pagination.limit = data.data.per_page || 10

      console.log("Products loaded successfully:", products.value.length)
      return
    }

    // Continue with existing code for other formats
    if (data && data.data) {
      // The API returns a nested structure:
      // data -> data -> data (products array)
      if (data.data.data && Array.isArray(data.data.data)) {
        console.log("Found products array at data.data.data")
        products.value = data.data.data

        // Update pagination
        pagination.totalPages = data.data.total_pages || 1
        pagination.currentPage = data.data.current_page || 1
        pagination.totalItems = data.data.total || 0
        pagination.limit = data.data.per_page || 10
      } else {
        console.error("No products array found in the expected location")
        products.value = []
      }

      console.log("Final products value:", products.value)
    } else {
      console.error("API success=false or missing:", data)
      throw new Error(data.message || 'Failed to fetch products: API did not return success status')
    }
  } catch (err) {
    console.error('Error fetching products:', err)

    // Handle the case where message is "ok" but data structure is wrong
    if (err.message === 'ok') {
      error.value = 'The server returned a success message but with an unexpected data structure.'
      console.log('The "ok" error usually means the data structure is not what was expected.');
    } else {
      error.value = err.message || 'Unknown error occurred';
    }

    // If there's an authentication error, handle it properly
    if (err.message.includes('Authentication required')) {
      errorMessage.value = 'Your session has expired. Please log in again.'
      setTimeout(() => {
        router.push('/login')
      }, 2000)
    } else {
      // Otherwise just show the error but don't redirect
      errorMessage.value = `Failed to load products: ${error.value}`
      products.value = []
    }
  } finally {
    loading.value = false
  }
}

// Fetch categories for dropdowns
const fetchCategories = async () => {
  try {
    console.log("Fetching categories...")
    const response = await fetch(`${baseUrl}/controllers/category.controller.php`, {
      credentials: 'include',
      headers: {
        'Accept': 'application/json'
      }
    })

    if (!response.ok) {
      console.error(`Failed to fetch categories: ${response.status} ${response.statusText}`)
      throw new Error(`HTTP error! status: ${response.status}`)
    }

    const responseText = await response.text()
    console.log("Categories response text:", responseText.substring(0, 200))

    try {
      const data = JSON.parse(responseText)
      console.log("Categories data structure:", data)

      // The PHP backend returns { data: [...], message: "ok", statusCode: 200 }
      if (data && data.data) {
        // PHP api returns direct array in data property
        categories.value = Array.isArray(data.data) ? data.data : []
        console.log("Categories loaded:", categories.value)
      } else {
        console.error("No data property in categories response:", data)
        categories.value = []
      }
    } catch (parseError) {
      console.error("Failed to parse categories JSON:", parseError)
      throw new Error("Invalid JSON in categories response")
    }
  } catch (err) {
    console.error('Error fetching categories:', err)
    errorMessage.value = `Failed to load categories: ${err.message}`
    categories.value = []
  }
}

// Form handlers
const openCreateForm = async () => {
  if (categories.value.length === 0) {
    await fetchCategories()
  }
  selectedProduct.value = {}
  editMode.value = false
  showForm.value = true
}

const openEditForm = async (product) => {
  if (categories.value.length === 0) {
    await fetchCategories()
  }
  selectedProduct.value = { ...product }
  editMode.value = true
  showForm.value = true
}

const closeForm = () => {
  showForm.value = false
}

// Handle successful operation message
const showSuccessMessage = (message) => {
  errorMessage.value = message;
  setTimeout(() => {
    errorMessage.value = '';
  }, 3000);
};

const handleFormSubmit = async ({ formData, isEditing, id, hasNewImage }) => {
  formLoading.value = true
  error.value = null
  errorMessage.value = ""

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

    // Check if operation was successful based on status or statusCode
    const isSuccess = result.status === 'success' || result.statusCode === 200 || result.statusCode === 201;

    if (isSuccess) {
      // Show success message
      const actionType = isEditing ? 'updated' : 'created';
      showSuccessMessage(`Product successfully ${actionType}!`);

      closeForm()
      fetchProducts()
    } else {
      throw new Error(result.message || `Failed to ${isEditing ? 'update' : 'create'} product`)
    }
  } catch (err) {
    console.error(`Error ${isEditing ? 'updating' : 'creating'} product:`, err)
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

    // Check if operation was successful
    const isSuccess = result.status === 'success' || result.statusCode === 200 || result.statusCode === 201;

    if (isSuccess) {
      // Show success message in the modal
      modalError.value = "Product image successfully updated!"

      // Hide the modal after a delay
      setTimeout(() => {
        if (imageModalRef.value) {
          imageModalRef.value.hide()
          modalError.value = ''
        }
      }, 2000)

      fetchProducts()
    } else {
      throw new Error(result.message || 'Failed to update image')
    }
  } catch (err) {
    console.error('Error updating image:', err)
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

    // Check if operation was successful based on status or statusCode
    const isSuccess = result.status === 'success' || result.statusCode === 200;

    if (isSuccess) {
      if (deleteModalRef.value) {
        deleteModalRef.value.hide()
      }

      // Show success message
      showSuccessMessage("Product successfully deleted!");

      fetchProducts()
    } else {
      throw new Error(result.message || 'Failed to delete product')
    }
  } catch (err) {
    console.error('Error deleting product:', err)
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
  try {
    console.log("Checking authentication on ProductPage mount")
    const userData = await authStore.checkAuth()
    console.log("Auth check result:", userData)

    if (!userData || !authStore.isAdmin()) {
      console.log("User is not admin, will redirect")
      errorMessage.value = 'Admin access required. Redirecting to login...'
      setTimeout(() => {
        router.push('/login')
      }, 1500)
      return
    }

    console.log("Admin verified, fetching data")
    // Fetch categories first, then fetch products
    await fetchCategories()
    await fetchProducts()
  } catch (err) {
    console.error('Error during initialization:', err)
    errorMessage.value = 'Failed to initialize page. Please try again.'
  }
})
</script>

<style scoped>
.alert {
  animation: fadeIn 0.3s ease-in-out;
  border-left-width: 4px;
}

.alert-success {
  background-color: #d4edda;
  border-color: #28a745;
  color: #155724;
}

.alert-danger {
  background-color: #f8d7da;
  border-color: #dc3545;
  color: #721c24;
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
