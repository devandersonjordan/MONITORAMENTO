import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Plus, Search, Radio } from 'lucide-react'
import { invertersApi } from '@/services/api/inverters'
import { useDebounce } from '@/hooks/useDebounce'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'

const brandLabels: Record<string, string> = {
  elekeeper: 'Elekeeper',
  goodwe: 'GoodWe',
  sungrow: 'Sungrow',
  deye: 'Deye',
}

const statusVariant: Record<string, 'success' | 'warning' | 'destructive'> = {
  online: 'success',
  warning: 'warning',
  offline: 'destructive',
}

export default function InvertersPage() {
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const debouncedSearch = useDebounce(search)

  const { data, isLoading } = useQuery({
    queryKey: ['inverters', page, debouncedSearch],
    queryFn: () => invertersApi.list({ page, search: debouncedSearch }).then(r => r.data),
  })

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Inversores</h1>
          <p className="text-muted-foreground">Gerenciar inversores das usinas</p>
        </div>
        <Button asChild>
          <Link to="/inverters/new"><Plus className="mr-2 h-4 w-4" />Novo Inversor</Link>
        </Button>
      </div>

      <Card>
        <CardHeader>
          <div className="relative max-w-sm">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input
              placeholder="Buscar por serial, modelo ou marca..."
              className="pl-10"
              value={search}
              onChange={e => { setSearch(e.target.value); setPage(1) }}
            />
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
                    <TableHead>Marca</TableHead>
                    <TableHead>Modelo</TableHead>
                    <TableHead>Serial</TableHead>
                    <TableHead>Usina</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Última Comunicação</TableHead>
                    <TableHead className="text-right">Ações</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {data?.data.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={7} className="text-center py-8 text-muted-foreground">
                        <Radio className="mx-auto h-8 w-8 mb-2 opacity-50" />
                        Nenhum inversor encontrado
                      </TableCell>
                    </TableRow>
                  )}
                  {data?.data.map(inv => (
                    <TableRow key={inv.id}>
                      <TableCell>
                        <Badge variant="outline">{brandLabels[inv.brand] || inv.brand}</Badge>
                      </TableCell>
                      <TableCell>{inv.model || '—'}</TableCell>
                      <TableCell className="font-mono text-xs">{inv.serial_number || '—'}</TableCell>
                      <TableCell>{inv.plant?.name || '—'}</TableCell>
                      <TableCell>
                        <Badge variant={statusVariant[inv.status] || 'secondary'}>
                          {inv.status === 'online' ? 'Online' : inv.status === 'offline' ? 'Offline' : inv.status}
                        </Badge>
                      </TableCell>
                      <TableCell>
                        {inv.last_communication_at
                          ? new Date(inv.last_communication_at).toLocaleString('pt-BR')
                          : '—'}
                      </TableCell>
                      <TableCell className="text-right">
                        <Button variant="ghost" size="sm" asChild>
                          <Link to={`/inverters/${inv.id}/edit`}>Editar</Link>
                        </Button>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>

              {data && data.last_page > 1 && (
                <div className="flex items-center justify-between mt-4">
                  <p className="text-sm text-muted-foreground">
                    Mostrando {data.from}–{data.to} de {data.total}
                  </p>
                  <div className="flex gap-2">
                    <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage(p => p - 1)}>Anterior</Button>
                    <Button variant="outline" size="sm" disabled={page >= data.last_page} onClick={() => setPage(p => p + 1)}>Próxima</Button>
                  </div>
                </div>
              )}
            </>
          )}
        </CardContent>
      </Card>
    </div>
  )
}
