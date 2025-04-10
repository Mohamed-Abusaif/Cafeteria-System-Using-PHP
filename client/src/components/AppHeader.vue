<script setup>
import { ref } from 'vue';
const showDropdown = ref(false);
const toggleDropdown = () => {
  showDropdown.value = !showDropdown.value;
};
</script>

<template>
  <header class="navbar navbar-expand-lg navbar-light bg-light shadow-sm fixed-top">
    <div class="container-fluid">
      <router-link to="/" class="navbar-brand d-flex align-items-center">
        <i class="bi bi-cup-hot"></i>
        <span class="fw-bold ms-2">Cafeteria</span>
      </router-link>

      <div class="ms-auto dropdown">
        <button
          class="btn btn-outline-secondary dropdown-toggle"
          type="button"
          id="userDropdown"
          @click="toggleDropdown"
          aria-expanded="false"
        >
          <i class="bi bi-person"></i>
        </button>

        <ul
          class="dropdown-menu dropdown-menu-end"
          :class="{ show: showDropdown }"
          aria-labelledby="userDropdown"
          style="position: absolute; right: 0; left: auto;"
        >
          <li>
            <router-link class="dropdown-item d-flex justify-content-between align-items-center" to="/">
              <span>Profile</span>
              <i class="bi bi-person-bounding-box"></i>
            </router-link>
          </li>
          <li>
            <router-link class="dropdown-item d-flex justify-content-between align-items-center" to="/dashboard">
              <span>Dashboard</span>
              <i class="bi bi-graph-up"></i>
            </router-link>
          </li>
          <li>
            <router-link class="dropdown-item d-flex justify-content-between align-items-center" to="/dashboard">
              <span>Cart</span>
              <i class="bi bi-cart-dash"></i>
            </router-link>
          </li>
          <li>
            <router-link class="dropdown-item d-flex justify-content-between align-items-center" to="/">
              <span>Orders</span>
              <i class="bi bi-ui-checks"></i>
            </router-link>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <router-link class="dropdown-item text-danger d-flex justify-content-between align-items-center" to="/">
              <span>Logout</span>
              <i class="bi bi-box-arrow-right"></i>
            </router-link>
          </li>
        </ul>
      </div>
    </div>
  </header>
</template>

<style scoped>
body {
  padding-top: 56px;
}
.dropdown-menu {
  margin-top: 0.5rem;
}
</style>
