<template>
  <div class="card mb-4">
    <div class="card-header bg-light">
      <h5 class="mb-0">{{ editMode ? 'Edit Product' : 'Create New Product' }}</h5>
    </div>
    <div class="card-body">
      <form @submit.prevent="handleSubmit">
        <div class="mb-3">
          <label for="name" class="form-label">Product Name</label>
          <input
            type="text"
            class="form-control"
            id="name"
            v-model="form.name"
            :class="{ 'is-invalid': errors.name }"
            required
          />
          <div v-if="errors.name" class="invalid-feedback">{{ errors.name }}</div>
        </div>

        <div class="mb-3">
          <label for="price" class="form-label">Price</label>
          <div class="input-group" :class="{ 'is-invalid': errors.price }">
            <span class="input-group-text">$</span>
            <input
              type="number"
              step="0.01"
              min="0"
              class="form-control"
              :class="{ 'is-invalid': errors.price }"
              id="price"
              v-model="form.price"
              required
            />
          </div>
          <div v-if="errors.price" class="invalid-feedback">{{ errors.price }}</div>
        </div>

        <div class="mb-3">
          <label for="category" class="form-label">Category</label>
          <select
            class="form-select"
            :class="{ 'is-invalid': errors.category_id }"
            id="category"
            v-model="form.category_id"
            required
          >
            <option value="" disabled>Select a category</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
          <div v-if="errors.category_id" class="invalid-feedback">{{ errors.category_id }}</div>
          <div v-if="categories.length === 0" class="text-danger mt-1">
            <small>No categories available. Please add a category first.</small>
          </div>
        </div>

        <div class="mb-3">
          <label for="availability" class="form-label">Availability</label>
          <select class="form-select" id="availability" v-model="form.availability">
            <option value="available">Available</option>
            <option value="unavailable">Unavailable</option>
          </select>
        </div>

        <div class="mb-3">
          <label for="image" class="form-label">Product Image</label>
          <input
            type="file"
            class="form-control"
            :class="{ 'is-invalid': errors.image }"
            id="image"
            accept="image/*"
            @change="handleImageChange"
            :required="!editMode"
          />
          <div v-if="errors.image" class="invalid-feedback">{{ errors.image }}</div>
          <small class="form-text text-muted">
            {{
              editMode
                ? 'Only upload if you want to change the image.'
                : 'Please upload a product image.'
            }}
          </small>
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

        <div class="d-flex justify-content-end gap-2">
          <button type="button" class="btn btn-secondary" @click="$emit('cancel')">Cancel</button>
          <button type="submit" class="btn btn-primary" :disabled="loading">
            <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
            {{ editMode ? 'Update' : 'Create' }} Product
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, defineProps, defineEmits, onMounted, watch } from 'vue'

const props = defineProps({
  product: {
    type: Object,
    default: () => ({}),
  },
  categories: {
    type: Array,
    default: () => [],
  },
  editMode: {
    type: Boolean,
    default: false,
  },
  loading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['submit', 'cancel'])

const form = reactive({
  name: '',
  price: '',
  category_id: '',
  availability: 'available',
  image: null,
})

const errors = reactive({
  name: '',
  price: '',
  category_id: '',
  image: '',
})

const imagePreview = ref(null)

// Initialize form with product data when in edit mode
watch(
  () => props.product,
  (newValue) => {
    if (props.editMode && newValue) {
      form.name = newValue.name || ''
      form.price = newValue.price || ''
      form.category_id = newValue.category_id || ''
      form.availability = newValue.availability || 'available'
      imagePreview.value = newValue.image || null
    }
  },
  { immediate: true },
)

// Monitor categories
watch(() => props.categories, (newValue) => {
  console.log('ProductForm received categories:', newValue?.length || 0)

  // If we're editing but have no category selected, try to select from existing categories
  if (props.editMode && !form.category_id && newValue && newValue.length > 0) {
    form.category_id = props.product.category_id || newValue[0].id
  }
}, { immediate: true })

const handleImageChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    form.image = file
    const reader = new FileReader()
    reader.onload = (e) => {
      imagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  } else {
    form.image = null
    imagePreview.value = props.editMode ? props.product.image : null
  }
}

const validateForm = () => {
  let isValid = true

  // Reset errors
  errors.name = ''
  errors.price = ''
  errors.category_id = ''
  errors.image = ''

  if (!form.name.trim()) {
    errors.name = 'Product name is required'
    isValid = false
  }

  if (!form.price) {
    errors.price = 'Price is required'
    isValid = false
  } else if (isNaN(form.price) || parseFloat(form.price) < 0) {
    errors.price = 'Price must be a valid positive number'
    isValid = false
  }

  if (!form.category_id) {
    errors.category_id = 'Category is required'
    isValid = false
  }

  if (!props.editMode && !form.image) {
    errors.image = 'Product image is required for new products'
    isValid = false
  }

  return isValid
}

const handleSubmit = () => {
  if (!validateForm()) {
    return
  }

  const formData = new FormData()
  formData.append('name', form.name)
  formData.append('price', form.price)
  formData.append('category_id', form.category_id)
  formData.append('availability', form.availability)

  if (form.image) {
    formData.append('image', form.image)
  }

  emit('submit', {
    formData,
    isEditing: props.editMode,
    id: props.product?.id,
    hasNewImage: !!form.image,
  })
}
</script>
