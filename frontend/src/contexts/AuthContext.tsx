import { createContext, useContext, useState, useEffect, useCallback, type ReactNode } from 'react'
import api from '@/lib/api'
import type { User, AuthResponse } from '@/types'

interface AuthContextType {
  user: User | null
  token: string | null
  isAuthenticated: boolean
  isLoading: boolean
  login: (email: string, password: string) => Promise<void>
  logout: () => Promise<void>
}

const AuthContext = createContext<AuthContextType | undefined>(undefined)

export function AuthProvider({ children }: { children: ReactNode }) {
  const [user, setUser] = useState<User | null>(() => {
    const stored = localStorage.getItem('user')
    return stored ? JSON.parse(stored) : null
  })
  const [token, setToken] = useState<string | null>(() => localStorage.getItem('token'))
  const [isLoading, setIsLoading] = useState(false)

  const isAuthenticated = !!token && !!user

  useEffect(() => {
    if (token && !user) {
      api.get('/auth/me').then(res => {
        setUser(res.data.user)
        localStorage.setItem('user', JSON.stringify(res.data.user))
      }).catch(() => {
        setToken(null)
        localStorage.removeItem('token')
        localStorage.removeItem('user')
      })
    }
  }, [token, user])

  const login = useCallback(async (email: string, password: string) => {
    setIsLoading(true)
    try {
      const res = await api.post<AuthResponse>('/auth/login', { email, password })
      const { user: userData, token: newToken } = res.data
      setUser(userData)
      setToken(newToken)
      localStorage.setItem('token', newToken)
      localStorage.setItem('user', JSON.stringify(userData))
    } finally {
      setIsLoading(false)
    }
  }, [])

  const logout = useCallback(async () => {
    try {
      await api.post('/auth/logout')
    } catch {
      // ignore
    } finally {
      setUser(null)
      setToken(null)
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    }
  }, [])

  return (
    <AuthContext.Provider value={{ user, token, isAuthenticated, isLoading, login, logout }}>
      {children}
    </AuthContext.Provider>
  )
}

export function useAuth() {
  const context = useContext(AuthContext)
  if (!context) throw new Error('useAuth must be used within AuthProvider')
  return context
}
