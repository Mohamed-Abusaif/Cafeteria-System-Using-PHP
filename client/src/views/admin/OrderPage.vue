<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { Modal } from 'bootstrap'

const orders = ref([])
const isLoading = ref(true)
const error = ref(null)

// Pagination
const currentPage = ref(1)
const totalPages = ref(1)
const totalItems = ref(0)
const limit = ref(5)

// Filters
const statusFilter = ref('')
const userIdFilter = ref('')

// Order details modal
const orderDetailsModalRef = ref(null)
let orderDetailsModalInstance = null
const selectedOrder = ref(null)

// Update status modal
const updateStatusModalRef = ref(null)
let updateStatusModalInstance = null
const orderToUpdate = ref(null)
const newStatus = ref('')
const updateError = ref('')

// Cancel order modal
const cancelOrderModalRef = ref(null)
let cancelOrderModalInstance = null
const orderToCancel = ref(null)

async function fetchOrders() {
  isLoading.value = true
  error.value = null
  try {
    const params = new URLSearchParams()
    params.append('page', currentPage.value)
    params.append('limit', limit.value)

    if (statusFilter.value) params.append('status', statusFilter.value)
    if (userIdFilter.value) params.append('user_id', userIdFilter.value)

    const url = `${import.meta.env.VITE_SERVER_URL}/controllers/order.controller.php?${params.toString()}`
    const response = await fetch(url, {
      method: 'GET',
      credentials: 'include',
    })

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`)

    const data = await response.json()
    if (data && data.data) {
      orders.value = data.data.data || []
      totalPages.value = data.data.total_pages || 1
      totalItems.value = data.data.total || 0
      currentPage.value = data.data.current_page || 1
    } else {
      if (response.ok && data && Array.isArray(data.data)) {
        orders.value = []
      } else {
        throw new Error(data.message || 'Failed to parse orders data.')
      }
    }
  } catch (err) {
    error.value = err.message
    orders.value = []
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  await fetchOrders()
  await nextTick()

  if (orderDetailsModalRef.value) {
    orderDetailsModalInstance = new Modal(orderDetailsModalRef.value)
  }
  if (updateStatusModalRef.value) {
    updateStatusModalInstance = new Modal(updateStatusModalRef.value)
  }
  if (cancelOrderModalRef.value) {
    cancelOrderModalInstance = new Modal(cancelOrderModalRef.value)
  }
})

function openOrderDetailsModal(order) {
  selectedOrder.value = order
  if (orderDetailsModalInstance) {
    orderDetailsModalInstance.show()
  } else {
    const modalElement = document.getElementById('orderDetailsModal')
    if (modalElement) new Modal(modalElement).show()
  }
}

function openUpdateStatusModal(order) {
  orderToUpdate.value = order
  newStatus.value = getNextStatus(order.status)
  updateError.value = ''
  if (updateStatusModalInstance) {
    updateStatusModalInstance.show()
  } else {
    const modalElement = document.getElementById('updateStatusModal')
    if (modalElement) new Modal(modalElement).show()
  }
}

function openCancelOrderModal(order) {
  orderToCancel.value = order
  if (cancelOrderModalInstance) {
    cancelOrderModalInstance.show()
  } else {
    const modalElement = document.getElementById('cancelOrderModal')
    if (modalElement) new Modal(modalElement).show()
  }
}

function getNextStatus(currentStatus) {
  const statusFlow = {
    'processing': 'delivered',
    'delivered': 'done',
    'done': 'done'
  }
  return statusFlow[currentStatus] || currentStatus
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

async function updateOrderStatus() {
  if (!orderToUpdate.value) return

  try {
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/order.controller.php/${orderToUpdate.value.id}`,
      {
        method: 'PATCH',
        credentials: 'include',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ status: newStatus.value })
      }
    )

    const result = await response.json()
    if (result && result.statusCode === 200) {
      if (updateStatusModalInstance) {
        updateStatusModalInstance.hide()
      }
      await fetchOrders()
    } else {
      updateError.value = result.message || 'Failed to update order status.'
    }
  } catch (err) {
    updateError.value = `Error updating order status: ${err.message}`
  }
}

