<template>
  <div
    class="modal fade"
    id="updateImageModal"
    tabindex="-1"
    aria-labelledby="updateImageModalLabel"
    aria-hidden="true"
    ref="modalRef"
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
          <div v-if="error" class="alert alert-danger mb-3">{{ error }}</div>

          <form @submit.prevent="submitImage">
            <div class="mb-3">
              <label for="newImage" class="form-label">New Image</label>
              <input
                type="file"
                class="form-control"
                id="newImage"
                accept="image/*"
                @change="handleImageChange"
                required
              />
            </div>

            <div v-if="imagePreview" class="mb-3 text-center">
              <p class="mb-1">Image Preview:</p>
              <img
                :src="imagePreview"
                alt="New product image"
                class="img-thumbnail"
                style="max-height: 150px"
              />
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button
            type="button"
            class="btn btn-primary"
            @click="submitImage"
            :disabled="loading || !selectedImage"
          >
            <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
            Update Image
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, defineProps, defineEmits, onMounted } from 'vue'
import { Modal } from 'bootstrap'

const props = defineProps({
  product: {
    type: Object,
    default: null,
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

const emit = defineEmits(['update', 'close'])
const modalRef = ref(null)
const modalInstance = ref(null)
const selectedImage = ref(null)
const imagePreview = ref(null)

onMounted(() => {
  if (modalRef.value) {
    modalInstance.value = new Modal(modalRef.value)
  }
})

const handleImageChange = (event) => {
  const file = event.target.files[0]
  if (file) {
    selectedImage.value = file
    const reader = new FileReader()
    reader.onload = (e) => {
      imagePreview.value = e.target.result
    }
    reader.readAsDataURL(file)
  } else {
    selectedImage.value = null
    imagePreview.value = null
  }
}

const submitImage = () => {
  if (!selectedImage.value || !props.product) {
    return
  }

  const formData = new FormData()
  formData.append('image', selectedImage.value)

  emit('update', {
    id: props.product.id,
    formData,
  })
}

const show = () => {
  if (modalInstance.value) {
    // Reset state
    selectedImage.value = null
    imagePreview.value = props.product?.image || null

    modalInstance.value.show()
  }
}

const hide = () => {
  if (modalInstance.value) {
    modalInstance.value.hide()
  }
}

// Expose methods to parent
defineExpose({ show, hide })
</script>
