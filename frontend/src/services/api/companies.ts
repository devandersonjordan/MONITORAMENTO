import api from '@/lib/api'
import type { Company, PaginatedResponse, ApiResponse } from '@/types'

export const companiesApi = {
  list: (params?: { page?: number; per_page?: number; search?: string; status?: string }) =>
    api.get<PaginatedResponse<Company>>('/companies', { params }),

  get: (id: number) =>
    api.get<ApiResponse<Company>>(`/companies/${id}`),

  create: (data: Partial<Company>) =>
    api.post<ApiResponse<Company>>('/companies', data),

  update: (id: number, data: Partial<Company>) =>
    api.put<ApiResponse<Company>>(`/companies/${id}`, data),

  delete: (id: number) =>
    api.delete(`/companies/${id}`),
}
