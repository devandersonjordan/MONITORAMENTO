import api from '@/lib/api'
import type { Plant, PaginatedResponse, ApiResponse } from '@/types'

export const plantsApi = {
  list: (params?: { page?: number; per_page?: number; search?: string; status?: string; client_id?: number }) =>
    api.get<PaginatedResponse<Plant>>('/plants', { params }),

  get: (id: number) =>
    api.get<ApiResponse<Plant>>(`/plants/${id}`),

  create: (data: Partial<Plant>) =>
    api.post<ApiResponse<Plant>>('/plants', data),

  update: (id: number, data: Partial<Plant>) =>
    api.put<ApiResponse<Plant>>(`/plants/${id}`, data),

  delete: (id: number) =>
    api.delete(`/plants/${id}`),
}
