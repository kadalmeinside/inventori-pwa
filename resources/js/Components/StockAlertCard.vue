<template>
  <div
    class="stock-alert-card"
    :class="`stock-alert-card--${severity}`"
    role="listitem"
  >
    <!-- Left accent bar -->
    <span class="stock-alert-card__accent" />

    <!-- Product info -->
    <div class="stock-alert-card__info">
      <p class="stock-alert-card__product">{{ entry.product.name }}</p>
      <p class="stock-alert-card__sku">{{ entry.product.sku }}</p>
    </div>

    <!-- Stock levels -->
    <div class="stock-alert-card__levels">
      <div class="stock-alert-card__qty" :class="`stock-alert-card__qty--${severity}`">
        {{ entry.quantity }}
        <span class="stock-alert-card__unit">{{ entry.product.unit }}</span>
      </div>
      <div class="stock-alert-card__min">of {{ entry.product.min_stock }} min</div>
    </div>

    <!-- Progress bar -->
    <div class="stock-alert-card__bar-wrap" :title="`${pct}% of minimum`">
      <div
        class="stock-alert-card__bar"
        :class="`stock-alert-card__bar--${severity}`"
        :style="{ width: `${Math.min(pct, 100)}%` }"
      />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  entry:    { type: Object, required: true },
  severity: { type: String, default: 'warning' }, // 'critical' | 'warning'
})

const pct = computed(() =>
  props.entry.product.min_stock > 0
    ? Math.round((props.entry.quantity / props.entry.product.min_stock) * 100)
    : 0
)
</script>

<style scoped>
.stock-alert-card {
  display: grid;
  grid-template-columns: auto 1fr auto;
  grid-template-rows: auto auto;
  column-gap: 0.75rem;
  row-gap: 0.375rem;
  padding: 0.875rem 1rem;
  border-radius: 1rem;
  background: rgba(255, 255, 255, 0.60);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border: 1px solid rgba(255, 255, 255, 0.75);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06), inset 0 1px 0 rgba(255,255,255,0.9);
  transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.2s;
  cursor: default;
  position: relative;
  overflow: hidden;
}

.stock-alert-card:hover {
  transform: translateX(3px);
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.09), inset 0 1px 0 rgba(255,255,255,0.9);
}

/* Color variants */
.stock-alert-card--critical {
  border-color: rgba(255, 59, 48, 0.25);
  background: rgba(255, 59, 48, 0.06);
}
.stock-alert-card--warning {
  border-color: rgba(255, 149, 0, 0.25);
  background: rgba(255, 149, 0, 0.06);
}

/* Left accent bar */
.stock-alert-card__accent {
  grid-column: 1;
  grid-row: 1 / -1;
  width: 3px;
  border-radius: 99px;
  align-self: stretch;
}
.stock-alert-card--critical .stock-alert-card__accent { background: var(--ios-red); }
.stock-alert-card--warning  .stock-alert-card__accent { background: var(--ios-orange); }

/* Info */
.stock-alert-card__info {
  grid-column: 2;
  grid-row: 1;
  min-width: 0;
}

.stock-alert-card__product {
  font-size: 0.8125rem;
  font-weight: 600;
  color: rgba(0, 0, 0, 0.80);
  margin: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.stock-alert-card__sku {
  font-size: 0.68rem;
  color: rgba(0, 0, 0, 0.35);
  margin: 0;
  font-family: var(--font-mono);
  letter-spacing: 0.04em;
}

/* Levels */
.stock-alert-card__levels {
  grid-column: 3;
  grid-row: 1;
  text-align: right;
  flex-shrink: 0;
}

.stock-alert-card__qty {
  font-size: 1.125rem;
  font-weight: 800;
  line-height: 1;
  display: flex;
  align-items: baseline;
  gap: 0.2rem;
  justify-content: flex-end;
  letter-spacing: -0.03em;
}

.stock-alert-card__qty--critical { color: var(--ios-red); }
.stock-alert-card__qty--warning  { color: var(--ios-orange); }

.stock-alert-card__unit {
  font-size: 0.65rem;
  font-weight: 500;
  color: rgba(0, 0, 0, 0.35);
  font-family: inherit;
}

.stock-alert-card__min {
  font-size: 0.65rem;
  color: rgba(0, 0, 0, 0.30);
  margin-top: 0.1rem;
}

/* Progress bar */
.stock-alert-card__bar-wrap {
  grid-column: 2 / -1;
  grid-row: 2;
  height: 3px;
  background: rgba(0, 0, 0, 0.07);
  border-radius: 999px;
  overflow: hidden;
}

.stock-alert-card__bar {
  height: 100%;
  border-radius: 999px;
  transition: width 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
}

.stock-alert-card__bar--critical { background: var(--ios-red); }
.stock-alert-card__bar--warning  { background: var(--ios-orange); }
</style>
