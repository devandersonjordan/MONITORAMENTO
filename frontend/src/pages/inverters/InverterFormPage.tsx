import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { zodResolver } from '@hookform/resolvers/zod'
import { Loader2 } from 'lucide-react'
import { invertersApi } from '@/services/api/inverters'
import { plantsApi } from '@/services/api/plants'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { toast } from '@/components/ui/toaster'

const inverterSchema = z.object({
  plant_id: z.coerce.number().min(1, 'Selecione uma usina'),
  brand: z.string().min(1, 'Selecione a marca'),
  model: z.string().optional(),
  serial_number: z.string().optional(),
  status: z.string().default('online'),
})

type InverterForm = z.infer<typeof inverterSchema>

export default function InverterFormPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const isEditing = !!id

  const { data: inverter } = useQuery({
    queryKey: ['inverter', id],
    queryFn: () => invertersApi.get(Number(id)).then(r => r.data.data),
    enabled: isEditing,
  })

  const { data: plantsData } = useQuery({
    queryKey: ['plants-select'],
    queryFn: () => plantsApi.list({ per_page: 1000 }).then(r => r.data),
  })

  const { register, handleSubmit, setValue, formState: { errors } } = useForm<InverterForm>({
    resolver: zodResolver(inverterSchema),
    values: inverter ? {
      plant_id: inverter.plant_id,
      brand: inverter.brand,
      model: inverter.model || '',
      serial_number: inverter.serial_number || '',
      status: inverter.status,
    } : undefined,
  })

  const mutation = useMutation({
    mutationFn: (data: InverterForm) =>
      isEditing ? invertersApi.update(Number(id), data) : invertersApi.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['inverters'] })
      toast({ title: isEditing ? 'Inversor atualizado' : 'Inversor criado' })
      navigate('/inverters')
    },
    onError: () => toast({ title: 'Erro ao salvar', variant: 'destructive' }),
  })

  return (
    <div className="max-w-2xl mx-auto space-y-6">
      <div>
        <h1 className="text-3xl font-bold">{isEditing ? 'Editar Inversor' : 'Novo Inversor'}</h1>
        <p className="text-muted-foreground">Preencha os dados do inversor</p>
      </div>

      <Card>
        <CardContent className="pt-6">
          <form onSubmit={handleSubmit(d => mutation.mutate(d))} className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label>Usina *</Label>
                <Select onValueChange={v => setValue('plant_id', Number(v))} defaultValue={inverter?.plant_id?.toString()}>
                  <SelectTrigger><SelectValue placeholder="Selecione..." /></SelectTrigger>
                  <SelectContent>
                    {plantsData?.data.map(p => (
                      <SelectItem key={p.id} value={p.id.toString()}>{p.name}</SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                {errors.plant_id && <p className="text-xs text-destructive">{errors.plant_id.message}</p>}
              </div>
              <div className="space-y-2">
                <Label>Marca *</Label>
                <Select onValueChange={v => setValue('brand', v)} defaultValue={inverter?.brand}>
                  <SelectTrigger><SelectValue placeholder="Selecione..." /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="elekeeper">Elekeeper</SelectItem>
                    <SelectItem value="goodwe">GoodWe</SelectItem>
                    <SelectItem value="sungrow">Sungrow</SelectItem>
                    <SelectItem value="deye">Deye</SelectItem>
                  </SelectContent>
                </Select>
                {errors.brand && <p className="text-xs text-destructive">{errors.brand.message}</p>}
              </div>
              <div className="space-y-2">
                <Label>Modelo</Label>
                <Input {...register('model')} />
              </div>
              <div className="space-y-2">
                <Label>Número de Série</Label>
                <Input {...register('serial_number')} />
              </div>
              <div className="space-y-2">
                <Label>Status</Label>
                <Select defaultValue="online" onValueChange={v => setValue('status', v)}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="online">Online</SelectItem>
                    <SelectItem value="offline">Offline</SelectItem>
                    <SelectItem value="warning">Alerta</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            </div>

            <div className="flex gap-3 pt-4">
              <Button type="submit" disabled={mutation.isPending}>
                {mutation.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                {isEditing ? 'Atualizar' : 'Criar Inversor'}
              </Button>
              <Button type="button" variant="outline" onClick={() => navigate('/inverters')}>Cancelar</Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}
