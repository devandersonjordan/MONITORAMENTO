<?php

declare(strict_types=1);

namespace App\Integrations\Inverters;

use App\Models\Inverter;

interface InverterAdapterInterface
{
    public function authenticate(array $credentials): bool;

    public function fetchRealtimeData(Inverter $inverter): ?InverterDataDTO;

    public function fetchDailyHistory(Inverter $inverter, string $date): array;

    public function getStatus(Inverter $inverter): string;

    public function getAlarms(Inverter $inverter): array;

    public static function brand(): string;
}
