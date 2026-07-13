<?php

declare(strict_types=1);

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="SolarSaaS API",
 *     version="1.0.0",
 *     description="API para monitoramento de usinas solares fotovoltaicas",
 *     @OA\Contact(email="suporte@solarsaas.com")
 * )
 * @OA\Server(url="/api", description="API Server")
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Sanctum Token"
 * )
 * @OA\Tag(name="Auth", description="Autenticação")
 * @OA\Tag(name="Companies", description="Gerenciamento de empresas")
 * @OA\Tag(name="Clients", description="Gerenciamento de clientes")
 * @OA\Tag(name="Plants", description="Gerenciamento de usinas")
 * @OA\Tag(name="Inverters", description="Gerenciamento de inversores")
 * @OA\Tag(name="Invoices", description="Faturas de energia")
 * @OA\Tag(name="Reports", description="Relatórios inteligentes")
 * @OA\Tag(name="Charts", description="Dados para gráficos")
 * @OA\Tag(name="AI", description="Assistente de IA")
 * @OA\Tag(name="Alerts", description="Alertas e anomalias")
 * @OA\Tag(name="Notifications", description="Notificações do usuário")
 * @OA\Tag(name="Dashboard", description="Estatísticas do dashboard")
 */
abstract class Controller
{
    //
}
