import { Routes, Route, Navigate } from 'react-router-dom'
import AppLayout from '@/components/layout/AppLayout'
import LoginPage from '@/pages/LoginPage'
import DashboardPage from '@/pages/DashboardPage'
import CompaniesPage from '@/pages/companies/CompaniesPage'
import CompanyFormPage from '@/pages/companies/CompanyFormPage'
import ClientsPage from '@/pages/clients/ClientsPage'
import ClientFormPage from '@/pages/clients/ClientFormPage'
import PlantsPage from '@/pages/plants/PlantsPage'
import PlantFormPage from '@/pages/plants/PlantFormPage'
import InvertersPage from '@/pages/inverters/InvertersPage'
import InverterFormPage from '@/pages/inverters/InverterFormPage'
import InvoicesPage from '@/pages/invoices/InvoicesPage'
import ReportsPage from '@/pages/reports/ReportsPage'
import AiAssistantPage from '@/pages/ai/AiAssistantPage'
import AlertsPage from '@/pages/alerts/AlertsPage'
import SettingsPage from '@/pages/settings/SettingsPage'

export default function AppRouter() {
  return (
    <Routes>
      <Route path="/login" element={<LoginPage />} />

      <Route element={<AppLayout />}>
        <Route path="/" element={<Navigate to="/dashboard" replace />} />
        <Route path="/dashboard" element={<DashboardPage />} />

        <Route path="/companies" element={<CompaniesPage />} />
        <Route path="/companies/new" element={<CompanyFormPage />} />
        <Route path="/companies/:id/edit" element={<CompanyFormPage />} />

        <Route path="/clients" element={<ClientsPage />} />
        <Route path="/clients/new" element={<ClientFormPage />} />
        <Route path="/clients/:id/edit" element={<ClientFormPage />} />

        <Route path="/plants" element={<PlantsPage />} />
        <Route path="/plants/new" element={<PlantFormPage />} />
        <Route path="/plants/:id/edit" element={<PlantFormPage />} />

        <Route path="/inverters" element={<InvertersPage />} />
        <Route path="/inverters/new" element={<InverterFormPage />} />
        <Route path="/inverters/:id/edit" element={<InverterFormPage />} />

        <Route path="/invoices" element={<InvoicesPage />} />
        <Route path="/reports" element={<ReportsPage />} />
        <Route path="/ai" element={<AiAssistantPage />} />
        <Route path="/alerts" element={<AlertsPage />} />
        <Route path="/settings" element={<SettingsPage />} />
      </Route>

      <Route path="*" element={<Navigate to="/dashboard" replace />} />
    </Routes>
  )
}
