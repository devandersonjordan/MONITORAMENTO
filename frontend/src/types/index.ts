export interface Company {
  id: number
  name: string
  logo_path: string | null
  cnpj: string
  phone: string | null
  email: string
  plan: string
  max_clients: number
  max_plants: number
  status: string
  created_at: string
  updated_at: string
}

export interface User {
  id: number
  company_id: number | null
  name: string
  email: string
  role: 'admin' | 'employee' | 'client'
  phone: string | null
  whatsapp: string | null
  cpf_cnpj: string | null
  address: string | null
  city: string | null
  state: string | null
  zip: string | null
  distributor: string | null
  uc_number: string | null
  meter_number: string | null
  company?: Company
  created_at: string
  updated_at: string
}

export interface Plant {
  id: number
  company_id: number
  client_id: number
  name: string
  power_kwp: number
  installation_date: string
  module_model: string | null
  module_qty: number | null
  inverter_model: string | null
  inverter_power_kw: number | null
  latitude: number | null
  longitude: number | null
  address: string | null
  installer_company: string | null
  status: string
  client?: User
  inverters?: Inverter[]
  created_at: string
  updated_at: string
}

export interface Inverter {
  id: number
  plant_id: number
  company_id: number
  brand: 'elekeeper' | 'goodwe' | 'sungrow' | 'deye'
  model: string | null
  serial_number: string | null
  status: string
  last_communication_at: string | null
  plant?: Plant
  readings?: InverterReading[]
  alerts?: InverterAlert[]
  created_at: string
  updated_at: string
}

export interface InverterReading {
  id: number
  inverter_id: number
  recorded_at: string
  power_w: number
  voltage_v: number
  current_a: number
  frequency_hz: number
  temperature_c: number
  daily_kwh: number
  monthly_kwh: number
  yearly_kwh: number
  total_kwh: number
  efficiency_pct: number
  status: string
}

export interface InverterAlert {
  id: number
  inverter_id: number
  company_id: number
  type: string
  severity: 'info' | 'warning' | 'critical'
  message: string
  data: Record<string, unknown> | null
  resolved_at: string | null
  created_at: string
}

export interface Invoice {
  id: number
  client_id: number
  company_id: number
  competence: string
  due_date: string | null
  amount_cents: number
  consumption_kwh: number | null
  injected_kwh: number | null
  compensated_kwh: number | null
  previous_balance_kwh: number | null
  current_balance_kwh: number | null
  credits_received_kwh: number | null
  credits_used_kwh: number | null
  tariff: number | null
  flag: string | null
  icms_value: number | null
  pis_value: number | null
  cofins_value: number | null
  public_lighting_value: number | null
  pdf_path: string | null
  ocr_status: string
}

export interface DashboardStats {
  total_clients: number
  total_plants: number
  total_inverters: number
  energy_today_kwh: number
  energy_month_kwh: number
  energy_year_kwh: number
  total_savings_brl: number
  total_credits_kwh: number
  pending_invoices: number
  offline_inverters: number
  active_alerts: number
}

export interface PaginatedResponse<T> {
  data: T[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number | null
  to: number | null
}

export interface ApiResponse<T> {
  data: T
  message?: string
}

export interface AuthResponse {
  user: User
  token: string
  token_type: string
}
