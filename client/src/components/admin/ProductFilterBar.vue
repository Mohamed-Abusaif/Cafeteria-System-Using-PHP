<template>
  <div class="card mb-4">
    <div class="card-body">
      <div class="row">
        <div class="col-md-4 mb-3 mb-md-0">
          <input
            type="text"
            class="form-control"
            placeholder="Search by name..."
            v-model="filters.name"
            @input="emitFilterChange"
          />
        </div>
        <div class="col-md-3 mb-3 mb-md-0">
          <select class="form-select" v-model="filters.category_id" @change="emitFilterChange">
            <option value="">All Categories</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
            <option v-if="categories.length === 0" disabled>No categories available</option>
          </select>
          <div v-if="categories.length === 0" class="form-text text-danger mt-1">
            <small>No categories loaded. Please check your connection.</small>
          </div>
        </div>
        <div class="col-md-3 mb-3 mb-md-0">
          <select class="form-select" v-model="filters.availability" @change="emitFilterChange">
            <option value="">All Status</option>
            <option value="available">Available</option>
            <option value="unavailable">Unavailable</option>
          </select>
        </div>
        <div class="col-md-2">
          <button class="btn btn-secondary w-100" @click="resetFilters">
            <i class="bi bi-x-circle me-2"></i>Reset
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { reactive, defineProps, defineEmits, watch } from 'vue'

const props = defineProps({
  categories: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['filter', 'reset'])

const filters = reactive({
  name: '',
  category_id: '',
  availability: '',
})

// Watch for categories changes and log them
watch(() => props.categories, (newValue) => {
  console.log('ProductFilterBar received categories:', newValue?.length || 0)
}, { immediate: true })

const emitFilterChange = () => {
  emit('filter', { ...filters })
}

const resetFilters = () => {
  filters.name = ''
  filters.category_id = ''
  filters.availability = ''
  emit('reset')
}
</script>
