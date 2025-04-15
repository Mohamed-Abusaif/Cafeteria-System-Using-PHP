<template>
  <div class="loading-container" :class="{ overlay: overlay }">
    <div class="spinner" :class="[`spinner-${size}`, `text-${color}`]">
      <div class="bounce1"></div>
      <div class="bounce2"></div>
      <div class="bounce3"></div>
    </div>
    <div v-if="message" class="loading-message">{{ message }}</div>
  </div>
</template>

<script setup>
defineProps({
  overlay: {
    type: Boolean,
    default: false,
  },
  message: {
    type: String,
    default: '',
  },
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['sm', 'md', 'lg'].includes(value),
  },
  color: {
    type: String,
    default: 'primary',
    validator: (value) =>
      ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'].includes(
        value,
      ),
  },
})
</script>

<style scoped>
.loading-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 1rem;
}

.loading-container.overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(255, 255, 255, 0.8);
  z-index: 1050;
}

.loading-message {
  margin-top: 1rem;
  font-size: 0.9rem;
  color: var(--text-color);
}

.spinner {
  display: flex;
  justify-content: center;
}

.spinner > div {
  width: 12px;
  height: 12px;
  margin: 0 3px;
  background-color: var(--primary-color);
  border-radius: 100%;
  display: inline-block;
  animation: sk-bouncedelay 1.4s infinite ease-in-out both;
}

.spinner-sm > div {
  width: 8px;
  height: 8px;
  margin: 0 2px;
}

.spinner-lg > div {
  width: 18px;
  height: 18px;
  margin: 0 4px;
}

.text-primary > div {
  background-color: var(--primary-color);
}

.text-secondary > div {
  background-color: var(--secondary-color);
}

.text-success > div {
  background-color: var(--success-color);
}

.text-danger > div {
  background-color: var(--danger-color);
}

.text-warning > div {
  background-color: var(--warning-color);
}

.text-info > div {
  background-color: var(--info-color);
}

.text-light > div {
  background-color: var(--light-color);
}

.text-dark > div {
  background-color: var(--dark-color);
}

.spinner .bounce1 {
  animation-delay: -0.32s;
}

.spinner .bounce2 {
  animation-delay: -0.16s;
}

@keyframes sk-bouncedelay {
  0%,
  80%,
  100% {
    transform: scale(0);
  }
  40% {
    transform: scale(1);
  }
}
</style>
