import { Link, useLocation } from 'react-router-dom'
import {
  LayoutDashboard, Building2, Users, Zap, Radio,
  FileText, BarChart3, Settings, ChevronLeft, Sun as SunIcon, LogOut,
  Sparkles, AlertTriangle,
} from 'lucide-react'
import { cn } from '@/lib/utils'
import { useAuth } from '@/contexts/AuthContext'
import { Button } from '@/components/ui/button'
import { Separator } from '@/components/ui/separator'

interface SidebarProps {
  collapsed: boolean
  onToggle: () => void
}

const navItems = [
  { to: '/dashboard', icon: LayoutDashboard, label: 'Dashboard' },
  { to: '/companies', icon: Building2, label: 'Empresas', adminOnly: true },
  { to: '/clients', icon: Users, label: 'Clientes' },
  { to: '/plants', icon: Zap, label: 'Usinas' },
  { to: '/inverters', icon: Radio, label: 'Inversores' },
  { to: '/invoices', icon: FileText, label: 'Faturas' },
  { to: '/reports', icon: BarChart3, label: 'Relatórios' },
  { to: '/alerts', icon: AlertTriangle, label: 'Alertas' },
  { to: '/ai', icon: Sparkles, label: 'Assistente IA' },
  { to: '/settings', icon: Settings, label: 'Configurações', adminOnly: true },
]

export default function Sidebar({ collapsed, onToggle }: SidebarProps) {
  const location = useLocation()
  const { user, logout } = useAuth()

  return (
    <aside
      className={cn(
        'fixed left-0 top-0 z-40 h-screen bg-sidebar border-r border-sidebar-border transition-all duration-300 flex flex-col',
        collapsed ? 'w-16' : 'w-64'
      )}
    >
      <div className="flex items-center h-16 px-4 gap-3">
        <div className="flex items-center justify-center w-8 h-8 rounded-lg bg-primary text-primary-foreground">
          <SunIcon className="w-5 h-5" />
        </div>
        {!collapsed && (
          <span className="font-bold text-lg text-sidebar-foreground">SolarSaaS</span>
        )}
        <Button
          variant="ghost"
          size="icon"
          className={cn('ml-auto h-8 w-8', collapsed && 'mx-auto')}
          onClick={onToggle}
        >
          <ChevronLeft className={cn('h-4 w-4 transition-transform', collapsed && 'rotate-180')} />
        </Button>
      </div>

      <Separator />

      <nav className="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
        {navItems.map(item => {
          if (item.adminOnly && user?.role === 'client') return null
          const isActive = location.pathname.startsWith(item.to)
          return (
            <Link
              key={item.to}
              to={item.to}
              className={cn(
                'flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                isActive
                  ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                  : 'text-sidebar-foreground hover:bg-sidebar-accent/50',
                collapsed && 'justify-center px-2'
              )}
            >
              <item.icon className="w-5 h-5 shrink-0" />
              {!collapsed && <span>{item.label}</span>}
            </Link>
          )
        })}
      </nav>

      <Separator />

      <div className="p-3">
        {!collapsed && user && (
          <div className="mb-2 px-2">
            <p className="text-sm font-medium text-sidebar-foreground truncate">{user.name}</p>
            <p className="text-xs text-muted-foreground truncate">{user.email}</p>
          </div>
        )}
        <Button
          variant="ghost"
          size={collapsed ? 'icon' : 'default'}
          className={cn('w-full', !collapsed && 'justify-start')}
          onClick={logout}
        >
          <LogOut className="w-4 h-4" />
          {!collapsed && <span className="ml-2">Sair</span>}
        </Button>
      </div>
    </aside>
  )
}
