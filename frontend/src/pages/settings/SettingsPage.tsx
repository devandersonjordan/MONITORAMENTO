import { useState } from 'react'
import { useForm } from 'react-hook-form'
import { z } from 'zod'
import { zodResolver } from '@hookform/resolvers/zod'
import { User, Lock, Bell, Building2 } from 'lucide-react'
import { useAuth } from '@/contexts/AuthContext'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Separator } from '@/components/ui/separator'
import { useToast } from '@/components/ui/toaster'
import api from '@/lib/api'

const profileSchema = z.object({
  name: z.string().min(2, 'Nome obrigatório'),
  email: z.string().email('Email inválido'),
  phone: z.string().optional(),
})

const passwordSchema = z.object({
  current_password: z.string().min(1, 'Senha atual obrigatória'),
  password: z.string().min(8, 'Mínimo 8 caracteres'),
  password_confirmation: z.string(),
}).refine(d => d.password === d.password_confirmation, {
  message: 'As senhas não conferem',
  path: ['password_confirmation'],
})

type ProfileForm = z.infer<typeof profileSchema>
type PasswordForm = z.infer<typeof passwordSchema>

export default function SettingsPage() {
  const { user } = useAuth()
  const { addToast } = useToast()
  const [savingProfile, setSavingProfile] = useState(false)
  const [savingPassword, setSavingPassword] = useState(false)

  const profileForm = useForm<ProfileForm>({
    resolver: zodResolver(profileSchema),
    defaultValues: {
      name: user?.name ?? '',
      email: user?.email ?? '',
      phone: user?.phone ?? '',
    },
  })

  const passwordForm = useForm<PasswordForm>({
    resolver: zodResolver(passwordSchema),
  })

  const onSaveProfile = async (data: ProfileForm) => {
    setSavingProfile(true)
    try {
      await api.put('/auth/profile', data)
      addToast({ title: 'Perfil atualizado com sucesso', variant: 'success' })
    } catch {
      addToast({ title: 'Erro ao atualizar perfil', variant: 'destructive' })
    } finally {
      setSavingProfile(false)
    }
  }

  const onSavePassword = async (data: PasswordForm) => {
    setSavingPassword(true)
    try {
      await api.put('/auth/password', data)
      addToast({ title: 'Senha alterada com sucesso', variant: 'success' })
      passwordForm.reset()
    } catch {
      addToast({ title: 'Erro ao alterar senha. Verifique a senha atual.', variant: 'destructive' })
    } finally {
      setSavingPassword(false)
    }
  }

  return (
    <div className="space-y-6 max-w-2xl">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Configurações</h1>
        <p className="text-muted-foreground">Gerencie sua conta e preferências</p>
      </div>

      {/* Profile */}
      <Card>
        <CardHeader>
          <div className="flex items-center gap-2">
            <User className="h-5 w-5 text-muted-foreground" />
            <CardTitle>Perfil</CardTitle>
          </div>
          <CardDescription>Atualize seus dados pessoais</CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={profileForm.handleSubmit(onSaveProfile)} className="space-y-4">
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label>Nome</Label>
                <Input {...profileForm.register('name')} />
                {profileForm.formState.errors.name && (
                  <p className="text-xs text-destructive">{profileForm.formState.errors.name.message}</p>
                )}
              </div>
              <div className="space-y-2">
                <Label>Email</Label>
                <Input type="email" {...profileForm.register('email')} />
                {profileForm.formState.errors.email && (
                  <p className="text-xs text-destructive">{profileForm.formState.errors.email.message}</p>
                )}
              </div>
              <div className="space-y-2">
                <Label>Telefone</Label>
                <Input {...profileForm.register('phone')} placeholder="(00) 00000-0000" />
              </div>
              <div className="space-y-2">
                <Label>Perfil</Label>
                <Input value={user?.role === 'admin' ? 'Administrador' : user?.role === 'employee' ? 'Funcionário' : 'Cliente'} disabled className="capitalize" />
              </div>
            </div>
            <div className="flex justify-end">
              <Button type="submit" disabled={savingProfile}>
                {savingProfile ? 'Salvando...' : 'Salvar Perfil'}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>

      {/* Company info (read-only) */}
      {user?.company && (
        <Card>
          <CardHeader>
            <div className="flex items-center gap-2">
              <Building2 className="h-5 w-5 text-muted-foreground" />
              <CardTitle>Empresa</CardTitle>
            </div>
            <CardDescription>Informações da empresa associada à sua conta</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 sm:grid-cols-2 text-sm">
              <div>
                <span className="text-muted-foreground">Nome</span>
                <p className="font-medium mt-1">{user.company.name}</p>
              </div>
              <div>
                <span className="text-muted-foreground">CNPJ</span>
                <p className="font-medium mt-1">{user.company.cnpj}</p>
              </div>
              <div>
                <span className="text-muted-foreground">Plano</span>
                <p className="font-medium mt-1 capitalize">{user.company.plan}</p>
              </div>
              <div>
                <span className="text-muted-foreground">Status</span>
                <p className="font-medium mt-1 capitalize">{user.company.status === 'active' ? 'Ativo' : 'Inativo'}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      <Separator />

      {/* Change password */}
      <Card>
        <CardHeader>
          <div className="flex items-center gap-2">
            <Lock className="h-5 w-5 text-muted-foreground" />
            <CardTitle>Alterar Senha</CardTitle>
          </div>
          <CardDescription>Escolha uma senha segura com pelo menos 8 caracteres</CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={passwordForm.handleSubmit(onSavePassword)} className="space-y-4">
            <div className="space-y-2">
              <Label>Senha Atual</Label>
              <Input type="password" {...passwordForm.register('current_password')} />
              {passwordForm.formState.errors.current_password && (
                <p className="text-xs text-destructive">{passwordForm.formState.errors.current_password.message}</p>
              )}
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
              <div className="space-y-2">
                <Label>Nova Senha</Label>
                <Input type="password" {...passwordForm.register('password')} />
                {passwordForm.formState.errors.password && (
                  <p className="text-xs text-destructive">{passwordForm.formState.errors.password.message}</p>
                )}
              </div>
              <div className="space-y-2">
                <Label>Confirmar Nova Senha</Label>
                <Input type="password" {...passwordForm.register('password_confirmation')} />
                {passwordForm.formState.errors.password_confirmation && (
                  <p className="text-xs text-destructive">{passwordForm.formState.errors.password_confirmation.message}</p>
                )}
              </div>
            </div>
            <div className="flex justify-end">
              <Button type="submit" variant="outline" disabled={savingPassword}>
                {savingPassword ? 'Alterando...' : 'Alterar Senha'}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>

      {/* Notifications info */}
      <Card>
        <CardHeader>
          <div className="flex items-center gap-2">
            <Bell className="h-5 w-5 text-muted-foreground" />
            <CardTitle>Notificações</CardTitle>
          </div>
          <CardDescription>Alertas de inversores e faturas são enviados automaticamente por email</CardDescription>
        </CardHeader>
        <CardContent>
          <p className="text-sm text-muted-foreground">
            O sistema envia notificações automáticas para <span className="font-medium text-foreground">{user?.email}</span> quando:
          </p>
          <ul className="mt-3 space-y-1 text-sm text-muted-foreground list-disc list-inside">
            <li>Um inversor fica sem comunicação por mais de 1 hora</li>
            <li>A produção cai abaixo do esperado</li>
            <li>Novas faturas são geradas</li>
            <li>Faturas estão próximas do vencimento</li>
          </ul>
        </CardContent>
      </Card>
    </div>
  )
}
