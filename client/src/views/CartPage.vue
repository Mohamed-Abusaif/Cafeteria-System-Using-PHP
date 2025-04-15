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

          <!-- Success Message Toast -->
          <div v-if="successMessage" class="alert alert-success custom-alert" role="alert">
            <font-awesome-icon :icon="['fas', 'check-circle']" class="mr-2" />
            {{ successMessage }}
            <button type="button" class="close" @click="clearSuccess" aria-label="Close">
              <span>&times;</span>
            </button>
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
                  :disabled="initialLoading || operationLoading || cartIsEmpty"
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

    <!-- Checkout Modal -->
    <div v-if="showCheckoutModal" class="checkout-modal-overlay">
      <div class="checkout-modal">
        <div class="checkout-modal-header">
          <h2>Complete Your Order</h2>
          <button class="close-modal-btn" @click="closeCheckoutModal" aria-label="Close">
            <span>&times;</span>
          </button>
        </div>

        <div class="checkout-modal-body">
          <!-- Error Message -->
          <div v-if="error" class="alert alert-danger checkout-error" role="alert">
            <font-awesome-icon :icon="['fas', 'exclamation-circle']" class="mr-2" />
            {{ error }}
          </div>

          <!-- Admin User Selection (Only visible to admins) -->
          <div v-if="isAdmin" class="admin-controls mb-4">
            <div class="form-group">
              <label for="user-id" class="form-label">
                Place order for User ID
                <span class="required">*</span>
              </label>
              <input
                id="user-id"
                v-model="checkoutForm.targetUserId"
                class="form-control"
                type="number"
                placeholder="Enter user ID"
                :disabled="checkoutLoading"
                required
              />
            </div>
          </div>

          <!-- Order Summary -->
          <div class="checkout-summary mb-4">
            <h3>Order Summary</h3>
            <div class="checkout-items">
              <div v-for="(item, index) in cart" :key="item.id || index" class="checkout-item">
                <span class="checkout-item-name">{{ getProductTitle(item) }}</span>
                <span class="checkout-item-quantity">x{{ item.quantity }}</span>
                <span class="checkout-item-price">${{ getItemTotal(item).toFixed(2) }}</span>
              </div>
            </div>
            <div class="checkout-total">
              <span class="checkout-total-label">Total:</span>
              <span class="checkout-total-value">${{ subtotal.toFixed(2) }}</span>
            </div>
          </div>

          <!-- Checkout Form -->
          <div class="checkout-form">
            <div class="form-group mb-3">
              <label for="room-select" class="form-label">Delivery Room <span class="required">*</span></label>
              <div v-if="roomsLoading" class="text-center py-2">
                <div class="spinner-border spinner-border-sm" role="status">
                  <span class="sr-only">Loading rooms...</span>
                </div>
                <span class="ml-2">Loading available rooms...</span>
              </div>
              <select
                id="room-select"
                v-model="checkoutForm.roomId"
                class="form-control"
                :disabled="checkoutLoading || roomsLoading"
                required
                v-else
              >
                <option value="" disabled>Select a room</option>
                <option v-for="room in rooms" :key="room.id" :value="room.id">
                  {{ room.name }}
                </option>
              </select>
              <small v-if="rooms.length === 0 && !roomsLoading" class="text-danger">
                No rooms available. Please contact support.
              </small>
            </div>

            <div class="form-group mb-4">
              <label for="notes" class="form-label">Additional Notes</label>
              <textarea
                id="notes"
                v-model="checkoutForm.notes"
                class="form-control"
                placeholder="Any special requests for your order?"
                rows="3"
                :disabled="checkoutLoading"
              ></textarea>
            </div>
          </div>
        </div>

        <div class="checkout-modal-footer">
          <button
            class="btn btn-secondary cancel-btn"
            @click="closeCheckoutModal"
            :disabled="checkoutLoading"
          >
            Cancel
          </button>
          <button
            class="btn btn-primary confirm-btn"
            @click="submitOrder"
            :disabled="checkoutLoading || !checkoutForm.roomId || roomsLoading || (isAdmin && !checkoutForm.targetUserId)"
          >
            <span v-if="checkoutLoading">
              <div class="spinner-border spinner-border-sm" role="status">
                <span class="sr-only">Processing...</span>
              </div>
              Processing...
            </span>
            <span v-else>
              <font-awesome-icon :icon="['fas', 'lock']" class="mr-2" />
              Confirm Order
            </span>
          </button>
        </div>
      </div>
    </div>
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
  faCheckCircle,
  faUserShield,
} from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

library.add(
  faChevronLeft,
  faExclamationCircle,
  faShoppingCart,
  faMinus,
  faPlus,
  faTrashAlt,
  faLock,
  faCheckCircle,
  faUserShield
)

