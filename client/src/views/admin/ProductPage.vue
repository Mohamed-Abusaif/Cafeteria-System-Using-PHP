<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { Modal } from 'bootstrap'

const products = ref([])
const categories = ref([])
const isLoading = ref(true)
const error = ref(null)

// Custom error handling
const errorMessages = ref([])
const errorTimeout = ref(null)

// Pagination
const currentPage = ref(1)
const totalPages = ref(1)
const totalItems = ref(0)
const limit = ref(10)

// Filters
const nameFilter = ref('')
const categoryFilter = ref('')
const availabilityFilter = ref('')

// Form fields
const formData = ref({
  name: '',
  price: '',
  category_id: '',
  availability: 'available',
  image: null,
})

// Form validation errors
const formErrors = ref({
  name: '',
  price: '',
  category_id: '',
  image: '',
})

// Modal references
const addEditModalRef = ref(null)
let addEditModalInstance = null
const isEditing = ref(false)
const editingProductId = ref(null)

const deleteModalRef = ref(null)
let deleteModalInstance = null
const productToDelete = ref(null)

// Preview image
const imagePreview = ref(null)

// Image upload modal
const imageUpdateModalRef = ref(null)
let imageUpdateModalInstance = null
const productToUpdateImage = ref(null)
const newProductImage = ref(null)
const newImagePreview = ref(null)
const imageUploadLoading = ref(false)

const modalTitle = computed(() => {
  return isEditing.value ? `Update Product #${editingProductId.value}` : 'Add New Product'
})

