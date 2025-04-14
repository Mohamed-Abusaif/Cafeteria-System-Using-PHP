<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const featuredProducts = ref([])
const categories = ref([])
const isLoading = ref(true)
const userData = ref({})
const error = ref(null)
const successMessage = ref('')

// Search and filtering
const searchQuery = ref('')
const selectedCategory = ref(null)
const currentPage = ref(1)
const itemsPerPage = 8
const totalItems = ref(0)
const totalProductsCount = ref(0)

// Toast notification
const showToast = ref(false)
const toastMessage = ref('')
const toastType = ref('success') // success or error

// Fetch featured products and categories
onMounted(async () => {
  await fetchUserData()
  await fetchProductsData()
  await fetchCategoriesData()
})

async function fetchProductsData() {
  isLoading.value = true
  try {
    // Using URLSearchParams for server-side filtering and pagination
    const params = new URLSearchParams()
    params.append('page', currentPage.value)
    params.append('limit', itemsPerPage)
    if (searchQuery.value) params.append('name', searchQuery.value)
    if (selectedCategory.value) params.append('category_id', selectedCategory.value)

    const url = `${import.meta.env.VITE_SERVER_URL}/controllers/product.controller.php?${params.toString()}`
    const response = await fetch(url)

    if (response.ok) {
      const productsData = await response.json()
      if (productsData && productsData.data) {
        featuredProducts.value = productsData.data.data || []
        // Set total items for pagination from the response
        totalItems.value = productsData.data.total || 0
        totalProductsCount.value = productsData.data.total_count || 0
      }
    }
  } catch (error) {
    console.error('Failed to fetch products:', error)
    error.value = 'Failed to fetch data'
  } finally {
    isLoading.value = false
  }
}

