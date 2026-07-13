<?php

declare(strict_types=1);

namespace App\Integrations\Inverters;

class InverterDataDTO
{
    public function __construct(
        public readonly float $power_w = 0,
        public readonly float $voltage_v = 0,
        public readonly float $current_a = 0,
        public readonly float $frequency_hz = 0,
        public readonly float $temperature_c = 0,
        public readonly float $daily_kwh = 0,
        public readonly float $monthly_kwh = 0,
        public readonly float $yearly_kwh = 0,
        public readonly float $total_kwh = 0,
        public readonly float $efficiency_pct = 0,
        public readonly string $status = 'normal',
    ) {}

    public function toArray(): array
    {
        return [
            'power_w' => $this->power_w,
            'voltage_v' => $this->voltage_v,
            'current_a' => $this->current_a,
            'frequency_hz' => $this->frequency_hz,
            'temperature_c' => $this->temperature_c,
            'daily_kwh' => $this->daily_kwh,
            'monthly_kwh' => $this->monthly_kwh,
            'yearly_kwh' => $this->yearly_kwh,
            'total_kwh' => $this->total_kwh,
            'efficiency_pct' => $this->efficiency_pct,
            'status' => $this->status,
        ];
    }
}
