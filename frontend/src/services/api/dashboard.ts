import api from '@/lib/api'
import type { DashboardStats } from '@/types'

export const dashboardApi = {
  getStats: () => api.get<{ data: DashboardStats }>('/dashboard/stats'),
}
