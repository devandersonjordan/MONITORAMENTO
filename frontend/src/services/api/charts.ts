import api from '@/lib/api'

export const chartsApi = {
  dailyGeneration: (params?: { plant_id?: number; days?: number }) =>
    api.get('/charts/daily-generation', { params }),

  monthlyGeneration: (params?: { year?: number }) =>
    api.get('/charts/monthly-generation', { params }),

  yearlyGeneration: () =>
    api.get('/charts/yearly-generation'),

  productionVsConsumption: (params?: { client_id?: number; months?: number }) =>
    api.get('/charts/production-vs-consumption', { params }),

  savingsHistory: (params?: { client_id?: number; months?: number }) =>
    api.get('/charts/savings-history', { params }),

  realtimePower: (params?: { plant_id?: number }) =>
    api.get('/charts/realtime-power', { params }),
}
