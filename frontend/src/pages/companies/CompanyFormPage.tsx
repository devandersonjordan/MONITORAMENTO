import { useNavigate, useParams } from 'react-router-dom'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { zodResolver } from '@hookform/resolvers/zod'
import { Loader2 } from 'lucide-react'
import { companiesApi } from '@/services/api/companies'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { toast } from '@/components/ui/toaster'

const companySchema = z.object({
  name: z.string().min(1, 'Nome obrigatório'),
  cnpj: z.string().min(14, 'CNPJ inválido'),
  email: z.string().email('Email inválido'),
  phone: z.string().optional(),
  plan: z.string().default('basic'),
  max_clients: z.coerce.number().min(1).default(50),
  max_plants: z.coerce.number().min(1).default(100),
  status: z.string().default('active'),
})

type CompanyForm = z.infer<typeof companySchema>

export default function CompanyFormPage() {
  const { id } = useParams()
  const navigate = useNavigate()
  const queryClient = useQueryClient()
  const isEditing = !!id

  const { data: company } = useQuery({
    queryKey: ['company', id],
    queryFn: () => companiesApi.get(Number(id)).then(r => r.data.data),
    enabled: isEditing,
  })

  const { register, handleSubmit, setValue, formState: { errors } } = useForm<CompanyForm>({
    resolver: zodResolver(companySchema),
    values: company ? {
      name: company.name,
      cnpj: company.cnpj,
      email: company.email,
      phone: company.phone || '',
      plan: company.plan,
      max_clients: company.max_clients,
      max_plants: company.max_plants,
      status: company.status,
    } : undefined,
  })

  const mutation = useMutation({
    mutationFn: (data: CompanyForm) =>
      isEditing ? companiesApi.update(Number(id), data) : companiesApi.create(data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['companies'] })
      toast({ title: isEditing ? 'Empresa atualizada' : 'Empresa criada' })
      navigate('/companies')
    },
    onError: () => {
      toast({ title: 'Erro ao salvar', variant: 'destructive' })
    },
  })

  return (
    <div className="max-w-2xl mx-auto space-y-6">
      <div>
        <h1 className="text-3xl font-bold">{isEditing ? 'Editar Empresa' : 'Nova Empresa'}</h1>
        <p className="text-muted-foreground">Preencha os dados da empresa</p>
      </div>

      <Card>
        <CardContent className="pt-6">
          <form onSubmit={handleSubmit(d => mutation.mutate(d))} className="space-y-4">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="space-y-2">
                <Label htmlFor="name">Nome *</Label>
                <Input id="name" {...register('name')} />
                {errors.name && <p className="text-xs text-destructive">{errors.name.message}</p>}
              </div>
              <div className="space-y-2">
                <Label htmlFor="cnpj">CNPJ *</Label>
                <Input id="cnpj" {...register('cnpj')} placeholder="00.000.000/0000-00" />
                {errors.cnpj && <p className="text-xs text-destructive">{errors.cnpj.message}</p>}
              </div>
              <div className="space-y-2">
                <Label htmlFor="email">Email *</Label>
                <Input id="email" type="email" {...register('email')} />
                {errors.email && <p className="text-xs text-destructive">{errors.email.message}</p>}
              </div>
              <div className="space-y-2">
                <Label htmlFor="phone">Telefone</Label>
                <Input id="phone" {...register('phone')} />
              </div>
              <div className="space-y-2">
                <Label>Plano</Label>
                <Select defaultValue="basic" onValueChange={v => setValue('plan', v)}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="basic">Básico</SelectItem>
                    <SelectItem value="professional">Profissional</SelectItem>
                    <SelectItem value="enterprise">Enterprise</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label>Status</Label>
                <Select defaultValue="active" onValueChange={v => setValue('status', v)}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="active">Ativo</SelectItem>
                    <SelectItem value="inactive">Inativo</SelectItem>
                    <SelectItem value="suspended">Suspenso</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              <div className="space-y-2">
                <Label htmlFor="max_clients">Máx. Clientes</Label>
                <Input id="max_clients" type="number" {...register('max_clients')} />
              </div>
              <div className="space-y-2">
                <Label htmlFor="max_plants">Máx. Usinas</Label>
                <Input id="max_plants" type="number" {...register('max_plants')} />
              </div>
            </div>

            <div className="flex gap-3 pt-4">
              <Button type="submit" disabled={mutation.isPending}>
                {mutation.isPending && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                {isEditing ? 'Atualizar' : 'Criar Empresa'}
              </Button>
              <Button type="button" variant="outline" onClick={() => navigate('/companies')}>
                Cancelar
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}