async function fetchProducts() {
  isLoading.value = true
  error.value = null
  try {
    // Build query params for filtering
    const params = new URLSearchParams()
    params.append('page', currentPage.value)
    params.append('limit', limit.value)

    if (nameFilter.value) params.append('name', nameFilter.value)
    if (categoryFilter.value) params.append('category_id', categoryFilter.value)
    if (availabilityFilter.value) params.append('availability', availabilityFilter.value)

    const url = `${import.meta.env.VITE_SERVER_URL}/controllers/product.controller.php?${params.toString()}`
    const response = await fetch(url)

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`)

    const data = await response.json()
    if (data && data.data) {
      products.value = data.data.data || []
      totalPages.value = data.data.total_pages || 1
      totalItems.value = data.data.total || 0
      currentPage.value = data.data.current_page || 1
    } else {
      if (response.ok && data && Array.isArray(data.data)) {
        products.value = []
      } else {
        throw new Error(data.message || 'Failed to parse products data.')
      }
    }
  } catch (err) {
    error.value = err.message
    products.value = []
  } finally {
    isLoading.value = false
  }
}

async function fetchCategories() {
  try {
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/category.controller.php`,
    )
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`)
    const data = await response.json()
    if (data && data.data) {
      categories.value = data.data
    } else {
      categories.value = []
    }
  } catch (err) {
    console.error('Error fetching categories:', err.message)
    categories.value = []
  }
}

onMounted(async () => {
  await Promise.all([fetchProducts(), fetchCategories()])
  await nextTick()

  if (addEditModalRef.value) {
    addEditModalInstance = new Modal(addEditModalRef.value)
  }
  if (deleteModalRef.value) {
    deleteModalInstance = new Modal(deleteModalRef.value)
  }
  if (imageUpdateModalRef.value) {
    imageUpdateModalInstance = new Modal(imageUpdateModalRef.value)
  }
})

function resetForm() {
  formData.value = {
    name: '',
    price: '',
    category_id: '',
    availability: 'available',
    image: null,
  }
  imagePreview.value = null
}

function openAddModal() {
  isEditing.value = false
  editingProductId.value = null
  resetForm()

  if (addEditModalInstance) {
    addEditModalInstance.show()
  } else {
    const modalElement = document.getElementById('addEditProductModal')
    if (modalElement) new Modal(modalElement).show()
  }
}

function openUpdateModal(product) {
  isEditing.value = true
  editingProductId.value = product.id

  formData.value = {
    name: product.name,
    price: product.price,
    category_id: product.category_id,
    availability: product.availability || 'available',
    image: null, // We don't populate the file input
  }

  // Set image preview if available
  imagePreview.value = product.image

  if (addEditModalInstance) {
    addEditModalInstance.show()
  } else {
    const modalElement = document.getElementById('addEditProductModal')
    if (modalElement) new Modal(modalElement).show()
  }
}

function openDeleteModal(product) {
  productToDelete.value = product
  if (deleteModalInstance) {
    deleteModalInstance.show()
  } else {
    const modalElement = document.getElementById('deleteConfirmationModal')
    if (modalElement) new Modal(modalElement).show()
  }
}

function handleImageChange(event) {
  const file = event.target.files[0]
  if (file) {
    formData.value.image = file

    // Create preview
    const reader = new FileReader()
    reader.onload = (e) => {
      imagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  } else {
    formData.value.image = null
    imagePreview.value = null
  }
}

// Function to show error message
function showError(message, duration = 5000) {
  const id = Date.now()
  errorMessages.value.push({ id, message })

  // Set a timeout to remove the error after duration
  setTimeout(() => {
    errorMessages.value = errorMessages.value.filter((e) => e.id !== id)
  }, duration)
}

// Function to clear all errors
function clearErrors() {
  errorMessages.value = []
  if (errorTimeout.value) {
    clearTimeout(errorTimeout.value)
    errorTimeout.value = null
  }
}

// Open the update image modal
function openUpdateImageModal(product) {
  productToUpdateImage.value = product
  newProductImage.value = null
  newImagePreview.value = product.image

  if (imageUpdateModalInstance) {
    imageUpdateModalInstance.show()
  } else {
    const modalElement = document.getElementById('updateImageModal')
    if (modalElement) new Modal(modalElement).show()
  }
}

// Handle new image selection
function handleUpdateImageChange(event) {
  const file = event.target.files[0]
  if (file) {
    newProductImage.value = file

    // Create preview
    const reader = new FileReader()
    reader.onload = (e) => {
      newImagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  } else {
    newProductImage.value = null
    newImagePreview.value = productToUpdateImage.value?.image
  }
}

// Submit the image update
async function updateProductImage() {
  if (!productToUpdateImage.value || !newProductImage.value) {
    showError('Please select a new image to upload')
    return
  }

  imageUploadLoading.value = true

  const formData = new FormData()
  formData.append('image', newProductImage.value)

  try {
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/changeImage.controller.php/${productToUpdateImage.value.id}`,
      {
        method: 'POST',
        body: formData,
      },
    )

    const result = await response.json()
    if (result && result.statusCode === 200) {
      if (imageUpdateModalInstance) {
        imageUpdateModalInstance.hide()
      }
      await fetchProducts()
    } else {
      throw new Error(result.message || 'Failed to update product image')
    }
  } catch (err) {
    showError(`Error updating image: ${err.message}`)
  } finally {
    imageUploadLoading.value = false
    productToUpdateImage.value = null
    newProductImage.value = null
  }
}

