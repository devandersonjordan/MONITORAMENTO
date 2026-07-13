import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { AlertTriangle, CheckCircle, Filter } from 'lucide-react'
import { alertsApi } from '@/services/api/alerts'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Skeleton } from '@/components/ui/skeleton'
import { useToast } from '@/components/ui/toaster'

const severityMap: Record<string, { label: string; variant: 'default' | 'success' | 'warning' | 'destructive' }> = {
  critical: { label: 'Crítico', variant: 'destructive' },
  warning: { label: 'Aviso', variant: 'warning' },
  info: { label: 'Info', variant: 'default' },
}

const typeLabels: Record<string, string> = {
  no_communication: 'Sem Comunicação',
  low_production: 'Produção Baixa',
  high_temperature: 'Temperatura Alta',
  low_efficiency: 'Eficiência Baixa',
  production_drop: 'Queda de Produção',
  voltage_instability: 'Instabilidade de Tensão',
}

export default function AlertsPage() {
  const [page, setPage] = useState(1)
  const [showUnresolved, setShowUnresolved] = useState(true)
  const queryClient = useQueryClient()
  const { addToast } = useToast()

  const { data: stats } = useQuery({
    queryKey: ['alert-stats'],
    queryFn: () => alertsApi.stats().then(r => r.data.data),
  })

  const { data, isLoading } = useQuery({
    queryKey: ['alerts', page, showUnresolved],
    queryFn: () => alertsApi.list({ page, per_page: 20, unresolved: showUnresolved }).then(r => r.data),
  })

  const resolveMutation = useMutation({
    mutationFn: (id: number) => alertsApi.resolve(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['alerts'] })
      queryClient.invalidateQueries({ queryKey: ['alert-stats'] })
      addToast({ title: 'Alerta resolvido', variant: 'success' })
    },
  })

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Alertas</h1>
        <p className="text-muted-foreground">Monitoramento de anomalias e alarmes dos inversores</p>
      </div>

      <div className="grid gap-4 grid-cols-1 sm:grid-cols-3">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Não Resolvidos</CardTitle>
            <AlertTriangle className="h-5 w-5 text-red-600" />
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold">{stats?.total_unresolved ?? 0}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Críticos</CardTitle>
            <AlertTriangle className="h-5 w-5 text-red-600" />
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-red-600">{stats?.by_severity?.critical ?? 0}</div>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between pb-2">
            <CardTitle className="text-sm font-medium text-muted-foreground">Avisos</CardTitle>
            <AlertTriangle className="h-5 w-5 text-yellow-600" />
          </CardHeader>
          <CardContent>
            <div className="text-3xl font-bold text-yellow-600">{stats?.by_severity?.warning ?? 0}</div>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle>Lista de Alertas</CardTitle>
            <Button
              variant={showUnresolved ? 'default' : 'outline'}
              size="sm"
              onClick={() => { setShowUnresolved(!showUnresolved); setPage(1) }}
            >
              <Filter className="h-4 w-4 mr-2" />
              {showUnresolved ? 'Não Resolvidos' : 'Todos'}
            </Button>
          </div>
        </CardHeader>
        <CardContent>
          {isLoading ? (
            <div className="space-y-3">
              {Array.from({ length: 5 }).map((_, i) => <Skeleton key={i} className="h-12 w-full" />)}
            </div>
          ) : (
            <>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Severidade</TableHead>
                    <TableHead>Tipo</TableHead>
                    <TableHead>Mensagem</TableHead>
                    <TableHead>Inversor</TableHead>
                    <TableHead>Data</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead className="w-[100px]">Ações</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {data?.data?.map((alert: any) => {
                    const sev = severityMap[alert.severity] ?? { label: alert.severity, variant: 'default' as const }
                    return (
                      <TableRow key={alert.id}>
                        <TableCell>
                          <Badge variant={sev.variant}>{sev.label}</Badge>
                        </TableCell>
                        <TableCell className="font-medium">{typeLabels[alert.type] ?? alert.type}</TableCell>
                        <TableCell className="max-w-xs truncate">{alert.message}</TableCell>
                        <TableCell className="font-mono text-sm">{alert.inverter?.serial_number ?? '—'}</TableCell>
                        <TableCell>{new Date(alert.created_at).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' })}</TableCell>
                        <TableCell>
                          {alert.resolved_at ? (
                            <Badge variant="success">Resolvido</Badge>
                          ) : (
                            <Badge variant="warning">Aberto</Badge>
                          )}
                        </TableCell>
                        <TableCell>
                          {!alert.resolved_at && (
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => resolveMutation.mutate(alert.id)}
                              disabled={resolveMutation.isPending}
                            >
                              <CheckCircle className="h-4 w-4" />
                            </Button>
                          )}
                        </TableCell>
                      </TableRow>
                    )
                  })}
                  {(!data?.data || data.data.length === 0) && (
                    <TableRow>
                      <TableCell colSpan={7} className="text-center py-8 text-muted-foreground">
                        Nenhum alerta encontrado
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>

              {data?.last_page > 1 && (
                <div className="flex justify-center gap-2 mt-4">
                  <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage(p => p - 1)}>Anterior</Button>
                  <span className="flex items-center text-sm text-muted-foreground">Página {page} de {data.last_page}</span>
                  <Button variant="outline" size="sm" disabled={page >= data.last_page} onClick={() => setPage(p => p + 1)}>Próxima</Button>
                </div>
              )}
            </>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
