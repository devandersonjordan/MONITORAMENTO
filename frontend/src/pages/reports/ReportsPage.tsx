import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { FileText, Download, Eye, Plus } from 'lucide-react'
import { reportsApi } from '@/services/api/reports'
import { clientsApi } from '@/services/api/clients'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Skeleton } from '@/components/ui/skeleton'
import { useToast } from '@/components/ui/toaster'

export default function ReportsPage() {
  const [page, setPage] = useState(1)
  const [showGenerate, setShowGenerate] = useState(false)
  const [selectedClient, setSelectedClient] = useState('')
  const [selectedMonth, setSelectedMonth] = useState('')
  const queryClient = useQueryClient()
  const { addToast } = useToast()

  const { data, isLoading } = useQuery({
    queryKey: ['reports', page],
    queryFn: () => reportsApi.list({ page, per_page: 15 }).then(r => r.data),
  })

  const { data: clients } = useQuery({
    queryKey: ['clients-select'],
    queryFn: () => clientsApi.list({ per_page: 100 }).then(r => r.data.data),
    enabled: showGenerate,
  })

  const generateMutation = useMutation({
    mutationFn: (data: { client_id: number; month: string }) => reportsApi.generate(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['reports'] })
      setShowGenerate(false)
      addToast({ title: 'Relatório gerado com sucesso', variant: 'success' })
    },
    onError: () => {
      addToast({ title: 'Erro ao gerar relatório', variant: 'destructive' })
    },
  })

  const handleDownload = async (id: number) => {
    try {
      const response = await reportsApi.downloadPdf(id)
      const url = URL.createObjectURL(response.data)
      const a = document.createElement('a')
      a.href = url
      a.download = `relatorio_${id}.pdf`
      a.click()
      URL.revokeObjectURL(url)
    } catch {
      addToast({ title: 'Erro ao baixar PDF', variant: 'destructive' })
    }
  }

  const handleGenerate = () => {
    if (!selectedClient || !selectedMonth) return
    generateMutation.mutate({ client_id: Number(selectedClient), month: selectedMonth })
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Relatórios</h1>
          <p className="text-muted-foreground">Relatórios inteligentes cruzando dados de inversores e faturas</p>
        </div>
        <Button onClick={() => setShowGenerate(!showGenerate)}>
          <Plus className="h-4 w-4 mr-2" /> Gerar Relatório
        </Button>
      </div>

      {showGenerate && (
        <Card>
          <CardHeader><CardTitle>Gerar Novo Relatório</CardTitle></CardHeader>
          <CardContent>
            <div className="flex gap-4 items-end">
              <div className="flex-1">
                <label className="text-sm font-medium mb-1 block">Cliente</label>
                <select
                  className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                  value={selectedClient}
                  onChange={e => setSelectedClient(e.target.value)}
                >
                  <option value="">Selecione...</option>
                  {clients?.map((c: any) => (
                    <option key={c.id} value={c.id}>{c.name}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="text-sm font-medium mb-1 block">Mês</label>
                <input
                  type="month"
                  className="rounded-md border border-input bg-background px-3 py-2 text-sm"
                  value={selectedMonth}
                  onChange={e => setSelectedMonth(e.target.value)}
                />
              </div>
              <Button onClick={handleGenerate} disabled={generateMutation.isPending || !selectedClient || !selectedMonth}>
                {generateMutation.isPending ? 'Gerando...' : 'Gerar'}
              </Button>
            </div>
          </CardContent>
        </Card>
      )}

      <Card>
        <CardContent className="pt-6">
          {isLoading ? (
            <div className="space-y-3">
              {Array.from({ length: 5 }).map((_, i) => <Skeleton key={i} className="h-12 w-full" />)}
            </div>
          ) : (
            <>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>ID</TableHead>
                    <TableHead>Tipo</TableHead>
                    <TableHead>Período</TableHead>
                    <TableHead>Cliente</TableHead>
                    <TableHead>Produção</TableHead>
                    <TableHead>PR</TableHead>
                    <TableHead>Economia</TableHead>
                    <TableHead className="w-[100px]">Ações</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {data?.data?.map((report: any) => {
                    const d = report.data ?? {}
                    return (
                      <TableRow key={report.id}>
                        <TableCell className="font-mono text-sm">#{report.id}</TableCell>
                        <TableCell>
                          <Badge variant="default">{report.type === 'monthly' ? 'Mensal' : report.type}</Badge>
                        </TableCell>
                        <TableCell>
                          {report.period_start ? new Date(report.period_start).toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' }) : '—'}
                        </TableCell>
                        <TableCell>{d.client?.name ?? '—'}</TableCell>
                        <TableCell>{d.production?.total_kwh ? `${d.production.total_kwh} kWh` : '—'}</TableCell>
                        <TableCell>{d.production?.performance_ratio ? `${d.production.performance_ratio}%` : '—'}</TableCell>
                        <TableCell>
                          {d.financial?.savings_brl
                            ? new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(d.financial.savings_brl)
                            : '—'}
                        </TableCell>
                        <TableCell>
                          <Button variant="ghost" size="sm" onClick={() => handleDownload(report.id)}>
                            <Download className="h-4 w-4" />
                          </Button>
                        </TableCell>
                      </TableRow>
                    )
                  })}
                  {(!data?.data || data.data.length === 0) && (
                    <TableRow>
                      <TableCell colSpan={8} className="text-center py-8 text-muted-foreground">
                        Nenhum relatório gerado ainda
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
