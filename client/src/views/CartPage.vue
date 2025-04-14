<template>
  <div class="d-flex flex-column min-vh-100">
    <!-- Main Content -->
    <main class="flex-grow-1">
      <div class="cart-container">
        <div class="container py-4 py-md-5">
          <!-- Back Link -->
          <div class="back-link mb-4">
            <router-link to="/" class="text-decoration-none">
              <font-awesome-icon :icon="['fas', 'chevron-left']" class="mr-2" />
              Continue Shopping
            </router-link>
          </div>

          <!-- Initial Loading State -->
          <div v-if="initialLoading" class="text-center py-5">
            <div class="spinner-loader">
              <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
              </div>
            </div>
            <p class="mt-3 loading-text">Loading your cart...</p>
          </div>

          <!-- CRUD Operation Loading Overlay -->
          <div v-if="operationLoading" class="operation-loading-overlay">
            <div class="operation-spinner">
              <div class="spinner-grow text-light" role="status">
                <span class="sr-only">Processing...</span>
              </div>
            </div>
          </div>

          <!-- Error State -->
          <div v-if="error" class="alert alert-danger custom-alert" role="alert">
            <font-awesome-icon :icon="['fas', 'exclamation-circle']" class="mr-2" />
            {{ error }}
            <button type="button" class="close" @click="clearError" aria-label="Close">
              <span>&times;</span>
            </button>
          </div>

          <!-- Empty Cart State -->
          <div v-if="!initialLoading && !error && cartIsEmpty" class="text-center py-5 empty-cart">
            <font-awesome-icon :icon="['fas', 'shopping-cart']" class="empty-icon mb-3" />
            <h2 class="empty-title">Your cart is empty</h2>
            <p class="text-muted empty-text">
              Looks like you haven't added any products to your cart yet.
            </p>
            <router-link to="/" class="btn btn-primary browse-btn mt-3"
              >Browse Products</router-link
            >
          </div>

          <!-- Cart Content -->
          <div v-if="!initialLoading && !error && !cartIsEmpty" class="row">
            <!-- Cart Items Column -->
            <div class="col-lg-8 mb-4 mb-lg-0">
              <div class="cart-header d-flex justify-content-between align-items-center">
                <h1 class="cart-title">Shopping Cart</h1>
                <span class="cart-count">{{ cartItemCountText }}</span>
              </div>
              <hr class="divider" />

              <!-- Cart Items -->
              <div class="cart-items">
                <template v-for="(item, index) in cart" :key="item.id || index">
                  <div class="cart-item">
                    <div class="item-image">
                      <img
                        :src="getProductImage(item)"
                        :alt="getProductTitle(item)"
                        class="product-image"
                      />
                    </div>
                    <div class="item-details">
                      <div class="item-title">
                        <h5>{{ getProductTitle(item) }}</h5>
                      </div>
                      <div class="quantity-control">
                        <button
                          class="quantity-btn decrease"
                          @click="decreaseQuantity(item)"
                          :disabled="item.quantity <= 1 || operationLoading"
                          aria-label="Decrease quantity"
                        >
                          <font-awesome-icon :icon="['fas', 'minus']" />
                        </button>
                        <span class="quantity-text">{{ item.quantity }}</span>
                        <button
                          class="quantity-btn increase"
                          @click="increaseQuantity(item)"
                          :disabled="item.quantity >= 99 || operationLoading"
                          aria-label="Increase quantity"
                        >
                          <font-awesome-icon :icon="['fas', 'plus']" />
                        </button>
                      </div>
                    </div>
                    <div class="item-actions">
                      <button
                        class="remove-btn"
                        @click="removeItem(item)"
                        :disabled="operationLoading"
                        aria-label="Remove from cart"
                      >
                        <font-awesome-icon :icon="['fas', 'trash-alt']" />
                      </button>
                      <div class="item-price">${{ getItemTotal(item).toFixed(2) }}</div>
                    </div>
                  </div>
                  <hr v-if="index !== cart.length - 1" class="item-divider" />
                </template>
              </div>
            </div>

            <!-- Order Summary Column -->
            <div class="col-lg-4">
              <div class="summary-card">
                <div class="summary-header">
                  <h2>Order Summary</h2>
                </div>
                <hr class="divider" />
                <div class="summary-content">
                  <div class="summary-row">
                    <span class="summary-label">Total</span>
                    <span class="summary-value">${{ subtotal.toFixed(2) }}</span>
                  </div>
                </div>
                <button
                  class="checkout-btn"
                  @click="proceedToCheckout"
                  :disabled="initialLoading || operationLoading"
                >
                  <font-awesome-icon :icon="['fas', 'lock']" class="mr-2" />
                  Proceed to Checkout
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { library } from '@fortawesome/fontawesome-svg-core'
import {
  faChevronLeft,
  faExclamationCircle,
  faShoppingCart,
  faMinus,
  faPlus,
  faTrashAlt,
  faLock,
} from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

