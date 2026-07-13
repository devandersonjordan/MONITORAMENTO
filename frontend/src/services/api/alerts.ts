import api from '@/lib/api'

export const alertsApi = {
  list: (params?: Record<string, unknown>) =>
    api.get('/alerts', { params }),

  stats: () =>
    api.get('/alerts/stats'),

  resolve: (id: number) =>
    api.patch(`/alerts/${id}/resolve`),
}
