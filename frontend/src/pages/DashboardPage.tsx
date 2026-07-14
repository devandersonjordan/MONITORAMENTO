import { useQuery } from '@tanstack/react-query'
import {
  Users, Zap, Sun, Calendar, TrendingUp,
  DollarSign, Battery, FileText, WifiOff, AlertTriangle,
} from 'lucide-react'
import ReactApexChart from 'react-apexcharts'
import { dashboardApi } from '@/services/api/dashboard'
import { chartsApi } from '@/services/api/charts'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import type { ApexOptions } from 'apexcharts'

function formatEnergy(kwh: number): string {
  if (kwh >= 1000) return `${(kwh / 1000).toFixed(1)} MWh`
  return `${kwh.toFixed(1)} kWh`
}

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(value)
}

const statCards = [
  { key: 'total_clients' as const, label: 'Clientes', icon: Users, color: 'text-blue-600', format: (v: number) => String(v) },
  { key: 'total_plants' as const, label: 'Usinas', icon: Zap, color: 'text-green-600', format: (v: number) => String(v) },
  { key: 'energy_today_kwh' as const, label: 'Energia Hoje', icon: Sun, color: 'text-yellow-600', format: formatEnergy },
  { key: 'energy_month_kwh' as const, label: 'Energia Mês', icon: Calendar, color: 'text-orange-600', format: formatEnergy },
  { key: 'energy_year_kwh' as const, label: 'Energia Ano', icon: TrendingUp, color: 'text-purple-600', format: formatEnergy },
  { key: 'total_savings_brl' as const, label: 'Economia Total', icon: DollarSign, color: 'text-emerald-600', format: formatCurrency },
  { key: 'total_credits_kwh' as const, label: 'Créditos', icon: Battery, color: 'text-cyan-600', format: formatEnergy },
  { key: 'pending_invoices' as const, label: 'Faturas Pendentes', icon: FileText, color: 'text-amber-600', format: (v: number) => String(v) },
  { key: 'offline_inverters' as const, label: 'Inversores Offline', icon: WifiOff, color: 'text-red-600', format: (v: number) => String(v) },
  { key: 'active_alerts' as const, label: 'Alarmes Ativos', icon: AlertTriangle, color: 'text-rose-600', format: (v: number) => String(v) },
]

const baseChartOptions: ApexOptions = {
  chart: {
    toolbar: { show: false },
    fontFamily: 'inherit',
  },
  theme: { mode: 'light' },
  grid: { borderColor: 'hsl(var(--border))' },
  xaxis: {
    labels: { style: { colors: 'hsl(var(--muted-foreground))' } },
  },
  yaxis: {
    labels: { style: { colors: 'hsl(var(--muted-foreground))' } },
  },
  tooltip: { theme: 'dark' },
}

function DailyGenerationChart() {
  const { data, isLoading } = useQuery({
    queryKey: ['chart-daily'],
    queryFn: () => chartsApi.dailyGeneration({ days: 30 }).then(r => r.data.data),
  })

  if (isLoading) return <Skeleton className="h-[300px]" />

  const options: ApexOptions = {
    ...baseChartOptions,
    chart: { ...baseChartOptions.chart, type: 'area' },
    colors: ['#f59e0b'],
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.5, opacityTo: 0.1 } },
    stroke: { curve: 'smooth', width: 2 },
    xaxis: {
      ...baseChartOptions.xaxis,
      categories: data?.map((d: { date: string }) => {
        const dt = new Date(d.date)
        return `${dt.getDate()}/${dt.getMonth() + 1}`
      }) ?? [],
    },
    yaxis: {
      ...baseChartOptions.yaxis,
      title: { text: 'kWh', style: { color: 'hsl(var(--muted-foreground))' } },
    },
    dataLabels: { enabled: false },
  }

  return (
    <ReactApexChart
      type="area"
      height={300}
      options={options}
      series={[{ name: 'Geração (kWh)', data: data?.map((d: { total_kwh: number }) => d.total_kwh) ?? [] }]}
    />
  )
}

function MonthlyGenerationChart() {
  const { data, isLoading } = useQuery({
    queryKey: ['chart-monthly'],
    queryFn: () => chartsApi.monthlyGeneration().then(r => r.data.data),
  })

  if (isLoading) return <Skeleton className="h-[300px]" />

  const options: ApexOptions = {
    ...baseChartOptions,
    chart: { ...baseChartOptions.chart, type: 'bar' },
    colors: ['#2563eb'],
    plotOptions: { bar: { borderRadius: 6, columnWidth: '60%' } },
    xaxis: {
      ...baseChartOptions.xaxis,
      categories: data?.map((d: { label: string }) => d.label) ?? [],
    },
    yaxis: {
      ...baseChartOptions.yaxis,
      title: { text: 'kWh', style: { color: 'hsl(var(--muted-foreground))' } },
    },
    dataLabels: { enabled: false },
  }

  return (
    <ReactApexChart
      type="bar"
      height={300}
      options={options}
      series={[{ name: 'Geração (kWh)', data: data?.map((d: { total_kwh: number }) => d.total_kwh) ?? [] }]}
    />
  )
}

