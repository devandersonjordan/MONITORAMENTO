import api from '@/lib/api'

export const invoicesApi = {
  list: (params?: Record<string, unknown>) =>
    api.get('/invoices', { params }),

  get: (id: number) =>
    api.get(`/invoices/${id}`),

  downloadPdf: (id: number) =>
    api.get(`/invoices/${id}/pdf`, { responseType: 'blob' }),
}