async function cancelOrder() {
  if (!orderToCancel.value) return

  try {
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/order.controller.php/${orderToCancel.value.id}`,
      {
        method: 'DELETE',
        credentials: 'include',
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
    orderToCancel.value = null
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
  statusFilter.value = ''
  userIdFilter.value = ''
  currentPage.value = 1
  fetchOrders()
}

</script>

<template>
  <div class="full-width-container mt-2">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-3 px-3">
      <h2 class="mb-0">Orders Management</h2>
    </div>

    <!-- Filters Section -->
    <div class="bg-light p-3 mb-3 rounded">
      <div class="row g-3">
        <div class="col-md-4">
          <label for="statusFilter" class="form-label">Status</label>
          <select class="form-select" id="statusFilter" v-model="statusFilter">
            <option value="">All Statuses</option>
            <option value="processing">Processing</option>
            <option value="delivered">Delivered</option>
            <option value="done">Done</option>
            <option value="canceled">Canceled</option>
          </select>
        </div>
        <div class="col-md-4">
          <label for="userIdFilter" class="form-label">User ID</label>
          <input
            type="text"
            class="form-control"
            id="userIdFilter"
            v-model="userIdFilter"
            placeholder="Filter by user ID..."
          />
        </div>
        <div class="col-md-4 d-flex align-items-end">
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
      Failed to load orders: {{ error }}
    </div>

    <!-- Table Section -->
    <div v-else class="full-width-table px-3">
      <div v-if="!orders || orders.length === 0" class="alert alert-info mx-3">
        No orders found. Try adjusting your filters.
      </div>
      <table v-else class="table table-hover mb-0">
        <thead class="table-light">
        <tr>
          <th scope="col" style="width: 5%">#</th>
          <th scope="col" style="width: 15%">Date</th>
          <th scope="col" style="width: 15%">User</th>
          <th scope="col" style="width: 10%">Room</th>
          <th scope="col" style="width: 10%">Total</th>
          <th scope="col" style="width: 10%">Status</th>
          <th scope="col" style="width: 35%">Actions</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="order in orders" :key="order.id">
          <th scope="row">{{ order.id }}</th>
          <td>{{ formatDate(order.created_at) }}</td>
          <td>{{ order.user ? order.user.name : `User #${order.user_id}` }}</td>
          <td>{{ order.room ? order.room.name : `Room #${order.room_id}` }}</td>
          <td>${{ order.total_price }}</td>
          <td>
              <span class="badge" :class="getStatusBadgeClass(order.status)">
                {{ order.status.charAt(0).toUpperCase() + order.status.slice(1) }}
              </span>
          </td>
          <td>
            <div class="btn-group">
              <button type="button" class="btn btn-sm btn-info me-1" @click="openOrderDetailsModal(order)">
                <i class="bi bi-eye"></i> Details
              </button>
              <button
                v-if="order.status !== 'done' && order.status !== 'canceled'"
                type="button"
                class="btn btn-sm btn-success me-1"
                @click="openUpdateStatusModal(order)"
              >
                <i class="bi bi-arrow-right-circle"></i> Update Status
              </button>
              <button
                v-if="order.status === 'processing'"
                type="button"
                class="btn btn-sm btn-danger"
                @click="openCancelOrderModal(order)"
              >
                <i class="bi bi-x-circle"></i> Cancel
              </button>
            </div>
          </td>
        </tr>
        </tbody>
      </table>

      <!-- Pagination -->
      <div
        v-if="totalPages > 1"
        class="d-flex justify-content-between align-items-center px-3 py-2"
      >
        <div>Showing {{ orders.length }} of {{ totalItems }} orders</div>
        <nav aria-label="Order pagination">
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

    <!-- Order Details Modal -->
    <div
      class="modal fade"
      id="orderDetailsModal"
      tabindex="-1"
      aria-labelledby="orderDetailsModalLabel"
      aria-hidden="true"
      ref="orderDetailsModalRef"
    >
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="orderDetailsModalLabel">
              Order #{{ selectedOrder?.id }} Details
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body" v-if="selectedOrder">
            <div class="row mb-3">
              <div class="col-md-6">
                <h6>Order Information</h6>
                <p><strong>Date:</strong> {{ formatDate(selectedOrder.created_at) }}</p>
                <p><strong>Status:</strong>
                  <span class="badge" :class="getStatusBadgeClass(selectedOrder.status)">
                    {{ selectedOrder.status.charAt(0).toUpperCase() + selectedOrder.status.slice(1) }}
                  </span>
                </p>
                <p><strong>Total Price:</strong> ${{ selectedOrder.total_price }}</p>
                <p><strong>Notes:</strong> {{ selectedOrder.notes || 'No notes' }}</p>
              </div>
              <div class="col-md-6">
                <h6>Customer Information</h6>
                <p><strong>User ID:</strong> {{ selectedOrder.user_id }}</p>
                <p><strong>Name:</strong> {{ selectedOrder.user ? selectedOrder.user.name : 'N/A' }}</p>
                <p><strong>Room:</strong> {{ selectedOrder.room ? selectedOrder.room.name : `Room #${selectedOrder.room_id}` }}</p>
              </div>
            </div>

            <h6>Order Items</h6>
            <div class="table-responsive">
              <table class="table table-sm table-bordered">
                <thead class="table-light">
                <tr>
                  <th>Product</th>
                  <th>Price</th>
                  <th>Quantity</th>
                  <th>Subtotal</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="product in selectedOrder.products" :key="product.id">
                  <td>{{ product.product_name }}</td>
                  <td>${{ product.price }}</td>
                  <td>{{ product.quantity }}</td>
                  <td>${{ (product.price * product.quantity).toFixed(2) }}</td>
                </tr>
                </tbody>
                <tfoot>
                <tr>
                  <td colspan="3" class="text-end"><strong>Total:</strong></td>
                  <td><strong>${{ selectedOrder.total_price }}</strong></td>
                </tr>
                </tfoot>
              </table>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Update Status Modal -->
    <div
      class="modal fade"
      id="updateStatusModal"
      tabindex="-1"
      aria-labelledby="updateStatusModalLabel"
      aria-hidden="true"
      ref="updateStatusModalRef"
    >
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="updateStatusModalLabel">Update Order Status</h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body">
            <div v-if="updateError" class="alert alert-danger">{{ updateError }}</div>
            <p>
              You are updating the status of Order #{{ orderToUpdate?.id }}.
            </p>
            <p>
              Current status:
              <span
                class="badge"
                :class="orderToUpdate ? getStatusBadgeClass(orderToUpdate.status) : ''"
              >
                {{ orderToUpdate?.status.charAt(0).toUpperCase() + orderToUpdate?.status.slice(1) }}
              </span>
            </p>
            <form @submit.prevent="updateOrderStatus">
              <div class="mb-3">
                <label for="newStatus" class="form-label">New Status</label>
                <select class="form-select" id="newStatus" v-model="newStatus">
                  <option v-if="orderToUpdate?.status === 'processing'" value="delivered">Delivered</option>
                  <option v-if="orderToUpdate?.status === 'delivered'" value="done">Done</option>
                </select>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" @click="updateOrderStatus">Update Status</button>
          </div>
        </div>
      </div>
    </div>

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
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  background-color: white;
}

.form-control:focus,
.form-select:focus {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
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
</style>
