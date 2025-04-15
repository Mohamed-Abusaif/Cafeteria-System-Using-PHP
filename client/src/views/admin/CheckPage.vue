<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { Modal } from 'bootstrap'

const checks = ref([])
const isLoading = ref(true)
const error = ref(null)

// Pagination
const currentPage = ref(1)
const totalPages = ref(1)
const totalItems = ref(0)
const limit = ref(5)

// Filters
const userIdFilter = ref('')
const startDateFilter = ref('')
const endDateFilter = ref('')

// Check details modal
const checkDetailsModalRef = ref(null)
let checkDetailsModalInstance = null
const selectedCheck = ref(null)

async function fetchChecks() {
  isLoading.value = true
  error.value = null
  try {
    const params = new URLSearchParams()
    params.append('page', currentPage.value)
    params.append('limit', limit.value)
    params.append('status', 'done')

    if (userIdFilter.value) params.append('user_id', userIdFilter.value)
    if (startDateFilter.value && endDateFilter.value) {
      params.append('start_date', startDateFilter.value)
      params.append('end_date', endDateFilter.value)
    }

    const url = `${import.meta.env.VITE_SERVER_URL}/controllers/order.controller.php?${params.toString()}`
    const response = await fetch(url, {
      method: 'GET',
      credentials: 'include',
    })

    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`)

    const data = await response.json()
    if (data && data.data) {
      checks.value = data.data.data || []
      totalPages.value = data.data.total_pages || 1
      totalItems.value = data.data.total || 0
      currentPage.value = data.data.current_page || 1
    } else {
      if (response.ok && data && Array.isArray(data.data)) {
        checks.value = []
      } else {
        throw new Error(data.message || 'Failed to parse checks data.')
      }
    }
  } catch (err) {
    error.value = err.message
    checks.value = []
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  await fetchChecks()
  await nextTick()

  if (checkDetailsModalRef.value) {
    checkDetailsModalInstance = new Modal(checkDetailsModalRef.value)
  }
})

function openCheckDetailsModal(check) {
  selectedCheck.value = check
  if (checkDetailsModalInstance) {
    checkDetailsModalInstance.show()
  } else {
    const modalElement = document.getElementById('checkDetailsModal')
    if (modalElement) new Modal(modalElement).show()
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
  fetchChecks()
}

function applyFilters() {
  currentPage.value = 1
  fetchChecks()
}

function clearFilters() {
  userIdFilter.value = ''
  startDateFilter.value = ''
  endDateFilter.value = ''
  currentPage.value = 1
  fetchChecks()
}

function calculateTotalRevenue() {
  return checks.value.reduce((total, check) => total + parseFloat(check.total_price), 0).toFixed(2)
}

function getTodayDate() {
  return new Date().toISOString().split('T')[0]
}
</script>

<template>
  <div class="full-width-container mt-2">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-3 px-3">
      <h2 class="mb-0">Checks History</h2>
      <div v-if="!isLoading && checks.length > 0" class="badge bg-success p-2">
        Total Revenue: ${{ calculateTotalRevenue() }}
      </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-light p-3 mb-3 rounded">
      <div class="row g-3">
        <div class="col-md-3">
          <label for="userIdFilter" class="form-label">User ID</label>
          <input
            type="text"
            class="form-control"
            id="userIdFilter"
            v-model="userIdFilter"
            placeholder="Filter by user ID..."
          />
        </div>
        <div class="col-md-3">
          <label for="startDateFilter" class="form-label">Start Date</label>
          <input
            type="date"
            class="form-control"
            id="startDateFilter"
            v-model="startDateFilter"
            :max="getTodayDate()"
          />
        </div>
        <div class="col-md-3">
          <label for="endDateFilter" class="form-label">End Date</label>
          <input
            type="date"
            class="form-control"
            id="endDateFilter"
            v-model="endDateFilter"
            :max="getTodayDate()"
            :min="startDateFilter"
          />
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
      Failed to load checks: {{ error }}
    </div>

    <!-- Table Section -->
    <div v-else class="full-width-table px-3">
      <div v-if="!checks || checks.length === 0" class="alert alert-info mx-3">
        No completed orders found. Try adjusting your filters.
      </div>
      <table v-else class="table table-hover mb-0">
        <thead class="table-light">
        <tr>
          <th scope="col" style="width: 5%">#</th>
          <th scope="col" style="width: 20%">Date</th>
          <th scope="col" style="width: 20%">User</th>
          <th scope="col" style="width: 15%">Room</th>
          <th scope="col" style="width: 15%">Total</th>
          <th scope="col" style="width: 25%">Actions</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="check in checks" :key="check.id">
          <th scope="row">{{ check.id }}</th>
          <td>{{ formatDate(check.created_at) }}</td>
          <td>{{ check.user ? check.user.name : `User #${check.user_id}` }}</td>
          <td>{{ check.room ? check.room.name : `Room #${check.room_id}` }}</td>
          <td>${{ check.total_price }}</td>
          <td>
            <div class="btn-group">
              <button type="button" class="btn btn-sm btn-info" @click="openCheckDetailsModal(check)">
                <i class="bi bi-receipt"></i> View Receipt
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
        <div>Showing {{ checks.length }} of {{ totalItems }} checks</div>
        <nav aria-label="Check pagination">
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

    <!-- Check Details Modal -->
    <div
      class="modal fade"
      id="checkDetailsModal"
      tabindex="-1"
      aria-labelledby="checkDetailsModalLabel"
      aria-hidden="true"
      ref="checkDetailsModalRef"
    >
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header receipt-header">
            <h5 class="modal-title" id="checkDetailsModalLabel">
              Receipt #{{ selectedCheck?.id }}
            </h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body" v-if="selectedCheck">
            <div class="receipt-container">
              <div class="receipt-header text-center mb-4">
                <h4>Cafeteria Receipt</h4>
                <p class="mb-0">Order #{{ selectedCheck.id }}</p>
                <p>{{ formatDate(selectedCheck.created_at) }}</p>
              </div>

              <div class="row mb-3">
                <div class="col-md-6">
                  <h6>Customer Information</h6>
                  <p><strong>User ID:</strong> {{ selectedCheck.user_id }}</p>
                  <p><strong>Name:</strong> {{ selectedCheck.user ? selectedCheck.user.name : 'N/A' }}</p>
                </div>
                <div class="col-md-6">
                  <h6>Order Information</h6>
                  <p><strong>Room:</strong> {{ selectedCheck.room ? selectedCheck.room.name : `Room #${selectedCheck.room_id}` }}</p>
                  <p><strong>Notes:</strong> {{ selectedCheck.notes || 'No notes' }}</p>
                </div>
              </div>

              <hr class="dashed-divider">

              <h6>Items Purchased</h6>
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead class="table-light">
                  <tr>
                    <th>Item</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th class="text-end">Subtotal</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr v-for="product in selectedCheck.products" :key="product.id">
                    <td>{{ product.product_name }}</td>
                    <td>${{ product.price }}</td>
                    <td>{{ product.quantity }}</td>
                    <td class="text-end">${{ (product.price * product.quantity).toFixed(2) }}</td>
                  </tr>
                  </tbody>
                  <tfoot>
                  <tr>
                    <td colspan="3" class="text-end"><strong>Total:</strong></td>
                    <td class="text-end"><strong>${{ selectedCheck.total_price }}</strong></td>
                  </tr>
                  </tfoot>
                </table>
              </div>

              <hr class="dashed-divider">

              <div class="text-center mt-4">
                <p class="mb-0">Thank you for your order!</p>
                <p class="small">This is a system-generated receipt.</p>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" onclick="window.print()">
              <i class="bi bi-printer"></i> Print Receipt
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
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  background-color: white;
}

.form-control:focus,
.form-select:focus {
  border-color: #86b7fe;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

.receipt-container {
  font-family: 'Arial', sans-serif;
  padding: 10px;
}

.receipt-header {
  border-bottom: 1px solid #e5e5e5;
}

.dashed-divider {
  border-top: 1px dashed #ccc;
  margin: 15px 0;
}

@media print {
  body * {
    visibility: hidden;
  }
  .modal-content, .modal-content * {
    visibility: visible;
  }
  .modal {
    position: absolute;
    left: 0;
    top: 0;
    margin: 0;
    padding: 0;
    overflow: visible!important;
  }
  .modal-dialog {
    width: 100%;
    max-width: 100%;
    margin: 0;
  }
  .modal-footer, .btn-close {
    display: none;
  }
}
</style>
