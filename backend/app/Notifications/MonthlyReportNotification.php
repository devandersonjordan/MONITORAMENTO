<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MonthlyReportNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Report $report
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $data = $this->report->data;
        $period = $data['period']['month_label'] ?? 'N/A';
        $production = $data['production']['total_kwh'] ?? 0;
        $savings = $data['financial']['savings_brl'] ?? 0;

        return (new MailMessage)
            ->subject("Relatório Mensal Solar — {$period}")
            ->greeting("Olá, {$notifiable->name}!")
            ->line("Seu relatório mensal de energia solar está pronto.")
            ->line("**Período:** {$period}")
            ->line("**Produção:** {$production} kWh")
            ->line("**Economia:** R$ " . number_format($savings, 2, ',', '.'))
            ->action('Ver Relatório', url("/reports/{$this->report->id}"))
            ->line('Obrigado por usar energia solar!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'report_id' => $this->report->id,
            'period' => $this->report->data['period']['month_label'] ?? null,
            'type' => 'monthly_report',
        ];
    }
}