// Add icons to the library
library.add(faChevronLeft, faExclamationCircle, faShoppingCart, faMinus, faPlus, faTrashAlt, faLock)

const router = useRouter()
const initialLoading = ref(true)
const operationLoading = ref(false)
const error = ref('')
const cart = ref([])

// Computed properties
const cartIsEmpty = computed(() => !cart.value || cart.value.length === 0)
const cartItemCountText = computed(() => {
  return `${cart.value.length} ${cart.value.length === 1 ? 'item' : 'items'}`
})
const subtotal = computed(() => {
  return cart.value.reduce((total, item) => {
    return total + getItemTotal(item)
  }, 0)
})

// Helper function to get the current user ID
const getUserId = async () => {
  try {
    const userResponse = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/auth/login.controller.php`,
      {
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        },
      },
    )
    const userData = await userResponse.json()
    const userId = userData?.data?.id

    if (!userId) {
      throw new Error('User not logged in')
    }

    return userId
  } catch (err) {
    throw new Error('Failed to get user ID: ' + (err.message || 'Unknown error'))
  }
}

// Fetch cart data
const fetchCart = async () => {
  initialLoading.value = true
  error.value = ''

  try {
    const userId = await getUserId()
    console.log('User ID:', userId)

    // Fetch cart data
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/cart.controller.php/${userId}`,
    )

    if (!response.ok) {
      throw new Error(`Server error: ${response.status}`)
    }

    const text = await response.text()
    console.log('Response text:', text)

    // Try to parse as JSON if possible
    let data
    try {
      data = JSON.parse(text)
      cart.value = data.data || []
    } catch (e) {
      console.error('JSON parsing error:', e)
      throw new Error('Server returned invalid JSON')
    }
  } catch (err) {
    error.value = err.message || 'An error occurred while loading your cart'
  } finally {
    initialLoading.value = false
  }
}

// Helper methods
const getProductImage = (item) => {
  return item.product?.image || '/assets/images/placeholder.jpg'
}

const getProductTitle = (item) => {
  return item.product?.name || 'Unknown Product'
}

const getItemTotal = (item) => {
  return item.quantity * (item.product?.price || 0)
}

// Helper function to update cart quantity
const updateQuantity = async (item, newQuantity) => {
  if (newQuantity < 1 || newQuantity > 99) return

  operationLoading.value = true
  try {
    const userId = await getUserId()
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/cart.controller.php/${userId}`,
      {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          product_id: item.product_id,
          quantity: newQuantity,
        }),
      },
    )

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}))
      throw new Error(errorData.message || `Failed to update quantity (${response.status})`)
    }

    // Update local state
    item.quantity = newQuantity
  } catch (err) {
    error.value = err.message || 'Failed to update item quantity'
  } finally {
    operationLoading.value = false
  }
}

// Simplified wrapper functions
const increaseQuantity = (item) => {
  if (item.quantity >= 99) return
  updateQuantity(item, item.quantity + 1)
}

const decreaseQuantity = (item) => {
  if (item.quantity <= 1) return
  updateQuantity(item, item.quantity - 1)
}

const removeItem = async (item) => {
  operationLoading.value = true
  try {
    const userId = await getUserId()
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/cart.controller.php/${userId}`,
      {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          product_id: item.product_id,
        }),
      },
    )

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}))
      throw new Error(errorData.message || `Failed to remove item (${response.status})`)
    }

    // Update local state - filter by product_id since that's what the backend uses
    cart.value = cart.value.filter((cartItem) => cartItem.product_id !== item.product_id)
  } catch (err) {
    error.value = err.message || 'Failed to remove item'
  } finally {
    operationLoading.value = false
  }
}

