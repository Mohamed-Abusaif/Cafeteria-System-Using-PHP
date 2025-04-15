<script setup>
import { RouterView } from 'vue-router'
import AppHeader from '@/components/AppHeader.vue'
import AppFooter from '@/components/AppFooter.vue'
</script>

<template>
  <div class="app-container">
    <AppHeader />
    <main class="main-content">
      <div class="router-container">
        <RouterView v-slot="{ Component }">
          <transition name="fade" mode="out-in">
            <component :is="Component" />
          </transition>
        </RouterView>
      </div>
    </main>
    <AppFooter />
  </div>
</template>

<style>
/* Import font */
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

.app-container {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  width: 100vw;
  margin: 0;
  padding: 0;
  overflow-x: hidden;
  max-width: 100%;
}

.main-content {
  flex: 1;
  width: 100%;
  padding: 0;
  margin: 0;
  max-width: 100%;
}

.router-container {
  width: 100%;
  padding-left: 2.5rem;
  padding-right: 2.5rem;
}

@media (max-width: 768px) {
  .router-container {
    padding-left: 1.25rem;
    padding-right: 1.25rem;
  }
}

/* Page transitions */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
