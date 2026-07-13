import api from '@/lib/api'

export const reportsApi = {
  list: (params?: Record<string, unknown>) =>
    api.get('/reports', { params }),

  get: (id: number) =>
    api.get(`/reports/${id}`),

  generate: (data: { client_id: number; month: string }) =>
    api.post('/reports/generate', data),

  downloadPdf: (id: number) =>
    api.get(`/reports/${id}/pdf`, { responseType: 'blob' }),
}
