import api from '@/lib/api'
import type { Inverter, PaginatedResponse, ApiResponse } from '@/types'

export const invertersApi = {
  list: (params?: { page?: number; per_page?: number; search?: string; brand?: string; status?: string; plant_id?: number }) =>
    api.get<PaginatedResponse<Inverter>>('/inverters', { params }),

  get: (id: number) =>
    api.get<ApiResponse<Inverter>>(`/inverters/${id}`),

  create: (data: Partial<Inverter>) =>
    api.post<ApiResponse<Inverter>>('/inverters', data),

  update: (id: number, data: Partial<Inverter>) =>
    api.put<ApiResponse<Inverter>>(`/inverters/${id}`, data),

  delete: (id: number) =>
    api.delete(`/inverters/${id}`),
}
