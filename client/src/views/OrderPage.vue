<template>
  <div class="d-flex flex-column min-vh-100">
    <!-- Main Content -->
    <main class="flex-grow-1">
      <div class="orders-container">
        <div class="container py-4 py-md-5">
          <!-- Back Link -->
          <div class="back-link mb-4">
            <router-link to="/" class="text-decoration-none">
              <font-awesome-icon :icon="['fas', 'chevron-left']" class="mr-2" />
              Back to Homepage
            </router-link>
          </div>

          <!-- Initial Loading State -->
          <div v-if="initialLoading" class="text-center py-5">
            <div class="spinner-loader">
              <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
              </div>
            </div>
            <p class="mt-3 loading-text">Loading your orders...</p>
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
          <div v-if="error && !initialLoading" class="alert alert-danger custom-alert" role="alert">
            <font-awesome-icon :icon="['fas', 'exclamation-circle']" class="mr-2" />
            {{ error }}
            <button type="button" class="close" @click="clearError" aria-label="Close">
              <span>&times;</span>
            </button>
          </div>

          <!-- Empty Orders State -->
          <div v-if="!initialLoading && !error && ordersIsEmpty" class="text-center py-5 empty-orders">
            <font-awesome-icon :icon="['fas', 'shopping-basket']" class="empty-icon mb-3" />
            <h2 class="empty-title">No orders found</h2>
            <p class="text-muted empty-text">
              You haven't placed any orders yet.
            </p>
            <router-link to="/" class="btn btn-primary browse-btn mt-3">Browse Products</router-link>
          </div>

          <!-- Orders Content -->
          <div v-if="!initialLoading && !error && !ordersIsEmpty">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h1 class="orders-title">My Orders</h1>
              <span class="orders-count">{{ orders.length }} {{ orders.length === 1 ? 'order' : 'orders' }}</span>
            </div>

            <!-- Filter Section -->
            <div class="filter-section bg-light p-3 mb-4 rounded">
              <div class="row g-3">
                <div class="col-md-4">
                  <label for="startDate" class="form-label">From Date</label>
                  <input
                    type="date"
                    class="form-control"
                    id="startDate"
                    v-model="startDate"
                  />
                </div>
                <div class="col-md-4">
                  <label for="endDate" class="form-label">To Date</label>
                  <input
                    type="date"
                    class="form-control"
                    id="endDate"
                    v-model="endDate"
                  />
                </div>
                <div class="col-md-4 d-flex align-items-end">
                  <div class="d-flex gap-2">
                    <button class="btn btn-primary" @click="applyFilters">
                      <font-awesome-icon :icon="['fas', 'search']" class="mr-2" />
                      Filter
                    </button>
                    <button class="btn btn-outline-secondary" @click="clearFilters">
                      <font-awesome-icon :icon="['fas', 'times-circle']" class="mr-2" />
                      Clear
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Orders List -->
            <div class="orders-list">
              <div v-for="order in orders" :key="order.id" class="order-card mb-4">
                <div class="order-header d-flex justify-content-between align-items-center">
                  <div>
                    <span class="order-number">Order #{{ order.id }}</span>
                    <span class="order-date">{{ formatDate(order.created_at) }}</span>
                  </div>
                  <span class="badge" :class="getStatusBadgeClass(order.status)">
                    {{ order.status.charAt(0).toUpperCase() + order.status.slice(1) }}
                  </span>
                </div>

                <div class="order-body">
                  <div class="order-products">
                    <h5 class="products-title">Order Items</h5>
                    <div class="table-responsive">
                      <table class="table table-sm">
                        <thead class="table-light">
                        <tr>
                          <th>Product</th>
                          <th class="text-center">Qty</th>
                          <th class="text-right">Price</th>
                          <th class="text-right">Subtotal</th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr v-for="product in order.products" :key="product.id">
                          <td>{{ product.product_name }}</td>
                          <td class="text-center">{{ product.quantity }}</td>
                          <td class="text-right">${{ product.price }}</td>
                          <td class="text-right">${{ (product.price * product.quantity).toFixed(2) }}</td>
                        </tr>
                        </tbody>
                        <tfoot>
                        <tr>
                          <td colspan="3" class="text-right"><strong>Total:</strong></td>
                          <td class="text-right"><strong>${{ order.total_price }}</strong></td>
                        </tr>
                        </tfoot>
                      </table>
                    </div>
                  </div>

                  <div class="order-details">
                    <div class="order-info">
                      <div class="info-row">
                        <span class="info-label">Room:</span>
                        <span class="info-value">{{ order.room ? order.room.name : `Room #${order.room_id}` }}</span>
                      </div>
                      <div class="info-row">
                        <span class="info-label">Notes:</span>
                        <span class="info-value">{{ order.notes || 'No notes' }}</span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="order-footer">
                  <button
                    v-if="order.status === 'processing'"
                    class="btn btn-danger cancel-btn"
                    @click="openCancelModal(order)"
                    :disabled="operationLoading"
                  >
                    <font-awesome-icon :icon="['fas', 'times-circle']" class="mr-2" />
                    Cancel Order
                  </button>
                </div>
              </div>
            </div>

            <!-- Pagination -->
            <div
              v-if="totalPages > 1"
              class="d-flex justify-content-between align-items-center px-3 py-2"
            >
              <div>Showing {{ orders.length }} of {{ totalItems }} orders</div>
              <nav aria-label="Orders pagination">
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
        </div>
      </div>
    </main>

    <!-- Cancel Order Modal -->
    <div
      class="modal fade"
      id="cancelOrderModal"
      tabindex="-1"
      aria-labelledby="cancelOrderModalLabel"
      aria-hidden="true"
      ref="cancelOrderModalRef"
    >
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="cancelOrderModalLabel">Cancel Order</h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body">
            <p>
              Are you sure you want to cancel Order #{{ orderToCancel?.id }}?
            </p>
            <p class="text-danger"><small>This action cannot be undone.</small></p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep it</button>
            <button type="button" class="btn btn-danger" @click="cancelOrder">Yes, Cancel Order</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { Modal } from 'bootstrap'
