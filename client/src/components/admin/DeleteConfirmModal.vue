<template>
  <div
    class="modal fade"
    id="deleteModal"
    tabindex="-1"
    aria-labelledby="deleteModalLabel"
    aria-hidden="true"
    ref="modalRef"
  >
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close"
          ></button>
        </div>
        <div class="modal-body">
          <div v-if="error"
            class="alert mb-3"
            :class="{
              'alert-danger': !error.includes('successfully'),
              'alert-success': error.includes('successfully')
            }"
          >
            {{ error }}
          </div>

          <p v-if="!error || !error.includes('successfully')">
            Are you sure you want to delete the product:
            <strong>{{ product?.name }}</strong>?
          </p>
          <p class="text-danger" v-if="!error || !error.includes('successfully')">
            <small>This action cannot be undone.</small>
          </p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            {{ error && error.includes('successfully') ? 'Close' : 'Cancel' }}
          </button>
          <button
            v-if="!error || !error.includes('successfully')"
            type="button"
            class="btn btn-danger"
            @click="confirmDelete"
            :disabled="loading"
          >
            <span v-if="loading" class="spinner-border spinner-border-sm me-2" role="status"></span>
            Delete Product
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

const emit = defineEmits(['confirm', 'close'])
const modalRef = ref(null)
const modalInstance = ref(null)

onMounted(() => {
  if (modalRef.value) {
    modalInstance.value = new Modal(modalRef.value)
  }
})

const confirmDelete = () => {
  if (!props.product) return
  emit('confirm', props.product.id)
}

const show = () => {
  if (modalInstance.value) {
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
