<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\InverterAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private InverterAlert $alert
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];

        if ($notifiable->whatsapp && $this->alert->severity === 'critical') {
            $channels[] = 'whatsapp';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $severityLabels = [
            'critical' => 'CRÍTICO',
            'warning' => 'Aviso',
            'info' => 'Informativo',
        ];

        $severityLabel = $severityLabels[$this->alert->severity] ?? $this->alert->severity;

        return (new MailMessage)
            ->subject("[{$severityLabel}] Alerta Solar — {$this->alert->type}")
            ->greeting("Olá, {$notifiable->name}!")
            ->line("Um alerta foi detectado no seu sistema solar:")
            ->line("**Tipo:** {$this->alert->type}")
            ->line("**Severidade:** {$severityLabel}")
            ->line("**Mensagem:** {$this->alert->message}")
            ->line("**Inversor:** {$this->alert->inverter?->serial_number}")
            ->action('Ver no Painel', url('/dashboard'))
            ->line('Verifique o mais breve possível para evitar perdas de geração.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'alert_id' => $this->alert->id,
            'type' => $this->alert->type,
            'severity' => $this->alert->severity,
            'message' => $this->alert->message,
            'inverter_id' => $this->alert->inverter_id,
        ];
    }

    public function toWhatsapp(object $notifiable): array
    {
        $severityEmoji = match ($this->alert->severity) {
            'critical' => '🔴',
            'warning' => '🟡',
            default => '🔵',
        };

        return [
            'to' => $notifiable->whatsapp,
            'message' => "{$severityEmoji} *Alerta Solar*\n\n"
                . "Tipo: {$this->alert->type}\n"
                . "Mensagem: {$this->alert->message}\n"
                . "Inversor: {$this->alert->inverter?->serial_number}\n\n"
                . "Acesse o painel para mais detalhes.",
        ];
    }
}