async function fetchCategoriesData() {
  try {
    const categoriesResponse = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/category.controller.php`,
    )
    if (categoriesResponse.ok) {
      const categoriesData = await categoriesResponse.json()
      if (categoriesData && categoriesData.data) {
        categories.value = categoriesData.data
      }
    }
  } catch (error) {
    console.error('Failed to fetch categories:', error)
    error.value = 'Failed to fetch data'
  }
}

async function fetchUserData() {
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
    }
  } catch (error) {
    console.error('Failed to load user data:', error)
    error.value = 'Failed to load user data'
  } finally {
    isLoading.value = false
  }
}

function navigateToCart() {
  if (!userData.value.id) {
    displayToast('Please login to view cart', 'error')
    return
  }
  router.push('/cart')
}

function navigateToProducts() {
  // Scroll to products section
  const productsSection = document.querySelector('.featured-products')
  if (productsSection) {
    productsSection.scrollIntoView({ behavior: 'smooth' })
  }
}

async function addToCart(productId) {
  isLoading.value = true
  try {
    if (!userData.value.id) {
      displayToast('Please login to add to cart', 'error')
      return
    }
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/cart.controller.php`,
      {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          product_id: productId,
          user_id: userData.value.id,
        }),
      },
    )
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`)
    const resData = await response.json()
    if (resData && resData.data) {
      displayToast('Product added to cart successfully!', 'success')
    }
  } catch (error) {
    displayToast('Failed to add to cart', 'error')
  } finally {
    isLoading.value = false
  }
}

// Function to display toast
function displayToast(message, type = 'success') {
  toastMessage.value = message
  toastType.value = type
  showToast.value = true

  // Auto hide toast after 3 seconds
  setTimeout(() => {
    showToast.value = false
  }, 3000)
}

// Select category for filtering
function selectCategory(categoryId) {
  if (selectedCategory.value === categoryId) {
    selectedCategory.value = null // Deselect if already selected
  } else {
    selectedCategory.value = categoryId
  }
  currentPage.value = 1 // Reset to first page after changing category
  fetchProductsData() // Fetch products with new filter
}

// Reset all filters
function resetFilters() {
  searchQuery.value = ''
  selectedCategory.value = null
  currentPage.value = 1
  fetchProductsData() // Fetch all products without filters
}

// Watch for changes in search query with debounce
let searchTimeout = null
watch(searchQuery, () => {
  if (searchTimeout) clearTimeout(searchTimeout)

  searchTimeout = setTimeout(() => {
    currentPage.value = 1 // Reset to first page on new search
    fetchProductsData()
  }, 500) // Debounce 500ms
})

// Watch for page changes
watch(currentPage, () => {
  fetchProductsData()
})

// Computed property for total pages
const totalPages = computed(() => {
  return Math.ceil(totalItems.value / itemsPerPage)
})

// Computed property to determine which page numbers to display
const displayedPages = computed(() => {
  if (totalPages.value <= 5) {
    // If 5 or fewer pages, show all
    return Array.from({ length: totalPages.value }, (_, i) => i + 1)
  }

  // Always show current page, and try to have 2 pages on each side
  let start = Math.max(currentPage.value - 2, 1)
  let end = Math.min(start + 4, totalPages.value)

  // Adjust start if we're near the end
  if (end === totalPages.value) {
    start = Math.max(end - 4, 1)
  }

  return Array.from({ length: end - start + 1 }, (_, i) => start + i)
})

// Page navigation
function goToPage(page) {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
    navigateToProducts()
  }
}

// Go to first and last page
function goToFirstPage() {
  goToPage(1)
}

function goToLastPage() {
  goToPage(totalPages.value)
}
</script>

<template>
  <div class="homepage">
    <!-- Toast Notification -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1050">
      <div
        v-if="showToast"
        class="toast show"
        :class="toastType === 'success' ? 'bg-success text-white' : 'bg-danger text-white'"
        role="alert"
        aria-live="assertive"
        aria-atomic="true"
      >
        <div class="toast-header">
          <strong class="me-auto">{{ toastType === 'success' ? 'Success' : 'Error' }}</strong>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="toast"
            aria-label="Close"
            @click="showToast = false"
          ></button>
        </div>
        <div class="toast-body">
          {{ toastMessage }}
        </div>
      </div>
    </div>

    <!-- Hero Section -->
    <section class="hero">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-6 hero-text">
            <h1 class="display-4 fw-bold mb-4">
              Premium Drinks, <span class="text-primary">At Your Fingertips</span>
            </h1>
            <p class="lead mb-4">
              Experience our handcrafted beverages with easy ordering, fast service, and exceptional
              quality.
            </p>
            <div class="d-flex gap-3 mt-4">
              <button class="btn btn-primary btn-lg px-4 py-2" @click="navigateToProducts">
                View Menu
                <i class="bi bi-arrow-right-circle ms-2"></i>
              </button>
              <button class="btn btn-outline-dark btn-lg px-4 py-2" @click="navigateToCart">
                View Cart
                <i class="bi bi-cart-plus ms-2"></i>
              </button>
            </div>
          </div>
          <div class="col-lg-6 mt-5 mt-lg-0">
            <div class="hero-img-container">
              <img
                src="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1080&q=80"
                alt="Specialty Coffee and Drinks"
                class="img-fluid rounded-4 shadow-lg"
              />
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Featured Products with Search, Filters and Pagination -->
    <section class="featured-products py-5 bg-light">
      <div class="container">
        <div class="text-center mb-5">
          <h2 class="fw-bold">Featured <span class="text-primary">Drinks</span></h2>
          <p class="text-muted">Our most popular beverages that customers love</p>
        </div>

        <div class="row">
          <!-- Search and Filters Column -->
          <div class="col-lg-3 mb-4">
            <!-- Search Bar -->
            <div class="card mb-4 border-0 shadow-sm">
              <div class="card-body">
                <div class="input-group">
                  <input
                    type="text"
                    class="form-control"
                    placeholder="Search products..."
                    v-model="searchQuery"
                    aria-label="Search products"
                  />
                  <button class="btn btn-outline-secondary" type="button" @click="resetFilters()">
                    <i class="bi bi-x-circle"></i>
                  </button>
                </div>
              </div>
            </div>

            <!-- Categories Filter with Radio Buttons -->
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px; max-height: 80vh">
              <div
                class="card-header bg-primary text-white d-flex justify-content-between align-items-center"
              >
                <h5 class="mb-0">Categories</h5>
              </div>

              <div class="card-body p-0">
                <div
                  class="categories-scrollable"
                  style="max-height: calc(80vh - 56px); overflow-y: auto"
                >
                  <div class="list-group list-group-flush">
                    <!-- All Categories Option -->
                    <button
                      type="button"
                      class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                      :class="{ active: selectedCategory === null }"
                      @click="resetFilters()"
                    >
                      <span>All Categories</span>
                    </button>

                    <!-- Individual Categories -->
                    <button
                      v-for="category in categories"
                      :key="category.id"
                      type="button"
                      class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                      :class="{ active: selectedCategory === category.id }"
                      @click="selectCategory(category.id)"
                    >
                      <span>{{ category.name }}</span>
                      <span class="badge bg-primary rounded-pill">{{
                        category.product_count
                      }}</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Products Grid Column -->
          <div class="col-lg-9">
            <div class="row g-4">
              <div v-if="isLoading" class="text-center py-5 col-12">
                <div class="spinner-border text-primary" role="status">
                  <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2">Loading products...</p>
              </div>

              <template v-else-if="featuredProducts.length > 0">
                <div
                  class="col-xl-3 col-lg-4 col-md-6"
                  v-for="product in featuredProducts"
                  :key="product.id"
                >
                  <div class="card product-card border-0 shadow-sm h-100 transition-all">
                    <div class="position-relative overflow-hidden" style="height: 180px">
                      <img
                        :src="
                          product.image || 'https://via.placeholder.com/300x200?text=Product+Image'
                        "
                        class="card-img-top h-100 w-100 object-fit-cover"
                        alt="Product Image"
                      />
                      <div class="position-absolute top-0 end-0 m-2">
                        <span
                          class="badge"
                          :class="product.availability === 'available' ? 'bg-success' : 'bg-danger'"
                        >
                          {{ product.availability === 'available' ? 'Available' : 'Unavailable' }}
                        </span>
                      </div>
                      <div class="product-overlay"></div>
                    </div>
                    <div class="card-body d-flex flex-column">
                      <div class="mb-2">
                        <h5 class="card-title fw-bold mb-1">{{ product.name }}</h5>
                        <p class="card-text text-muted small mb-2">
                          {{ product.category.name }}
                        </p>
                      </div>
                      <div class="mt-auto">
                        <p class="card-price text-primary fw-bold mb-3">
                          ${{ parseFloat(product.price).toFixed(2) }}
                        </p>
                        <button
                          class="btn w-100"
                          :class="
                            product.availability === 'available'
                              ? 'btn-primary'
                              : 'btn-outline-secondary'
                          "
                          :disabled="product.availability !== 'available'"
                          @click="addToCart(product.id)"
                        >
                          <i
                            class="bi"
                            :class="
                              product.availability === 'available'
                                ? 'bi-cart-plus'
                                : 'bi-slash-circle'
                            "
                          ></i>
                          {{ product.availability === 'available' ? 'Add to Cart' : 'Unavailable' }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </template>

              <div v-else class="col-12 text-center py-5">
                <div class="alert alert-info">
                  <i class="bi bi-exclamation-circle me-2"></i>
                  No products found matching your criteria. Try changing your search or filters.
                </div>
                <button class="btn btn-outline-primary mt-3" @click="resetFilters">
                  <i class="bi bi-arrow-counterclockwise me-2"></i>
                  Reset Filters
                </button>
              </div>
            </div>

            <!-- Pagination with First/Last Page Buttons -->
            <div v-if="!isLoading && totalPages > 1" class="d-flex justify-content-center mt-5">
              <nav aria-label="Product pagination">
                <ul class="pagination">
                  <li class="page-item" :class="{ disabled: currentPage === 1 }">
                    <button
                      class="page-link"
                      @click="goToPage(currentPage - 1)"
                      aria-label="Previous"
                    >
                      <span aria-hidden="true">&laquo;</span>
                    </button>
                  </li>

                  <li
                    v-for="page in displayedPages"
                    :key="page"
                    class="page-item"
                    :class="{ active: page === currentPage }"
                  >
                    <button class="page-link" @click="goToPage(page)">{{ page }}</button>
                  </li>

                  <li class="page-item" :class="{ disabled: currentPage === totalPages }">
                    <button class="page-link" @click="goToPage(currentPage + 1)" aria-label="Next">
                      <span aria-hidden="true">&raquo;</span>
                    </button>
                  </li>
                </ul>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works py-5">
      <div class="container">
        <div class="text-center mb-5">
          <h2 class="fw-bold">How It <span class="text-primary">Works</span></h2>
          <p class="text-muted">Quick and easy steps to order from our cafeteria</p>
        </div>

        <div class="row g-4 justify-content-center">
          <div class="col-md-4 text-center">
            <div class="step-card p-4">
              <div class="step-icon mb-3">
                <div class="icon-circle">
                  <i class="bi bi-search fs-2"></i>
                </div>
              </div>
              <h4 class="fw-bold">1. Browse Menu</h4>
              <p class="text-muted">
                Explore our diverse beverage menu and find your favorite drinks.
              </p>
            </div>
          </div>
          <div class="col-md-4 text-center">
            <div class="step-card p-4">
              <div class="step-icon mb-3">
                <div class="icon-circle">
                  <i class="bi bi-cart-plus fs-2"></i>
                </div>
              </div>
              <h4 class="fw-bold">2. Place Order</h4>
              <p class="text-muted">Add items to your cart and complete your order in minutes.</p>
            </div>
          </div>
          <div class="col-md-4 text-center">
            <div class="step-card p-4">
              <div class="step-icon mb-3">
                <div class="icon-circle">
                  <i class="bi bi-cup-hot fs-2"></i>
                </div>
              </div>
              <h4 class="fw-bold">3. Enjoy!</h4>
              <p class="text-muted">Collect your order or have it delivered to your room.</p>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.product-card {
  transition: all 0.3s ease;
  border-radius: 10px;
  overflow: hidden;
}

.product-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.product-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.1), transparent);
  opacity: 0;
  transition: opacity 0.3s ease;
}

.product-card:hover .product-overlay {
  opacity: 1;
}

.categories-wrapper {
  max-height: 300px;
  overflow-y: auto;
}

/* Custom radio buttons styling */
.form-check {
  padding-left: 0;
  margin-bottom: 10px;
}

.form-check-input {
  margin-right: 10px;
}

.form-check-label {
  cursor: pointer;
  padding: 8px 10px;
  border-radius: 5px;
  transition: all 0.2s ease;
  margin-left: 10px;
}

.form-check-input:checked + .form-check-label {
  background-color: rgba(13, 110, 253, 0.1);
  color: #0d6efd;
  font-weight: 500;
}

.list-group-item.active {
  background-color: #0d6efd;
  border-color: #0d6efd;
}

.object-fit-cover {
  object-fit: cover;
}

.transition-all {
  transition: all 0.3s ease;
}

.sticky-top {
  position: sticky;
  z-index: 10;
}

.homepage {
  overflow-x: hidden;
}

/* Hero Section */
.hero {
  padding: 80px 0;
  background: linear-gradient(to right, #f8f9fa 0%, #f1f1f1 100%);
}

.hero-text h1 {
  font-size: 3rem;
  line-height: 1.2;
}

.hero-text p {
  color: #6c757d;
  font-size: 1.2rem;
}

.hero-img-container {
  position: relative;
}

.hero-img-container img {
  transition: transform 0.5s ease;
}

.hero-img-container:hover img {
  transform: scale(1.02);
}

/* Category Cards */
.category-card {
  transition:
    transform 0.3s ease,
    box-shadow 0.3s ease;
  border-radius: 10px;
  overflow: hidden;
}

.category-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
}

.category-icon {
  background-color: rgba(13, 110, 253, 0.1);
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
}

/* Product Cards */
.product-card {
  transition:
    transform 0.3s ease,
    box-shadow 0.3s ease;
  border-radius: 10px;
  overflow: hidden;
}

.product-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
}

.product-badge {
  position: absolute;
  top: 10px;
  right: 10px;
}

/* How It Works */
.step-card {
  transition: transform 0.3s ease;
}

.step-card:hover {
  transform: translateY(-5px);
}

.icon-circle {
  background-color: rgba(13, 110, 253, 0.1);
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  color: #0d6efd;
}

/* Toast notification */
.toast {
  opacity: 1 !important;
}

/* Pagination */
.pagination .page-link {
  color: #0d6efd;
  cursor: pointer;
}

.pagination .page-item.active .page-link {
  background-color: #0d6efd;
  border-color: #0d6efd;
  color: white;
}

/* Category sidebar */
.list-group-item.active {
  background-color: #0d6efd;
  border-color: #0d6efd;
}

/* Testimonials */
.testimonial-card {
  transition: transform 0.3s ease;
  border-radius: 10px;
}

.testimonial-card:hover {
  transform: translateY(-5px);
}

/* CTA Section */
.cta-card {
  background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
  box-shadow: 0 10px 30px rgba(13, 110, 253, 0.3);
}

.btn {
  border-radius: 5px;
  transition: all 0.3s ease;
}

.btn-primary {
  background-color: #0d6efd;
  border-color: #0d6efd;
}

.btn-primary:hover {
  background-color: #0b5ed7;
  border-color: #0a58ca;
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(13, 110, 253, 0.2);
}

.btn-outline-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 15px rgba(13, 110, 253, 0.2);
}

@media (max-width: 992px) {
  .hero {
    padding: 60px 0;
  }

  .hero-text h1 {
    font-size: 2.5rem;
  }
}

@media (max-width: 768px) {
  .hero {
    padding: 40px 0;
    text-align: center;
  }

  .hero-text h1 {
    font-size: 2rem;
  }

  .hero-text {
    margin-bottom: 2rem;
  }

  .hero .btn-group {
    justify-content: center;
  }
}
</style>
