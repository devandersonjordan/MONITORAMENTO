import { useState, useRef, useEffect } from 'react'
import { useMutation } from '@tanstack/react-query'
import { Send, Bot, User, Loader2, Sparkles } from 'lucide-react'
import { aiApi } from '@/services/api/ai'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'

interface Message {
  role: 'user' | 'assistant'
  content: string
}

const suggestions = [
  'Como está a produção das minhas usinas este mês?',
  'Qual a economia que tive com energia solar?',
  'Explique minha última fatura de energia',
  'Há algum inversor com problema?',
  'Quais dicas para melhorar a eficiência?',
]

export default function AiAssistantPage() {
  const [messages, setMessages] = useState<Message[]>([])
  const [input, setInput] = useState('')
  const messagesEndRef = useRef<HTMLDivElement>(null)

  const chatMutation = useMutation({
    mutationFn: (message: string) =>
      aiApi.chat({ message, history: messages }).then(r => r.data.data.response),
    onSuccess: (response) => {
      setMessages(prev => [...prev, { role: 'assistant', content: response }])
    },
    onError: () => {
      setMessages(prev => [
        ...prev,
        { role: 'assistant', content: 'Desculpe, ocorreu um erro. Tente novamente.' },
      ])
    },
  })

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' })
  }, [messages])

  const handleSend = (text?: string) => {
    const message = text ?? input.trim()
    if (!message || chatMutation.isPending) return
    setMessages(prev => [...prev, { role: 'user', content: message }])
    setInput('')
    chatMutation.mutate(message)
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Assistente IA</h1>
        <p className="text-muted-foreground">Converse com a IA sobre seus dados solares</p>
      </div>

      <Card className="flex flex-col h-[calc(100vh-220px)]">
        <CardHeader className="border-b pb-4">
          <div className="flex items-center gap-2">
            <div className="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center">
              <Sparkles className="h-4 w-4 text-primary" />
            </div>
            <div>
              <CardTitle className="text-base">SolarIA</CardTitle>
              <p className="text-xs text-muted-foreground">Powered by Claude</p>
            </div>
          </div>
        </CardHeader>

        <CardContent className="flex-1 overflow-y-auto p-4 space-y-4">
          {messages.length === 0 && (
            <div className="flex flex-col items-center justify-center h-full gap-6">
              <div className="h-16 w-16 rounded-full bg-primary/10 flex items-center justify-center">
                <Bot className="h-8 w-8 text-primary" />
              </div>
              <div className="text-center">
                <h3 className="font-semibold text-lg">Como posso ajudar?</h3>
                <p className="text-sm text-muted-foreground mt-1">
                  Pergunte sobre suas usinas, faturas, produção ou qualquer dúvida sobre energia solar.
                </p>
              </div>
              <div className="flex flex-wrap gap-2 max-w-lg justify-center">
                {suggestions.map((s, i) => (
                  <button
                    key={i}
                    onClick={() => handleSend(s)}
                    className="text-sm px-3 py-2 rounded-lg border border-border hover:bg-accent transition-colors text-left"
                  >
                    {s}
                  </button>
                ))}
              </div>
            </div>
          )}

          {messages.map((msg, i) => (
            <div key={i} className={`flex gap-3 ${msg.role === 'user' ? 'justify-end' : ''}`}>
              {msg.role === 'assistant' && (
                <div className="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                  <Bot className="h-4 w-4 text-primary" />
                </div>
              )}
              <div
                className={`max-w-[70%] rounded-lg px-4 py-3 text-sm whitespace-pre-wrap ${
                  msg.role === 'user'
                    ? 'bg-primary text-primary-foreground'
                    : 'bg-muted'
                }`}
              >
                {msg.content}
              </div>
              {msg.role === 'user' && (
                <div className="h-8 w-8 rounded-full bg-muted flex items-center justify-center shrink-0">
                  <User className="h-4 w-4" />
                </div>
              )}
            </div>
          ))}

          {chatMutation.isPending && (
            <div className="flex gap-3">
              <div className="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
                <Bot className="h-4 w-4 text-primary" />
              </div>
              <div className="bg-muted rounded-lg px-4 py-3">
                <Loader2 className="h-4 w-4 animate-spin" />
              </div>
            </div>
          )}

          <div ref={messagesEndRef} />
        </CardContent>

        <div className="border-t p-4">
          <form
            onSubmit={e => { e.preventDefault(); handleSend() }}
            className="flex gap-2"
          >
            <Input
              value={input}
              onChange={e => setInput(e.target.value)}
              placeholder="Digite sua pergunta..."
              disabled={chatMutation.isPending}
              className="flex-1"
            />
            <Button type="submit" disabled={!input.trim() || chatMutation.isPending}>
              <Send className="h-4 w-4" />
            </Button>
          </form>
        </div>
      </Card>
    </div>
  )
}
