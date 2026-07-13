import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { zodResolver } from '@hookform/resolvers/zod'
import { Loader2 } from 'lucide-react'
import { clientsApi } from '@/services/api/clients'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'
import { toast } from '@/components/ui/toaster'

const clientSchema = z.object({
  name: z.string().min(1, 'Nome obrigatório'),
  email: z.string().email('Email inválido'),
  password: z.string().min(8, 'Mínimo 8 caracteres').optional().or(z.literal('')),
  phone: z.string().optional(),
  whatsapp: z.string().optional(),
  cpf_cnpj: z.string().optional(),
  address: z.string().optional(),
  city: z.string().optional(),
  state: z.string().max(2).optional(),
  zip: z.string().optional(),
  distributor: z.string().optional(),
  uc_number: z.string().optional(),
  meter_number: z.string().optional(),
  equatorial_login: z.string().optional(),
  equatorial_password: z.string().optional(),
})

type ClientForm = z.infer<typeof clientSchema>

export default function ClientFormPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const isEditing = !!id

  const { data: client } = useQuery({
    queryKey: ['client', id],
    queryFn: () => clientsApi.get(Number(id)).then(r => r.data.data),
    enabled: isEditing,
  })

  const { register, handleSubmit, formState: { errors } } = useForm<ClientForm>({
    resolver: zodResolver(clientSchema),
    values: client ? {
      name: client.name,
      email: client.email,
      password: '',
      phone: client.phone || '',
      whatsapp: client.whatsapp || '',
      cpf_cnpj: client.cpf_cnpj || '',
      address: client.address || '',
      city: client.city || '',
      state: client.state || '',
      zip: client.zip || '',
      distributor: client.distributor || '',
      uc_number: client.uc_number || '',
      meter_number: client.meter_number || '',
      equatorial_login: '',
      equatorial_password: '',
    } : undefined,
  })

  const mutation = useMutation({
    mutationFn: (data: ClientForm) => {
      const payload = { ...data }
      if (isEditing && !payload.password) delete payload.password
      return isEditing ? clientsApi.update(Number(id), payload) : clientsApi.create(payload)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['clients'] })
      toast({ title: isEditing ? 'Cliente atualizado' : 'Cliente criado' })
      navigate('/clients')
    },
    onError: () => {
      toast({ title: 'Erro ao salvar', variant: 'destructive' })
    },
  })

  return (
    <div className="max-w-3xl mx-auto space-y-6">
      <div>
        <h1 className="text-3xl font-bold">{isEditing ? 'Editar Cliente' : 'Novo Cliente'}</h1>
        <p className="text-muted-foreground">Preencha os dados do cliente</p>
      </div>

      <Card>
        <CardContent className="pt-6">
          <form onSubmit={handleSubmit(d => mutation.mutate(d))} className="space-y-6">
            <div>
              <h3 className="text-lg font-semibold mb-3">Dados Pessoais</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Nome *</Label>
                  <Input {...register('name')} />
                  {errors.name && <p className="text-xs text-destructive">{errors.name.message}</p>}
                </div>
                <div className="space-y-2">
                  <Label>CPF/CNPJ</Label>
                  <Input {...register('cpf_cnpj')} />
                </div>
                <div className="space-y-2">
                  <Label>Email *</Label>
                  <Input type="email" {...register('email')} />
                  {errors.email && <p className="text-xs text-destructive">{errors.email.message}</p>}
                </div>
                <div className="space-y-2">
                  <Label>{isEditing ? 'Nova Senha (deixe vazio para manter)' : 'Senha *'}</Label>
                  <Input type="password" {...register('password')} />
                  {errors.password && <p className="text-xs text-destructive">{errors.password.message}</p>}
                </div>
                <div className="space-y-2">
                  <Label>Telefone</Label>
                  <Input {...register('phone')} />
                </div>
                <div className="space-y-2">
                  <Label>WhatsApp</Label>
                  <Input {...register('whatsapp')} />
                </div>
              </div>
            </div>

            <Separator />

            <div>
              <h3 className="text-lg font-semibold mb-3">Endereço</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="md:col-span-2 space-y-2">
                  <Label>Endereço</Label>
                  <Input {...register('address')} />
                </div>
                <div className="space-y-2">
                  <Label>Cidade</Label>
                  <Input {...register('city')} />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div className="space-y-2">
                    <Label>Estado</Label>
                    <Input {...register('state')} maxLength={2} placeholder="AL" />
                  </div>
                  <div className="space-y-2">
                    <Label>CEP</Label>
                    <Input {...register('zip')} />
                  </div>
                </div>
              </div>
            </div>

            <Separator />

            <div>
              <h3 className="text-lg font-semibold mb-3">Dados da Concessionária</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Distribuidora</Label>
                  <Input {...register('distributor')} placeholder="Equatorial Alagoas" />
                </div>
                <div className="space-y-2">
                  <Label>Número da UC</Label>
                  <Input {...register('uc_number')} />
                </div>
                <div className="space-y-2">
                  <Label>Número do Medidor</Label>
                  <Input {...register('meter_number')} />
                </div>
              </div>
            </div>

            <Separator />

            <div>
              <h3 className="text-lg font-semibold mb-3">Credenciais Equatorial</h3>
              <p className="text-sm text-muted-foreground mb-3">
                Credenciais para download automático de faturas (armazenadas com criptografia)
              </p>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div className="space-y-2">
                  <Label>Login Equatorial</Label>
                  <Input {...register('equatorial_login')} />
                </div>
                <div className="space-y-2">
                  <Label>Senha Equatorial</Label>
                  <Input type="password" {...register('equatorial_password')} />
                </div>
              </div>
            </div>

            <div className="flex gap-3 pt-4">
              <Button type="submit" disabled={mutation.isPending}>
                {mutation.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                {isEditing ? 'Atualizar' : 'Criar Cliente'}
              </Button>
              <Button type="button" variant="outline" onClick={() => navigate('/clients')}>
                Cancelar
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}
