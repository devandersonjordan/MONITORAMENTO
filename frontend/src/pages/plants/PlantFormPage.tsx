import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { zodResolver } from '@hookform/resolvers/zod'
import { Loader2 } from 'lucide-react'
import { plantsApi } from '@/services/api/plants'
import { clientsApi } from '@/services/api/clients'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { toast } from '@/components/ui/toaster'

const plantSchema = z.object({
  client_id: z.coerce.number().min(1, 'Selecione um cliente'),
  name: z.string().min(1, 'Nome obrigatório'),
  power_kwp: z.coerce.number().min(0.1, 'Potência deve ser maior que 0'),
  installation_date: z.string().min(1, 'Data obrigatória'),
  module_model: z.string().optional(),
  module_qty: z.coerce.number().optional(),
  inverter_model: z.string().optional(),
  inverter_power_kw: z.coerce.number().optional(),
  latitude: z.coerce.number().optional(),
  longitude: z.coerce.number().optional(),
  address: z.string().optional(),
  installer_company: z.string().optional(),
  status: z.string().default('active'),
})

type PlantForm = z.infer<typeof plantSchema>

export default function PlantFormPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const isEditing = !!id

  const { data: plant } = useQuery({
    queryKey: ['plant', id],
    queryFn: () => plantsApi.get(Number(id)).then(r => r.data.data),
    enabled: isEditing,
  })

  const { data: clientsData } = useQuery({
    queryKey: ['clients-select'],
    queryFn: () => clientsApi.list({ per_page: 1000 }).then(r => r.data),
  })

  const { register, handleSubmit, setValue, formState: { errors } } = useForm<PlantForm>({
    resolver: zodResolver(plantSchema),
    values: plant ? {
      client_id: plant.client_id,
      name: plant.name,
      power_kwp: plant.power_kwp,
      installation_date: plant.installation_date,
      module_model: plant.module_model || '',
      module_qty: plant.module_qty || undefined,
      inverter_model: plant.inverter_model || '',
      inverter_power_kw: plant.inverter_power_kw || undefined,
      latitude: plant.latitude || undefined,
      longitude: plant.longitude || undefined,
      address: plant.address || '',
      installer_company: plant.installer_company || '',
      status: plant.status,
    } : undefined,
  })

  const mutation = useMutation({
    mutationFn: (data: PlantForm) =>
      isEditing ? plantsApi.update(Number(id), data) : plantsApi.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['plants'] })
      toast({ title: isEditing ? 'Usina atualizada' : 'Usina criada' })
      navigate('/plants')
    },
    onError: () => toast({ title: 'Erro ao salvar', variant: 'destructive' }),
  })

  return (
    <div className="max-w-3xl mx-auto space-y-6">
      <div>
        <h1 className="text-3xl font-bold">{isEditing ? 'Editar Usina' : 'Nova Usina'}</h1>
        <p className="text-muted-foreground">Preencha os dados da usina fotovoltaica</p>
      </div>

      <Card>
        <CardContent className="pt-6">
          <form onSubmit={handleSubmit(d => mutation.mutate(d))} className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Cliente *</Label>
                <Select onValueChange={v => setValue('client_id', Number(v))} defaultValue={plant?.client_id?.toString()}>
                  <SelectTrigger><SelectValue placeholder="Selecione..." /></SelectTrigger>
                  <SelectContent>
                    {clientsData?.data.map(c => (
                      <SelectItem key={c.id} value={c.id.toString()}>{c.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.client_id && <p className="text-xs text-destructive">{errors.client_id.message}</p>}
              </div>
              <div className="space-y-2">
                <Label>Nome *</Label>
                <Input {...register('name')} />
                {errors.name && <p className="text-xs text-destructive">{errors.name.message}</p>}
              </div>
              <div className="space-y-2">
                <Label>Potência (kWp) *</Label>
                <Input type="number" step="0.01" {...register('power_kwp')} />
                {errors.power_kwp && <p className="text-xs text-destructive">{errors.power_kwp.message}</p>}
              </div>
              <div className="space-y-2">
                <Label>Data de Instalação *</Label>
                <Input type="date" {...register('installation_date')} />
              </div>
              <div className="space-y-2">
                <Label>Modelo dos Módulos</Label>
                <Input {...register('module_model')} />
              </div>
              <div className="space-y-2">
                <Label>Quantidade de Módulos</Label>
                <Input type="number" {...register('module_qty')} />
              </div>
              <div className="space-y-2">
                <Label>Modelo do Inversor</Label>
                <Input {...register('inverter_model')} />
              </div>
              <div className="space-y-2">
                <Label>Potência do Inversor (kW)</Label>
                <Input type="number" step="0.01" {...register('inverter_power_kw')} />
              </div>
              <div className="space-y-2">
                <Label>Latitude</Label>
                <Input type="number" step="0.0000001" {...register('latitude')} />
              </div>
              <div className="space-y-2">
                <Label>Longitude</Label>
                <Input type="number" step="0.0000001" {...register('longitude')} />
              </div>
              <div className="md:col-span-2 space-y-2">
                <Label>Endereço</Label>
                <Input {...register('address')} />
              </div>
              <div className="space-y-2">
                <Label>Empresa Instaladora</Label>
                <Input {...register('installer_company')} />
              </div>
              <div className="space-y-2">
                <Label>Status</Label>
                <Select defaultValue="active" onValueChange={v => setValue('status', v)}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="active">Ativa</SelectItem>
                    <SelectItem value="maintenance">Manutenção</SelectItem>
                    <SelectItem value="inactive">Inativa</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div className="flex gap-3 pt-4">
              <Button type="submit" disabled={mutation.isPending}>
                {mutation.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                {isEditing ? 'Atualizar' : 'Criar Usina'}
              </Button>
              <Button type="button" variant="outline" onClick={() => navigate('/plants')}>Cancelar</Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}
