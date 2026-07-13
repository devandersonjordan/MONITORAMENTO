import api from '@/lib/api'
import type { AuthResponse } from '@/types'

export const authApi = {
  login: (email: string, password: string) =>
    api.post<AuthResponse>('/auth/login', { email, password }),

  register: (data: { name: string; email: string; password: string; password_confirmation: string }) =>
    api.post<AuthResponse>('/auth/register', data),

  logout: () => api.post('/auth/logout'),

  me: () => api.get<{ user: AuthResponse['user'] }>('/auth/me'),
}
