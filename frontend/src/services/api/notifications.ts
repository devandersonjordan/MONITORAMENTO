import api from '@/lib/api'

export const notificationsApi = {
  list: (params?: Record<string, unknown>) =>
    api.get('/notifications', { params }),

  unreadCount: () =>
    api.get('/notifications/unread-count'),

  markAsRead: (id: string) =>
    api.patch(`/notifications/${id}/read`),

  markAllAsRead: () =>
    api.post('/notifications/mark-all-read'),
}