async function saveOrUpdateProduct() {
  // Reset validation errors
  formErrors.value = {
    name: '',
    price: '',
    category_id: '',
    image: '',
  }

  // Validate form
  let isValid = true

  if (!formData.value.name.trim()) {
    formErrors.value.name = 'Product name is required'
    isValid = false
  }

  if (!formData.value.price) {
    formErrors.value.price = 'Product price is required'
    isValid = false
  } else if (isNaN(formData.value.price) || parseFloat(formData.value.price) < 0) {
    formErrors.value.price = 'Price must be a valid positive number'
    isValid = false
  }

  if (!formData.value.category_id) {
    formErrors.value.category_id = 'Category is required'
    isValid = false
  }

  if (!isEditing.value && !formData.value.image) {
    formErrors.value.image = 'Product image is required for new products'
    isValid = false
  }

  if (!isValid) {
    return
  }

  // Create FormData for multipart/form-data (for file upload)
  const form = new FormData()
  form.append('name', formData.value.name)
  form.append('price', formData.value.price)
  form.append('category_id', formData.value.category_id)
  form.append('availability', formData.value.availability)

  // Add image only if we have one and we're creating or updating with new image
  if (
    formData.value.image &&
    (isEditing.value === false || (isEditing.value && formData.value.image instanceof File))
  ) {
    form.append('image', formData.value.image)
  }

  let url = `${import.meta.env.VITE_SERVER_URL}/controllers/product.controller.php`
  let method = 'POST'

  if (isEditing.value && editingProductId.value) {
    method = 'PATCH'
    url += `/${editingProductId.value}`
  }

  try {
    const response = await fetch(url, {
      method: method,
      body: isEditing.value && !formData.value.image ? JSON.stringify(formData.value) : form,
      headers:
        isEditing.value && !formData.value.image
          ? {
              'Content-Type': 'application/json',
            }
          : {},
    })

    const result = await response.json()

    if (result && (result.statusCode === 200 || result.statusCode === 201)) {
      if (addEditModalInstance) {
        addEditModalInstance.hide()
      }
      resetForm()
      await fetchProducts()
    } else {
      throw new Error(result.message || `Failed to ${isEditing.value ? 'update' : 'save'} product.`)
    }
  } catch (err) {
    showError(`Error ${isEditing.value ? 'updating' : 'saving'} product: ${err.message}`)
  }
}