const clearError = () => {
  error.value = ''
}

const proceedToCheckout = () => {
  router.push('/checkout')
}

onMounted(async () => {
  try {
    // Try to get the user ID first to verify authentication
    await getUserId()

    // If successful (no error thrown), fetch the cart
    fetchCart()
  } catch (err) {
    // User is not authenticated, redirect to home page
    router.push('/')

    // Optionally show a message about the redirect
    // You could use a toast notification here if you have one
    console.log('User not authenticated. Redirected to home page.')
  }
})
</script>

<style scoped>
.cart-container {
  background-color: #f8f9fa;
  min-height: 70vh;
}

.back-link a {
  color: #495057;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
}

.back-link a:hover {
  color: #0d6efd;
}

.spinner-loader {
  display: flex;
  justify-content: center;
}

.loading-text {
  color: #6c757d;
}

.operation-loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1050;
}

.empty-icon {
  font-size: 3rem;
  color: #adb5bd;
}

.empty-title {
  color: #343a40;
  margin-bottom: 1rem;
}

.empty-text {
  font-size: 1.1rem;
}

.cart-title {
  font-size: 1.5rem;
  font-weight: 600;
}

.cart-count {
  color: #6c757d;
  font-size: 1rem;
}

.divider {
  margin-top: 1rem;
  margin-bottom: 1rem;
  border-color: #dee2e6;
}

.cart-item {
  display: flex;
  padding: 1rem 0;
}

.item-image {
  width: 120px;
  margin-right: 1rem;
}

.product-image {
  width: 100%;
  height: auto;
  border-radius: 4px;
  object-fit: cover;
}

.item-details {
  flex-grow: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.item-title h5 {
  margin-bottom: 0.5rem;
  font-weight: 500;
}

.quantity-control {
  display: flex;
  align-items: center;
}

.quantity-btn {
  background-color: #f8f9fa;
  border: 1px solid #ced4da;
  border-radius: 4px;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.quantity-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.quantity-text {
  margin: 0 0.75rem;
  min-width: 2rem;
  text-align: center;
}

.item-actions {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  align-items: flex-end;
}

.remove-btn {
  background: none;
  border: none;
  color: #dc3545;
  cursor: pointer;
  padding: 0.25rem;
}

.remove-btn:hover {
  color: #c82333;
}

.remove-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.item-price {
  font-weight: 600;
  font-size: 1.1rem;
}

.item-divider {
  margin: 0;
  border-color: #e9ecef;
}

.summary-card {
  background-color: white;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.summary-header h2 {
  font-size: 1.25rem;
  font-weight: 600;
  margin-bottom: 0;
}

.summary-content {
  padding: 1rem 0;
}

.summary-row {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.summary-label {
  font-weight: 500;
}

.summary-value {
  font-weight: 600;
}

.checkout-btn {
  width: 100%;
  background-color: #0d6efd;
  color: white;
  border: none;
  border-radius: 4px;
  padding: 0.75rem;
  font-weight: 500;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
}

.checkout-btn:hover {
  background-color: #0b5ed7;
}

.checkout-btn:disabled {
  background-color: #6c757d;
  cursor: not-allowed;
}

/* Add proper styles for sr-only to hide text visually but keep it for screen readers */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border-width: 0;
}

/* Spacing for icons */
.mr-2 {
  margin-right: 0.5rem;
}
</style>