import { library } from '@fortawesome/fontawesome-svg-core'
import {
  faChevronLeft,
  faExclamationCircle,
  faShoppingBasket,
  faTimesCircle,
  faSearch,
  faTimes
} from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

library.add(
  faChevronLeft,
  faExclamationCircle,
  faShoppingBasket,
  faTimesCircle,
  faSearch,
  faTimes
)

const router = useRouter()
const initialLoading = ref(true)
const operationLoading = ref(false)
const error = ref('')
const orders = ref([])

// Pagination
const currentPage = ref(1)
const totalPages = ref(1)
const totalItems = ref(0)
const limit = ref(5)

// Filters
const startDate = ref('')
const endDate = ref('')

// Cancel order modal
const cancelOrderModalRef = ref(null)
let cancelOrderModalInstance = null
const orderToCancel = ref(null)

// Computed properties
const ordersIsEmpty = computed(() => !orders.value || orders.value.length === 0)

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

const fetchOrders = async () => {
  initialLoading.value = true
  error.value = ''

  try {
    const userId = await getUserId()

    const params = new URLSearchParams()
    params.append('page', currentPage.value)
    params.append('limit', limit.value)
    params.append('user_id', userId)

    if (startDate.value && endDate.value) {
      params.append('start_date', `${startDate.value} 00:00:00`)
      params.append('end_date', `${endDate.value} 23:59:59`)
    }

    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/order.controller.php?${params.toString()}`
    )

    if (!response.ok) {
      throw new Error(`Server error: ${response.status}`)
    }

    const data = await response.json()

    if (data && data.data) {
      orders.value = data.data.data || []
      totalPages.value = data.data.total_pages || 1
      totalItems.value = data.data.total || 0
      currentPage.value = data.data.current_page || 1
    } else {
      orders.value = []
      totalPages.value = 1
      totalItems.value = 0
    }
  } catch (err) {
    error.value = err.message || 'An error occurred while loading your orders'
    orders.value = []
  } finally {
    initialLoading.value = false
  }
}

function formatDate(dateString) {
  const date = new Date(dateString)
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  }).format(date)
}

function getStatusBadgeClass(status) {
  const statusClasses = {
    'processing': 'bg-warning',
    'delivered': 'bg-info',
    'done': 'bg-success',
    'canceled': 'bg-danger'
  }
  return statusClasses[status] || 'bg-secondary'
}

function openCancelModal(order) {
  orderToCancel.value = order
  if (cancelOrderModalInstance) {
    cancelOrderModalInstance.show()
  } else {
    const modalElement = document.getElementById('cancelOrderModal')
    if (modalElement) new Modal(modalElement).show()
  }
}

async function cancelOrder() {
  if (!orderToCancel.value) return

  operationLoading.value = true
  try {
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/order.controller.php/${orderToCancel.value.id}`,
      {
        method: 'DELETE'
      }
    )

    const result = await response.json()
    if (result && result.statusCode === 200) {
      if (cancelOrderModalInstance) {
        cancelOrderModalInstance.hide()
      }
      await fetchOrders()
    } else {
      error.value = result.message || 'Failed to cancel order.'
    }
  } catch (err) {
    error.value = `Error canceling order: ${err.message}`
  } finally {
    operationLoading.value = false
    orderToCancel.value = null
  }
}

