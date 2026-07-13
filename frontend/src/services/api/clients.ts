import api from '@/lib/api'
import type { User, PaginatedResponse, ApiResponse } from '@/types'

export const clientsApi = {
  list: (params?: { page?: number; per_page?: number; search?: string }) =>
    api.get<PaginatedResponse<User>>('/clients', { params }),

  get: (id: number) =>
    api.get<ApiResponse<User>>(`/clients/${id}`),

  create: (data: Record<string, unknown>) =>
    api.post<ApiResponse<User>>('/clients', data),

  update: (id: number, data: Record<string, unknown>) =>
    api.put<ApiResponse<User>>(`/clients/${id}`, data),

  delete: (id: number) =>
    api.delete(`/clients/${id}`),
}