async function confirmDelete() {
  if (!productToDelete.value) return

  try {
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/product.controller.php/${productToDelete.value.id}`,
      {
        method: 'DELETE',
      },
    )

    const result = await response.json()
    if (result && result.statusCode === 200) {
      if (deleteModalInstance) {
        deleteModalInstance.hide()
      }
      await fetchProducts()
    } else {
      throw new Error(result.message || 'Failed to delete product.')
    }
  } catch (err) {
    showError(`Error deleting product: ${err.message}`)
  } finally {
    productToDelete.value = null
  }
}

function changePage(page) {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  fetchProducts()
}

function applyFilters() {
  currentPage.value = 1 // Reset to first page when filtering
  fetchProducts()
}

function clearFilters() {
  nameFilter.value = ''
  categoryFilter.value = ''
  availabilityFilter.value = ''
  currentPage.value = 1
  fetchProducts()
}
</script>

<template>
  <div class="full-width-container mt-2">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-3 px-3">
      <h2 class="mb-0">Products Details</h2>
      <button type="button" class="btn btn-primary" @click="openAddModal">
        <i class="bi bi-plus-circle me-1"></i> Add Product
      </button>
    </div>

    <!-- Error Messages Component -->
    <div class="error-messages-container">
      <div
        v-for="error in errorMessages"
        :key="error.id"
        class="alert alert-danger alert-dismissible fade show mx-3 mb-2"
      >
        {{ error.message }}
        <button
          @click="errorMessages = errorMessages.filter((e) => e.id !== error.id)"
          type="button"
          class="btn-close"
          aria-label="Close"
        ></button>
      </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-light p-3 mb-3 rounded">
      <div class="row g-3">
        <div class="col-md-3">
          <label for="nameFilter" class="form-label">Product Name</label>
          <input
            type="text"
            class="form-control"
            id="nameFilter"
            v-model="nameFilter"
            placeholder="Search by name..."
          />
        </div>
        <div class="col-md-3">
          <label for="categoryFilter" class="form-label">Category</label>
          <select class="form-select" id="categoryFilter" v-model="categoryFilter">
            <option value="">All Categories</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
        </div>
        <div class="col-md-3">
          <label for="availabilityFilter" class="form-label">Availability</label>
          <select class="form-select" id="availabilityFilter" v-model="availabilityFilter">
            <option value="">All</option>
            <option value="available">Available</option>
            <option value="unavailable">Unavailable</option>
          </select>
        </div>
        <div class="col-md-3 d-flex align-items-end">
          <div class="d-flex gap-2">
            <button class="btn btn-primary" @click="applyFilters">
              <i class="bi bi-search me-1"></i> Filter
            </button>
            <button class="btn btn-outline-secondary" @click="clearFilters">
              <i class="bi bi-x-circle me-1"></i> Clear
            </button>
          </div>
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
      Failed to load products: {{ error }}
    </div>

    <!-- Table Section -->
    <div v-else class="full-width-table px-3">
      <div v-if="!products || products.length === 0" class="alert alert-info mx-3">
        No products found. Try adjusting your filters or adding a new product.
      </div>
      <table v-else class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th scope="col" style="width: 5%">#</th>
            <th scope="col" style="width: 15%">Image</th>
            <th scope="col" style="width: 20%">Name</th>
            <th scope="col" style="width: 10%">Price</th>
            <th scope="col" style="width: 15%">Category</th>
            <th scope="col" style="width: 15%">Availability</th>
            <th scope="col" style="width: 20%">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="product in products" :key="product.id">
            <th scope="row">{{ product.id }}</th>
            <td>
              <div class="position-relative image-container">
                <img
                  :src="product.image || 'https://via.placeholder.com/50'"
                  alt="Product image"
                  class="img-thumbnail"
                  style="width: 50px; height: 50px; object-fit: cover"
                />
                <div class="image-overlay" @click.stop="openUpdateImageModal(product)">
                  <i class="bi bi-camera"></i>
                </div>
              </div>
            </td>
            <td>{{ product.name }}</td>
            <td>${{ parseFloat(product.price).toFixed(2) }}</td>
            <td>{{ product.category ? product.category.name : 'No Category' }}</td>
            <td>
              <span
                class="badge"
                :class="product.availability === 'available' ? 'bg-success' : 'bg-danger'"
              >
                {{ product.availability === 'available' ? 'Available' : 'Unavailable' }}
              </span>
            </td>
            <td>
              <div class="btn-group">
                <button
                  type="button"
                  class="btn btn-sm btn-warning me-1"
                  @click="openUpdateModal(product)"
                >
                  <i class="bi bi-pencil-square"></i> Update
                </button>
                <button
                  type="button"
                  class="btn btn-sm btn-danger"
                  @click="openDeleteModal(product)"
                >
                  <i class="bi bi-trash"></i> Delete
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div
        v-if="totalPages > 0"
        class="d-flex justify-content-between align-items-center px-3 py-2"
      >
        <div>Showing {{ products.length }} of {{ totalItems }} products</div>
        <nav aria-label="Product pagination">
          <ul class="pagination mb-0">
            <li class="page-item" :class="{ disabled: currentPage <= 1 }">
              <a
                @click.prevent="changePage(currentPage - 1)"
                class="page-link"
                href="#"
                aria-label="Previous"
              >
                <span aria-hidden="true">&laquo;</span>
              </a>
            </li>
            <li
              v-for="page in totalPages"
              :key="page"
              class="page-item"
              :class="{ active: page === currentPage }"
            >
              <a @click.prevent="changePage(page)" class="page-link" href="#">{{ page }}</a>
            </li>
            <li class="page-item" :class="{ disabled: currentPage >= totalPages }">
              <a
                @click.prevent="changePage(currentPage + 1)"
                class="page-link"
                href="#"
                aria-label="Next"
              >
                <span aria-hidden="true">&raquo;</span>
              </a>
            </li>
          </ul>
        </nav>
      </div>
    </div>

    <!-- Add/Edit Product Modal -->
    <div
      class="modal fade"
      id="addEditProductModal"
      tabindex="-1"
      aria-labelledby="addEditProductModalLabel"
      aria-hidden="true"
      ref="addEditModalRef"
    >
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addEditProductModalLabel">{{ modalTitle }}</h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveOrUpdateProduct">
              <div class="mb-3">
                <label for="productName" class="form-label">Product Name</label>
                <input
                  type="text"
                  class="form-control"
                  :class="{ 'is-invalid': formErrors.name }"
                  id="productName"
                  v-model="formData.name"
                  required
                />
                <div class="invalid-feedback" v-if="formErrors.name">{{ formErrors.name }}</div>
              </div>

              <div class="mb-3">
                <label for="productPrice" class="form-label">Price</label>
                <div class="input-group" :class="{ 'is-invalid': formErrors.price }">
                  <span class="input-group-text">$</span>
                  <input
                    type="number"
                    step="0.01"
                    min="0"
                    class="form-control"
                    :class="{ 'is-invalid': formErrors.price }"
                    id="productPrice"
                    v-model="formData.price"
                    required
                  />
                </div>
                <div class="invalid-feedback" v-if="formErrors.price">{{ formErrors.price }}</div>
              </div>

              <div class="mb-3">
                <label for="productCategory" class="form-label">Category</label>
                <select
                  class="form-select"
                  :class="{ 'is-invalid': formErrors.category_id }"
                  id="productCategory"
                  v-model="formData.category_id"
                  required
                >
                  <option value="" disabled>Select a category</option>
                  <option v-for="category in categories" :key="category.id" :value="category.id">
                    {{ category.name }}
                  </option>
                </select>
                <div class="invalid-feedback" v-if="formErrors.category_id">
                  {{ formErrors.category_id }}
                </div>
              </div>

              <div class="mb-3">
                <label for="productAvailability" class="form-label">Availability</label>
                <select
                  class="form-select"
                  id="productAvailability"
                  v-model="formData.availability"
                >
                  <option value="available">Available</option>
                  <option value="unavailable">Unavailable</option>
                </select>
              </div>

              <div v-if="!isEditing" class="mb-3">
                <label for="productImage" class="form-label">Product Image</label>
                <input
                  type="file"
                  class="form-control"
                  :class="{ 'is-invalid': formErrors.image }"
                  id="productImage"
                  accept="image/*"
                  @change="handleImageChange"
                  :required="!isEditing"
                />
                <small class="form-text text-muted">
                  {{
                    isEditing
                      ? 'Only upload if you want to change the image.'
                      : 'Please upload a product image.'
                  }}
                </small>
                <div class="invalid-feedback" v-if="formErrors.image">{{ formErrors.image }}</div>
              </div>

              <div v-if="imagePreview" class="mb-3 text-center">
                <p class="mb-1">Image Preview:</p>
                <img
                  :src="imagePreview"
                  alt="Product preview"
                  class="img-thumbnail"
                  style="max-height: 150px"
                />
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" @click="saveOrUpdateProduct">
              {{ isEditing ? 'Update' : 'Save' }} Product
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
            <p>
              Are you sure you want to delete the product:
              <strong>{{ productToDelete?.name }}</strong
              >?
            </p>
            <p class="text-danger"><small>This action cannot be undone.</small></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger" @click="confirmDelete">
              Delete Product
            </button>
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
      ref="imageUpdateModalRef"
    >
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="updateImageModalLabel">Update Product Image</h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="updateProductImage">
              <div class="mb-3">
                <label for="newImage" class="form-label">New Image</label>
                <input
                  type="file"
                  class="form-control"
                  id="newImage"
                  accept="image/*"
                  @change="handleUpdateImageChange"
                />
              </div>

              <div v-if="newImagePreview" class="mb-3 text-center">
                <p class="mb-1">Image Preview:</p>
                <img
                  :src="newImagePreview"
                  alt="New product image"
                  class="img-thumbnail"
                  style="max-height: 150px"
                />
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" @click="updateProductImage">
              Update Image
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pagination {
  cursor: pointer;
}

.full-width-container {
  width: 100%;
  overflow-x: auto;
}

.full-width-table {
  min-width: 100%;
  overflow-x: auto;
}

.form-control:focus,
.form-select:focus {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.input-group-text {
  background-color: #f8f9fa;
}

.badge {
  padding: 0.5em 0.75em;
  font-weight: 500;
}

.error-messages-container {
  position: fixed;
  top: 20px;
  right: 20px;
  z-index: 1050;
  max-width: 400px;
}

.image-container {
  position: relative;
  width: 50px;
  height: 50px;
  overflow: hidden;
  display: inline-block;
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
  cursor: pointer;
}

.image-overlay i {
  color: white;
  font-size: 1.2rem;
}

.image-container:hover .image-overlay {
  opacity: 1;
}
</style>
