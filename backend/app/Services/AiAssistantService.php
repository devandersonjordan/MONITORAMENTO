<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InverterReading;
use App\Models\Invoice;
use App\Models\Plant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAssistantService
{
    private string $apiKey;
    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.claude.api_key', '');
        $this->model = config('services.claude.model', 'claude-sonnet-4-20250514');
    }

    public function chat(User $user, string $message, array $conversationHistory = []): string
    {
        $context = $this->buildUserContext($user);

        $systemPrompt = <<<PROMPT
        Você é um assistente especializado em energia solar fotovoltaica, integrado ao sistema SolarSaaS.
        Você ajuda clientes e operadores a entender seus dados de geração, consumo, faturas e performance das usinas.

        Contexto do usuário:
        {$context}

        Regras:
        - Responda sempre em português brasileiro
        - Seja objetivo e técnico quando necessário, mas acessível
        - Use dados reais do sistema quando disponíveis
        - Para análises, considere a irradiação média de Alagoas (~5.2 kWh/m²/dia)
        - Sugira ações práticas quando identificar problemas
        - Não invente dados — se não tiver informação, diga claramente
        PROMPT;

        $messages = [];
        foreach ($conversationHistory as $msg) {
            $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
        }
        $messages[] = ['role' => 'user', 'content' => $message];

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => 2048,
                'system' => $systemPrompt,
                'messages' => $messages,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return $data['content'][0]['text'] ?? 'Desculpe, não consegui processar sua pergunta.';
            }

            Log::error('Claude API error', ['status' => $response->status(), 'body' => $response->body()]);
            return 'Desculpe, houve um erro ao processar sua pergunta. Tente novamente.';
        } catch (\Throwable $e) {
            Log::error("AI Assistant error: {$e->getMessage()}");
            return 'Serviço de IA temporariamente indisponível. Tente novamente em alguns minutos.';
        }
    }

    public function analyzePlantPerformance(Plant $plant): string
    {
        $plant->load('inverters');
        $inverterIds = $plant->inverters->pluck('id');

        $last30Days = InverterReading::whereIn('inverter_id', $inverterIds)
            ->where('recorded_at', '>=', Carbon::now()->subDays(30))
            ->selectRaw("DATE(recorded_at) as date, SUM(daily_kwh) as daily_total, AVG(efficiency_pct) as avg_efficiency, MAX(temperature_c) as max_temp")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totalProduction = $last30Days->sum('daily_total');
        $avgEfficiency = $last30Days->avg('avg_efficiency');
        $maxTemp = $last30Days->max('max_temp');
        $expectedMonthly = $plant->power_kwp * 5.2 * 30;
        $pr = $expectedMonthly > 0 ? ($totalProduction / $expectedMonthly) * 100 : 0;

        $prompt = <<<PROMPT
        Analise a performance desta usina solar nos últimos 30 dias:

        Usina: {$plant->name}
        Potência instalada: {$plant->power_kwp} kWp
        Inversores: {$plant->inverters->count()}
        Produção total (30 dias): {$totalProduction} kWh
        Produção esperada: {$expectedMonthly} kWh
        Performance Ratio: {$pr}%
        Eficiência média: {$avgEfficiency}%
        Temperatura máxima: {$maxTemp}°C

        Dados diários (últimos 30 dias):
        {$last30Days->map(fn($r) => "{$r->date}: {$r->daily_total} kWh, efic. {$r->avg_efficiency}%, temp máx {$r->max_temp}°C")->join("\n")}

        Forneça:
        1. Avaliação geral da performance
        2. Pontos de atenção identificados
        3. Recomendações de ação
        4. Previsão para o próximo mês baseada na tendência
        PROMPT;

        return $this->sendToAi($prompt);
    }

    public function analyzeInvoice(Invoice $invoice): string
    {
        $invoice->load('client');

        $prompt = <<<PROMPT
        Analise esta fatura de energia do cliente {$invoice->client?->name}:

        Competência: {$invoice->competence?->format('m/Y')}
        Consumo: {$invoice->consumption_kwh} kWh
        Energia Injetada: {$invoice->injected_kwh} kWh
        Energia Compensada: {$invoice->compensated_kwh} kWh
        Saldo anterior: {$invoice->previous_balance_kwh} kWh
        Saldo atual: {$invoice->current_balance_kwh} kWh
        Valor da fatura: R$ {$invoice->amount}
        Tarifa: R$ {$invoice->tariff}/kWh
        Bandeira: {$invoice->flag}
        ICMS: R$ {$invoice->icms_value}
        PIS: R$ {$invoice->pis_value}
        COFINS: R$ {$invoice->cofins_value}
        Iluminação pública: R$ {$invoice->public_lighting_value}

        Forneça:
        1. Resumo da fatura em linguagem simples
        2. Se a compensação solar está sendo bem aproveitada
        3. Se há oportunidades de economia
        4. Alertas sobre valores incomuns
        PROMPT;

        return $this->sendToAi($prompt);
    }

    private function buildUserContext(User $user): string
    {
        $plants = Plant::where('client_id', $user->id)->get();
        $totalCapacity = $plants->sum('power_kwp');
        $plantCount = $plants->count();

        $lastInvoice = Invoice::where('client_id', $user->id)
            ->orderByDesc('competence')
            ->first();

        $context = "Nome: {$user->name}\n";
        $context .= "Papel: {$user->role}\n";
        $context .= "Usinas: {$plantCount} ({$totalCapacity} kWp total)\n";

        if ($lastInvoice) {
            $context .= "Última fatura: {$lastInvoice->competence?->format('m/Y')} - R$ {$lastInvoice->amount}\n";
            $context .= "Último consumo: {$lastInvoice->consumption_kwh} kWh\n";
        }

        return $context;
    }

    private function sendToAi(string $prompt): string
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => 4096,
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);

            if ($response->successful()) {
                return $response->json('content.0.text') ?? 'Erro ao processar análise.';
            }

            return 'Erro na comunicação com o serviço de IA.';
        } catch (\Throwable $e) {
            Log::error("AI analysis error: {$e->getMessage()}");
            return 'Serviço de IA temporariamente indisponível.';
        }
    }
}
