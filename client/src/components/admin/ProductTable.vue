<template>
  <div>
    <div v-if="loading" class="text-center py-5">
      <div class="spinner-border" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>

    <div v-else-if="error" class="alert alert-danger">Failed to load products: {{ error }}</div>

    <div v-else-if="!products || !hasProducts" class="text-center py-5">
      <p class="text-muted">No products found. Try adjusting your filters or add a new product.</p>
    </div>

    <div v-else class="table-responsive">
      <table class="table table-striped table-hover">
        <thead class="table-light">
          <tr>
            <th style="width: 60px">ID</th>
            <th style="width: 80px">Image</th>
            <th>Name</th>
            <th>Price</th>
            <th>Category</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="product in productItems" :key="product.id">
            <td>{{ product.id }}</td>
            <td>
              <div class="position-relative image-container">
                <img
                  :src="product.image || 'https://via.placeholder.com/50'"
                  alt="Product"
                  class="img-thumbnail"
                  style="width: 50px; height: 50px; object-fit: cover"
                />
                <div class="image-overlay" @click="$emit('update-image', product)">
                  <i class="bi bi-camera"></i>
                </div>
              </div>
            </td>
            <td>{{ product.name }}</td>
            <td>${{ parseFloat(product.price).toFixed(2) }}</td>
            <td>{{ getCategoryName(product.category_id) }}</td>
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
                  class="btn btn-sm btn-outline-primary"
                  @click="$emit('edit', product)"
                  title="Edit"
                >
                  <i class="bi bi-pencil"></i>
                </button>
                <button
                  class="btn btn-sm btn-outline-danger"
                  @click="$emit('delete', product)"
                  title="Delete"
                >
                  <i class="bi bi-trash"></i>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.totalPages > 1" class="d-flex justify-content-center mt-4">
      <nav aria-label="Page navigation">
        <ul class="pagination">
          <li class="page-item" :class="{ disabled: pagination.currentPage === 1 }">
            <a
              class="page-link"
              @click.prevent="$emit('page-change', pagination.currentPage - 1)"
            >
              <i class="bi bi-chevron-left"></i>
            </a>
          </li>

          <li
            v-for="page in getPageNumbers()"
            :key="page"
            class="page-item"
            :class="{ active: page === pagination.currentPage }"
          >
            <a class="page-link" @click.prevent="$emit('page-change', page)">{{ page }}</a>
          </li>

          <li
            class="page-item"
            :class="{ disabled: pagination.currentPage === pagination.totalPages }"
          >
            <a
              class="page-link"

              @click.prevent="$emit('page-change', pagination.currentPage + 1)"
            >
              <i class="bi bi-chevron-right"></i>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits, computed, watch } from 'vue'

const props = defineProps({
  products: {
    type: [Array, Object],
    default: () => [],
  },
  categories: {
    type: Array,
    default: () => [],
  },
  pagination: {
    type: Object,
    default: () => ({
      currentPage: 1,
      totalPages: 1,
      totalItems: 0,
    }),
  },
  loading: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
})

defineEmits(['edit', 'delete', 'update-image', 'page-change'])

// Watch for product data changes and log them
watch(() => props.products, (newValue) => {
  console.log('ProductTable received products:', newValue)
}, { deep: true })

// Check if products exist and have data
const hasProducts = computed(() => {
  console.log('Checking hasProducts with:', props.products)
  return Array.isArray(props.products) && props.products.length > 0;
});

// Safely get product items - just return the products array directly
const productItems = computed(() => {
  console.log('Computing productItems from:', props.products)
  return Array.isArray(props.products) ? props.products : [];
});

const getCategoryName = (categoryId) => {
  const category = props.categories.find((c) => c.id === categoryId)
  return category ? category.name : 'Unknown'
}

const getPageNumbers = () => {
  const maxVisiblePages = 5
  let pages = []

  if (props.pagination.totalPages <= maxVisiblePages) {
    // Show all pages if there are fewer than maxVisiblePages
    for (let i = 1; i <= props.pagination.totalPages; i++) {
      pages.push(i)
    }
  } else {
    // Calculate start and end of pages to show
    let start = Math.max(1, props.pagination.currentPage - Math.floor(maxVisiblePages / 2))
    let end = Math.min(props.pagination.totalPages, start + maxVisiblePages - 1)

    // Adjust if at the edges
    if (end === props.pagination.totalPages) {
      start = Math.max(1, end - maxVisiblePages + 1)
    }

    for (let i = start; i <= end; i++) {
      pages.push(i)
    }
  }

  return pages
}
</script>

<style scoped>
.table th,
.table td {
  vertical-align: middle;
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