const router = useRouter()
const initialLoading = ref(true)
const operationLoading = ref(false)
const error = ref('')
const successMessage = ref('')
const cart = ref([])
const isAdmin = ref(false)
const currentUserId = ref(null)

const showCheckoutModal = ref(false)
const checkoutForm = ref({
  roomId: '',
  notes: '',
  targetUserId: ''
})
const checkoutLoading = ref(false)
const rooms = ref([])
const roomsLoading = ref(false)

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

// Helper function to get the current user ID and check if they're an admin
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

    // Check if user is admin
    isAdmin.value = userData?.data?.role === 'Admin'
    currentUserId.value = userId

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
      {
        method: 'GET',
        credentials: 'include',
      }
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

const fetchRooms = async () => {
  roomsLoading.value = true
  try {
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/room.controller.php`,
      {
        method: 'GET',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        },
      },
    )

    if (!response.ok) {
      throw new Error(`Failed to fetch rooms: ${response.status}`)
    }

    const data = await response.json()
    rooms.value = data.data || []
  } catch (err) {
    console.error('Error fetching rooms:', err)
    error.value = 'Failed to load available rooms. Please try again.'
  } finally {
    roomsLoading.value = false
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
        credentials: 'include',
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

    successMessage.value = 'Quantity updated successfully'
    setTimeout(() => { successMessage.value = '' }, 3000) // Clear after 3 seconds
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
        credentials: 'include',
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

    successMessage.value = 'Item removed from cart'
    setTimeout(() => { successMessage.value = '' }, 3000) // Clear after 3 seconds
  } catch (err) {
    error.value = err.message || 'Failed to remove item'
  } finally {
    operationLoading.value = false
  }
}

const clearError = () => {
  error.value = ''
}

const clearSuccess = () => {
  successMessage.value = ''
}

const proceedToCheckout = async () => {
  // Reset form
  checkoutForm.value = {
    roomId: '',
    notes: '',
    targetUserId: isAdmin.value ? '' : currentUserId.value
  }

  error.value = ''

  if (rooms.value.length === 0) {
    await fetchRooms()
  }

  showCheckoutModal.value = true
}

const submitOrder = async () => {
  if (!checkoutForm.value.roomId) {
    error.value = 'Please select a room for delivery'
    return
  }

  if (isAdmin.value && !checkoutForm.value.targetUserId) {
    error.value = 'Please enter a user ID to place this order for'
    return
  }

  checkoutLoading.value = true
  try {
    const userId = isAdmin.value && checkoutForm.value.targetUserId
      ? checkoutForm.value.targetUserId
      : await getUserId()

    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/order.controller.php`,
      {
        method: 'POST',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          Admin_id: isAdmin.value ? parseInt(currentUserId.value, 10) : null,
          user_id: parseInt(userId, 10),
          room_id: parseInt(checkoutForm.value.roomId, 10),
          notes: checkoutForm.value.notes || null,
        }),
      }
    )

    if (!response.ok) {
      const errorData = await response.json().catch(() => ({}))
      throw new Error(errorData.message || `Failed to create order (${response.status})`)
    }

    const orderData = await response.json()
    console.log('Order created:', orderData)

    showCheckoutModal.value = false

    if (isAdmin.value && checkoutForm.value.targetUserId != currentUserId.value) {
      successMessage.value = `Order successfully placed for user ID: ${checkoutForm.value.targetUserId}`
      setTimeout(() => {
        successMessage.value = ''
        router.push('/dashboard/orders')
      }, 2000)
    } else {
      successMessage.value = 'Your order has been placed successfully!'
      setTimeout(() => {
        successMessage.value = ''
        router.push('/orders')
      }, 2000)
    }

  } catch (err) {
    error.value = err.message || 'Failed to create order'
  } finally {
    checkoutLoading.value = false
  }
}

const closeCheckoutModal = () => {
  showCheckoutModal.value = false
  error.value = ''
}

onMounted(async () => {
  try {
    // Try to get the user ID first to verify authentication
    await getUserId()

    // If successful (no error thrown), fetch the cart
    fetchCart()
    fetchRooms()

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

/* Custom alert styles with animation */
.custom-alert {
  position: relative;
  padding: 1rem;
  border-radius: 6px;
  box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
  margin-bottom: 1.5rem;
  animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(-10px); }
  to { opacity: 1; transform: translateY(0); }
}

.alert-success {
  background-color: #d4edda;
  border-color: #c3e6cb;
  color: #155724;
}

.close {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  background: none;
  border: none;
  cursor: pointer;
  font-size: 1.25rem;
  color: inherit;
  opacity: 0.7;
}

.close:hover {
  opacity: 1;
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

.ml-2 {
  margin-left: 0.5rem;
}

/* Checkout Modal Styles */
.checkout-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1060;
  animation: fadeIn 0.2s ease-out;
}

.checkout-modal {
  background-color: white;
  border-radius: 8px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
  from { transform: translateY(20px); opacity: 0; }
  to { transform: translateY(0); opacity: 1; }
}

.checkout-modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e9ecef;
}

.checkout-modal-header h2 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
}

