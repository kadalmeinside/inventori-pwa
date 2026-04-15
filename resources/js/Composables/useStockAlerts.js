/**
 * useStockAlerts — Reactive stock alert composable
 *
 * Provides computed alerts for products that are below minimum stock threshold.
 */

import { computed } from 'vue'
import { usePage }  from '@inertiajs/vue3'

export function useStockAlerts() {
  const page = usePage()

  /** All stock entries passed as Inertia shared prop from DashboardController */
  const stockEntries = computed(() => page.props.stockAlerts ?? [])

  /** Critical alerts: quantity is 0 */
  const criticalAlerts = computed(() =>
    stockEntries.value.filter(e => e.quantity === 0)
  )

  /** Warning alerts: quantity > 0 but below min_stock */
  const warningAlerts = computed(() =>
    stockEntries.value.filter(e => e.quantity > 0 && e.quantity < e.product.min_stock)
  )

  /** Total alert count */
  const alertCount = computed(() => stockEntries.value.length)

  return { stockEntries, criticalAlerts, warningAlerts, alertCount }
}