function ProductionVsConsumptionChart() {
  const { data, isLoading } = useQuery({
    queryKey: ['chart-prod-cons'],
    queryFn: () => chartsApi.productionVsConsumption({ months: 12 }).then(r => r.data.data),
  })

  if (isLoading) return <Skeleton className="h-[300px]" />

  const options: ApexOptions = {
    ...baseChartOptions,
    chart: { ...baseChartOptions.chart, type: 'bar' },
    colors: ['#16a34a', '#dc2626', '#2563eb'],
    plotOptions: { bar: { borderRadius: 4, columnWidth: '70%' } },
    xaxis: {
      ...baseChartOptions.xaxis,
      categories: data?.map((d: { label: string }) => d.label) ?? [],
    },
    yaxis: {
      ...baseChartOptions.yaxis,
      title: { text: 'kWh', style: { color: 'hsl(var(--muted-foreground))' } },
    },
    dataLabels: { enabled: false },
    legend: { position: 'top' },
  }

  return (
    <ReactApexChart
      type="bar"
      height={300}
      options={options}
      series={[
        { name: 'Produção', data: data?.map((d: { production_kwh: number }) => d.production_kwh) ?? [] },
        { name: 'Consumo', data: data?.map((d: { consumption_kwh: number }) => d.consumption_kwh) ?? [] },
        { name: 'Compensação', data: data?.map((d: { compensated_kwh: number }) => d.compensated_kwh) ?? [] },
      ]}
    />
  )
}

function SavingsChart() {
  const { data, isLoading } = useQuery({
    queryKey: ['chart-savings'],
    queryFn: () => chartsApi.savingsHistory({ months: 12 }).then(r => r.data.data),
  })

  if (isLoading) return <Skeleton className="h-[300px]" />

  const options: ApexOptions = {
    ...baseChartOptions,
    chart: { ...baseChartOptions.chart, type: 'line' },
    colors: ['#16a34a', '#f59e0b'],
    stroke: { curve: 'smooth', width: 3 },
    xaxis: {
      ...baseChartOptions.xaxis,
      categories: data?.map((d: { label: string }) => d.label) ?? [],
    },
    yaxis: {
      ...baseChartOptions.yaxis,
      title: { text: 'R$', style: { color: 'hsl(var(--muted-foreground))' } },
      labels: {
        style: { colors: 'hsl(var(--muted-foreground))' },
        formatter: (v: number) => `R$ ${v.toFixed(0)}`,
      },
    },
    dataLabels: { enabled: false },
    legend: { position: 'top' },
    markers: { size: 4 },
  }

  return (
    <ReactApexChart
      type="line"
      height={300}
      options={options}
      series={[
        { name: 'Economia', data: data?.map((d: { savings_brl: number }) => d.savings_brl) ?? [] },
        { name: 'Fatura', data: data?.map((d: { invoice_brl: number }) => d.invoice_brl) ?? [] },
      ]}
    />
  )
}

function RealtimePowerChart() {
  const { data, isLoading } = useQuery({
    queryKey: ['chart-realtime'],
    queryFn: () => chartsApi.realtimePower().then(r => r.data.data),
    refetchInterval: 60000,
  })

  if (isLoading) return <Skeleton className="h-[300px]" />

  const options: ApexOptions = {
    ...baseChartOptions,
    chart: { ...baseChartOptions.chart, type: 'area' },
    colors: ['#8b5cf6'],
    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } },
    stroke: { curve: 'smooth', width: 2 },
    xaxis: {
      ...baseChartOptions.xaxis,
      categories: data?.map((d: { hour: string }) => {
        const dt = new Date(d.hour)
        return `${dt.getHours()}:00`
      }) ?? [],
    },
    yaxis: {
      ...baseChartOptions.yaxis,
      title: { text: 'Watts', style: { color: 'hsl(var(--muted-foreground))' } },
      labels: {
        style: { colors: 'hsl(var(--muted-foreground))' },
        formatter: (v: number) => v >= 1000 ? `${(v / 1000).toFixed(1)} kW` : `${v.toFixed(0)} W`,
      },
    },
    dataLabels: { enabled: false },
  }

  return (
    <ReactApexChart
      type="area"
      height={300}
      options={options}
      series={[{ name: 'Potência Total', data: data?.map((d: { total_power_w: number }) => d.total_power_w) ?? [] }]}
    />
  )
}

export default function DashboardPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['dashboard-stats'],
    queryFn: () => dashboardApi.getStats().then(r => r.data.data),
  })

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Dashboard</h1>
        <p className="text-muted-foreground">Visão geral do sistema de monitoramento solar</p>
      </div>

      <div className="grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        {statCards.map(card => (
          <Card key={card.key}>
            <CardHeader className="flex flex-row items-center justify-between pb-2">
              <CardTitle className="text-sm font-medium text-muted-foreground">{card.label}</CardTitle>
              <card.icon className={`h-5 w-5 ${card.color}`} />
            </CardHeader>
            <CardContent>
              {isLoading ? (
                <Skeleton className="h-8 w-24" />
              ) : (
                <div className="text-2xl font-bold">
                  {data ? card.format(data[card.key]) : '—'}
                </div>
              )}
            </CardContent>
          </Card>
        ))}
      </div>

      <div className="grid gap-4 grid-cols-1 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Potência em Tempo Real (24h)</CardTitle>
          </CardHeader>
          <CardContent>
            <RealtimePowerChart />
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle>Geração Diária (30 dias)</CardTitle>
          </CardHeader>
          <CardContent>
            <DailyGenerationChart />
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-4 grid-cols-1 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Geração Mensal</CardTitle>
          </CardHeader>
          <CardContent>
            <MonthlyGenerationChart />
          </CardContent>
        </Card>
        <Card>
          <CardHeader>
            <CardTitle>Produção vs Consumo vs Compensação</CardTitle>
          </CardHeader>
          <CardContent>
            <ProductionVsConsumptionChart />
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Economia vs Fatura</CardTitle>
        </CardHeader>
        <CardContent>
          <SavingsChart />
        </CardContent>
      </Card>
    </div>
  )
}