.close-modal-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  padding: 0;
  cursor: pointer;
  color: #6c757d;
}

.checkout-modal-body {
  padding: 1.5rem;
}

.checkout-error {
  margin-bottom: 1rem;
}

.checkout-summary {
  background-color: #f8f9fa;
  border-radius: 6px;
  padding: 1rem;
}

.checkout-summary h3 {
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 1rem;
}

.checkout-items {
  margin-bottom: 1rem;
}

.checkout-item {
  display: flex;
  justify-content: space-between;
  margin-bottom: 0.5rem;
}

.checkout-item-name {
  flex-grow: 1;
}

.checkout-item-quantity {
  margin: 0 1rem;
  color: #6c757d;
}

.checkout-item-price {
  font-weight: 500;
}

.checkout-total {
  display: flex;
  justify-content: space-between;
  border-top: 1px solid #dee2e6;
  padding-top: 0.75rem;
  margin-top: 0.75rem;
  font-weight: 600;
}

.checkout-form {
  margin-top: 1rem;
}

.form-label {
  font-weight: 500;
  display: block;
  margin-bottom: 0.5rem;
}

.required {
  color: #dc3545;
}

.form-control {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #ced4da;
  border-radius: 4px;
  font-size: 1rem;
  line-height: 1.5;
}

.form-control:focus {
  border-color: #86b7fe;
  outline: 0;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

textarea.form-control {
  resize: vertical;
}

.checkout-modal-footer {
  display: flex;
  justify-content: flex-end;
  padding: 1.5rem;
  border-top: 1px solid #e9ecef;
}

.cancel-btn {
  margin-right: 0.75rem;
  padding: 0.5rem 1.25rem;
  background-color: #6c757d;
  color: white;
  border: none;
  border-radius: 4px;
  font-weight: 500;
}

.cancel-btn:hover {
  background-color: #5a6268;
}

.confirm-btn {
  padding: 0.5rem 1.25rem;
  background-color: #0d6efd;
  color: white;
  border: none;
  border-radius: 4px;
  font-weight: 500;
  display: flex;
  align-items: center;
}

.confirm-btn:hover {
  background-color: #0b5ed7;
}

.confirm-btn:disabled {
  background-color: #6c757d;
  cursor: not-allowed;
}

/* Responsive styles */
@media (max-width: 767.98px) {
  .cart-item {
    flex-direction: column;
  }

  .item-image {
    width: 100%;
    margin-right: 0;
    margin-bottom: 1rem;
  }

  .product-image {
    max-height: 200px;
    width: auto;
    display: block;
    margin: 0 auto;
  }

  .item-details {
    margin-bottom: 1rem;
  }

  .item-actions {
    flex-direction: row;
    justify-content: space-between;
    width: 100%;
  }

  .remove-btn {
    padding: 0.5rem;
  }
}

@media (max-width: 575.98px) {
  .checkout-modal {
    width: 95%;
    max-height: 95vh;
  }

  .checkout-modal-header {
    padding: 1rem;
  }

  .checkout-modal-body,
  .checkout-modal-footer {
    padding: 1rem;
  }

  .checkout-item {
    flex-wrap: wrap;
  }

  .checkout-item-name {
    width: 100%;
    margin-bottom: 0.25rem;
  }

  .checkout-item-quantity,
  .checkout-item-price {
    margin: 0;
  }

  .checkout-modal-footer {
    flex-direction: column;
  }

  .cancel-btn {
    margin-right: 0;
    margin-bottom: 0.5rem;
    width: 100%;
  }

  .confirm-btn {
    width: 100%;
    justify-content: center;
  }
}

/* Animations for button interactions */
.quantity-btn:active,
.remove-btn:active,
.checkout-btn:active,
.cancel-btn:active,
.confirm-btn:active {
  transform: scale(0.97);
}

/* Hover effects */
.browse-btn:hover {
  background-color: #0b5ed7;
}

/* Focus styles for accessibility */
.form-control:focus,
.quantity-btn:focus,
.remove-btn:focus,
.checkout-btn:focus,
.close-modal-btn:focus,
.browse-btn:focus,
.cancel-btn:focus,
.confirm-btn:focus {
  outline: 2px solid #86b7fe;
  outline-offset: 2px;
}

</style>
