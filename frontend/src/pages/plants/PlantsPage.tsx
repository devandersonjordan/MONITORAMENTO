import { useState } from 'react'
import { Link } from 'react-router-dom'
import { useQuery } from '@tanstack/react-query'
import { Plus, Search, Zap } from 'lucide-react'
import { plantsApi } from '@/services/api/plants'
import { useDebounce } from '@/hooks/useDebounce'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { Skeleton } from '@/components/ui/skeleton'

const statusMap: Record<string, { label: string; variant: 'success' | 'warning' | 'destructive' }> = {
  active: { label: 'Ativa', variant: 'success' },
  maintenance: { label: 'Manutenção', variant: 'warning' },
  inactive: { label: 'Inativa', variant: 'destructive' },
}

export default function PlantsPage() {
  const [search, setSearch] = useState('')
  const [page, setPage] = useState(1)
  const debouncedSearch = useDebounce(search)

  const { data, isLoading } = useQuery({
    queryKey: ['plants', page, debouncedSearch],
    queryFn: () => plantsApi.list({ page, search: debouncedSearch }).then(r => r.data),
  })

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Usinas</h1>
          <p className="text-muted-foreground">Gerenciar usinas fotovoltaicas</p>
        </div>
        <Button asChild>
          <Link to="/plants/new"><Plus className="mr-2 h-4 w-4" />Nova Usina</Link>
        </Button>
      </div>

      <Card>
        <CardHeader>
          <div className="relative max-w-sm">
            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
            <Input
              placeholder="Buscar por nome ou endereço..."
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
                    <TableHead>Nome</TableHead>
                    <TableHead>Cliente</TableHead>
                    <TableHead>Potência</TableHead>
                    <TableHead>Data Instalação</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead className="text-right">Ações</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {data?.data.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={6} className="text-center py-8 text-muted-foreground">
                        <Zap className="mx-auto h-8 w-8 mb-2 opacity-50" />
                        Nenhuma usina encontrada
                      </TableCell>
                    </TableRow>
                  )}
                  {data?.data.map(plant => {
                    const status = statusMap[plant.status] || statusMap.active
                    return (
                      <TableRow key={plant.id}>
                        <TableCell className="font-medium">{plant.name}</TableCell>
                        <TableCell>{plant.client?.name || '—'}</TableCell>
                        <TableCell>{plant.power_kwp} kWp</TableCell>
                        <TableCell>{new Date(plant.installation_date).toLocaleDateString('pt-BR')}</TableCell>
                        <TableCell><Badge variant={status.variant}>{status.label}</Badge></TableCell>
                        <TableCell className="text-right">
                          <Button variant="ghost" size="sm" asChild>
                            <Link to={`/plants/${plant.id}/edit`}>Editar</Link>
                          </Button>
                        </TableCell>
                      </TableRow>
                    )
                  })}
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