function changePage(page) {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  fetchOrders()
}

function applyFilters() {
  currentPage.value = 1
  fetchOrders()
}

function clearFilters() {
  startDate.value = ''
  endDate.value = ''
  currentPage.value = 1
  fetchOrders()
}

function clearError() {
  error.value = ''
}

onMounted(async () => {
  try {
    await getUserId()
    await fetchOrders()

    await nextTick()
    if (cancelOrderModalRef.value) {
      cancelOrderModalInstance = new Modal(cancelOrderModalRef.value)
    }
  } catch (err) {
    error.value = 'You must be logged in to view your orders'
    setTimeout(() => {
      router.push('/')
    }, 2000)
  }
})
</script>

<style scoped>
.orders-container {
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

.orders-title {
  font-size: 1.5rem;
  font-weight: 600;
}

.orders-count {
  color: #6c757d;
  font-size: 1rem;
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

.order-card {
  background-color: white;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.order-header {
  padding: 1rem;
  background-color: #f8f9fa;
  border-bottom: 1px solid #e9ecef;
}

.order-number {
  font-weight: 600;
  margin-right: 1rem;
}

.order-date {
  color: #6c757d;
  font-size: 0.9rem;
}

.badge {
  padding: 0.5em 0.75em;
  font-weight: 500;
}

.badge.bg-warning {
  background-color: #ffc107 !important;
  color: #212529;
}

.badge.bg-info {
  background-color: #0dcaf0 !important;
}

.badge.bg-success {
  background-color: #198754 !important;
}

.badge.bg-danger {
  background-color: #dc3545 !important;
}

.order-body {
  padding: 1rem;
}

.products-title {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 0.75rem;
}

.order-details {
  margin-top: 1rem;
}

.info-row {
  margin-bottom: 0.5rem;
}

.info-label {
  font-weight: 500;
  margin-right: 0.5rem;
  min-width: 60px;
  display: inline-block;
}

.order-footer {
  padding: 1rem;
  border-top: 1px solid #e9ecef;
  display: flex;
  justify-content: flex-end;
}

.cancel-btn {
  padding: 0.375rem 0.75rem;
}

.pagination {
  cursor: pointer;
}

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

/* Table text alignment */
.text-right {
  text-align: right;
}

.text-center {
  text-align: center;
}

/* Filter section */
.filter-section {
  margin-bottom: 1.5rem;
}

.gap-2 {
  gap: 0.5rem;
}
</style>
