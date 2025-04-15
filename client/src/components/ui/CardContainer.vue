<template>
  <div
    class="card custom-card"
    :class="{
      'hover-effect': hover,
      'no-border': noBorder,
      'no-padding': noPadding,
      [`shadow-${shadow}`]: shadow,
    }"
  >
    <div
      v-if="$slots.header || title"
      class="card-header"
      :class="{ 'bg-primary text-white': primaryHeader }"
    >
      <slot name="header">
        <h5 class="card-title mb-0">{{ title }}</h5>
      </slot>
    </div>
    <div class="card-body" :class="{ 'p-0': noPadding }">
      <slot></slot>
    </div>
    <div v-if="$slots.footer" class="card-footer">
      <slot name="footer"></slot>
    </div>
  </div>
</template>

<script setup>
defineProps({
  title: {
    type: String,
    default: '',
  },
  hover: {
    type: Boolean,
    default: true,
  },
  noBorder: {
    type: Boolean,
    default: false,
  },
  noPadding: {
    type: Boolean,
    default: false,
  },
  primaryHeader: {
    type: Boolean,
    default: false,
  },
  shadow: {
    type: String,
    default: '', // empty, 'sm', or 'lg'
    validator: (value) => ['', 'sm', 'lg'].includes(value),
  },
})
</script>

<style scoped>
.custom-card {
  border-radius: var(--border-radius);
  overflow: hidden;
  margin-bottom: 1.5rem;
  transition:
    transform 0.3s ease,
    box-shadow 0.3s ease;
}

.hover-effect:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.no-border {
  border: none;
}

.shadow-sm {
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}

.shadow-lg {
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12);
}

.card-header {
  border-bottom: 1px solid rgba(0, 0, 0, 0.08);
  background-color: #fff;
}

.card-footer {
  border-top: 1px solid rgba(0, 0, 0, 0.08);
  background-color: #fff;
}
</style>
