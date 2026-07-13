import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { FileText, Download, Eye, Search } from 'lucide-react'
import { invoicesApi } from '@/services/api/invoices'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Skeleton } from '@/components/ui/skeleton'

const ocrStatusMap: Record<string, { label: string; variant: 'default' | 'success' | 'warning' | 'destructive' }> = {
  completed: { label: 'Processada', variant: 'success' },
  pending: { label: 'Pendente', variant: 'warning' },
  failed: { label: 'Falhou', variant: 'destructive' },
  no_pdf: { label: 'Sem PDF', variant: 'default' },
}

export default function InvoicesPage() {
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)

  const { data, isLoading } = useQuery({
    queryKey: ['invoices', search, page],
    queryFn: () => invoicesApi.list({ search, page, per_page: 15 }).then(r => r.data),
  })

  const handleDownload = async (id: number) => {
    try {
      const response = await invoicesApi.downloadPdf(id)
      const url = URL.createObjectURL(response.data)
      const a = document.createElement('a')
      a.href = url
      a.download = `fatura_${id}.pdf`
      a.click()
      URL.revokeObjectURL(url)
    } catch {
      // toast error
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Faturas</h1>
          <p className="text-muted-foreground">Faturas de energia baixadas da Equatorial</p>
        </div>
      </div>

      <Card>
        <CardHeader>
          <div className="flex items-center gap-4">
            <div className="relative flex-1 max-w-sm">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Buscar por cliente ou UC..."
                value={search}
                onChange={e => { setSearch(e.target.value); setPage(1) }}
                className="pl-9"
              />
            </div>
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
                    <TableHead>Cliente</TableHead>
                    <TableHead>UC</TableHead>
                    <TableHead>Competência</TableHead>
                    <TableHead>Vencimento</TableHead>
                    <TableHead>Valor</TableHead>
                    <TableHead>Consumo</TableHead>
                    <TableHead>OCR</TableHead>
                    <TableHead className="w-[100px]">Ações</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {data?.data?.map((invoice: any) => {
                    const status = ocrStatusMap[invoice.ocr_status] ?? { label: invoice.ocr_status, variant: 'default' as const }
                    return (
                      <TableRow key={invoice.id}>
                        <TableCell className="font-medium">{invoice.client?.name ?? '—'}</TableCell>
                        <TableCell>{invoice.client?.uc_number ?? '—'}</TableCell>
                        <TableCell>{invoice.competence ? new Date(invoice.competence).toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' }) : '—'}</TableCell>
                        <TableCell>{invoice.due_date ? new Date(invoice.due_date).toLocaleDateString('pt-BR') : '—'}</TableCell>
                        <TableCell>
                          {invoice.amount_cents
                            ? new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(invoice.amount_cents / 100)
                            : '—'}
                        </TableCell>
                        <TableCell>{invoice.consumption_kwh ? `${invoice.consumption_kwh} kWh` : '—'}</TableCell>
                        <TableCell>
                          <Badge variant={status.variant}>{status.label}</Badge>
                        </TableCell>
                        <TableCell>
                          <div className="flex gap-1">
                            {invoice.pdf_path && (
                              <Button variant="ghost" size="sm" onClick={() => handleDownload(invoice.id)}>
                                <Download className="h-4 w-4" />
                              </Button>
                            )}
                          </div>
                        </TableCell>
                      </TableRow>
                    )
                  })}
                  {(!data?.data || data.data.length === 0) && (
                    <TableRow>
                      <TableCell colSpan={8} className="text-center py-8 text-muted-foreground">
                        Nenhuma fatura encontrada
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
