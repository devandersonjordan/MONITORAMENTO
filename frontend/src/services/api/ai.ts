import api from '@/lib/api'

export const aiApi = {
  chat: (data: { message: string; history?: { role: string; content: string }[] }) =>
    api.post('/ai/chat', data),

  analyzePlant: (plantId: number) =>
    api.get(`/ai/analyze/plant/${plantId}`),

  analyzeInvoice: (invoiceId: number) =>
    api.get(`/ai/analyze/invoice/${invoiceId}`),
}
