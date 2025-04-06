<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { Modal } from 'bootstrap'

const rooms = ref([])
const isLoading = ref(true)
const error = ref(null)

const formRoomName = ref('')
const formRoomDescription = ref('')
const addEditModalRef = ref(null)
let addEditModalInstance = null
const isEditing = ref(false)
const editingRoomId = ref(null)

const deleteModalRef = ref(null)
let deleteModalInstance = null
const roomToDelete = ref(null)

const modalTitle = computed(() => {
  return isEditing.value ? `Update Room#${editingRoomId.value}` : 'Add New Room'
})

async function fetchRooms() {
  isLoading.value = true
  error.value = null
  try {
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/room.controller.php`,
    )
    if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`)
    const data = await response.json()
    if (data && data.data) {
      rooms.value = data.data
    } else {
      if (response.ok && data && Array.isArray(data.data)) {
        rooms.value = []
      } else {
        throw new Error(data.message || 'Failed to parse rooms data.')
      }
    }
  } catch (err) {
    error.value = err.message
    rooms.value = []
  } finally {
    isLoading.value = false
  }
}

onMounted(async () => {
  await fetchRooms()
  await nextTick()
  if (addEditModalRef.value) {
    addEditModalInstance = new Modal(addEditModalRef.value)
  }
  if (deleteModalRef.value) {
    deleteModalInstance = new Modal(deleteModalRef.value)
  }
})

function openAddModal() {
  isEditing.value = false
  editingRoomId.value = null
  formRoomName.value = ''
  formRoomDescription.value = ''
  if (addEditModalInstance) {
    addEditModalInstance.show()
  } else {
    const modalElement = document.getElementById('addEditRoomModal')
    if (modalElement) new Modal(modalElement).show()
  }
}

function openUpdateModal(room) {
  isEditing.value = true
  editingRoomId.value = room.id
  formRoomName.value = room.name
  formRoomDescription.value = room.description
  if (addEditModalInstance) {
    addEditModalInstance.show()
  } else {
    const modalElement = document.getElementById('addEditRoomModal')
    if (modalElement) new Modal(modalElement).show()
  }
}

function openDeleteModal(room) {
  roomToDelete.value = room
  if (deleteModalInstance) {
    deleteModalInstance.show()
  } else {
    const modalElement = document.getElementById('deleteConfirmationModal')
    if (modalElement) new Modal(modalElement).show()
  }
}

async function saveOrUpdateRoom() {
  if (!formRoomName.value) {
    alert('Room name is required.')
    return
  }

  let url = `${import.meta.env.VITE_SERVER_URL}/controllers/room.controller.php`
  let method = 'POST'
  const roomData = {
    name: formRoomName.value,
    description: formRoomDescription.value,
  }

  if (isEditing.value && editingRoomId.value) {
    method = 'PATCH'
    url += `/${editingRoomId.value}`
  }

  try {
    const response = await fetch(url, {
      method: method,
      body: JSON.stringify(roomData),
    })
    const result = await response.json()

    if (result && (result.statusCode === 200 || result.statusCode === 201)) {
      if (addEditModalInstance) {
        addEditModalInstance.hide()
      }
      await fetchRooms()
    } else {
      throw new Error(result.message || `Failed to ${isEditing.value ? 'update' : 'save'} room.`)
    }
  } catch (err) {
    alert(`Error ${isEditing.value ? 'updating' : 'saving'} room: ${err.message}`)
  }
}

async function confirmDelete() {
  if (!roomToDelete.value) return

  try {
    const response = await fetch(
      `${import.meta.env.VITE_SERVER_URL}/controllers/room.controller.php/${roomToDelete.value.id}`,
      {
        method: 'DELETE',
      },
    )

    const result = await response.json()
    if (result && result.statusCode === 200) {
      if (deleteModalInstance) {
        deleteModalInstance.hide()
      }
      await fetchRooms()
    } else {
      throw new Error(result.message || 'Failed to delete room.')
    }
  } catch (err) {
    alert(`Error deleting room: ${err.message}`)
  } finally {
    roomToDelete.value = null
  }
}
</script>

<template>
  <div class="full-width-container mt-2">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-3 px-3">
      <h2 class="mb-0">Rooms Details</h2>
      <!-- Button now calls openAddModal -->
      <button type="button" class="btn btn-primary" @click="openAddModal">
        <i class="bi bi-plus-circle me-1"></i> Add Room
      </button>
    </div>

    <!-- Loading and Error Display -->
    <div v-if="isLoading" class="text-center p-5">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading...</span>
      </div>
    </div>
    <div v-else-if="error" class="alert alert-danger mx-3" role="alert">
      Failed to load rooms: {{ error }}
    </div>

    <!-- Table Section -->
    <div v-else class="full-width-table px-3">
      <div v-if="!rooms || rooms.length === 0" class="alert alert-info mx-3">No rooms found.</div>
      <table v-else class="table table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th scope="col" style="width: 10%">#</th>
            <th scope="col" style="width: 30%">Name</th>
            <th scope="col" style="width: 40%">Description</th>
            <th scope="col" style="width: 20%">Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="room in rooms" :key="room.id">
            <th scope="row">{{ room.id }}</th>
            <td>{{ room.name }}</td>
            <td>{{ room.description }}</td>
            <td>
              <div class="btn-group">
                <button
                  type="button"
                  class="btn btn-sm btn-warning me-1"
                  @click="openUpdateModal(room)"
                >
                  <i class="bi bi-pencil-square"></i> Update
                </button>
                <button type="button" class="btn btn-sm btn-danger" @click="openDeleteModal(room)">
                  <i class="bi bi-trash"></i> Delete
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Add/Edit Room Modal -->
    <div
      class="modal fade"
      id="addEditRoomModal"
      tabindex="-1"
      aria-labelledby="addEditRoomModalLabel"
      aria-hidden="true"
      ref="addEditModalRef"
    >
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addEditRoomModalLabel">{{ modalTitle }}</h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveOrUpdateRoom">
              <div class="mb-3">
                <label for="roomName" class="form-label">Room Name</label>
                <input
                  type="text"
                  class="form-control"
                  id="roomName"
                  v-model="formRoomName"
                  required
                />
              </div>
              <div class="mb-3">
                <label for="roomDescription" class="form-label">Description</label>
                <textarea
                  class="form-control"
                  id="roomDescription"
                  rows="3"
                  v-model="formRoomDescription"
                ></textarea>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            <button type="button" class="btn btn-primary" @click="saveOrUpdateRoom">
              Save Changes
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div
      class="modal fade"
      id="deleteConfirmationModal"
      tabindex="-1"
      aria-labelledby="deleteConfirmationModalLabel"
      aria-hidden="true"
      ref="deleteModalRef"
    >
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="deleteConfirmationModalLabel">Confirm Delete</h5>
            <button
              type="button"
              class="btn-close"
              data-bs-dismiss="modal"
              aria-label="Close"
            ></button>
          </div>
          <div class="modal-body">
            Are you sure you want to delete room "{{ roomToDelete?.name }}"?
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-danger" @click="confirmDelete">Delete</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.full-width-container {
  width: 90%;
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem;
}

.full-width-table {
  width: 100%;
  overflow-x: auto;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  border-radius: 8px;
  background-color: white;
  margin: 0 auto; /* Center the table */
}

table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}

thead {
  background-color: #f8f9fa;
}

th,
td {
  padding: 12px 15px;
}

.table-hover tbody tr:hover {
  background-color: rgba(0, 123, 255, 0.05);
}
</style>
