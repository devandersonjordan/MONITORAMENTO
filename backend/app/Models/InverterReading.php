<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InverterReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'inverter_id',
        'recorded_at',
        'power_w',
        'voltage_v',
        'current_a',
        'frequency_hz',
        'temperature_c',
        'daily_kwh',
        'monthly_kwh',
        'yearly_kwh',
        'total_kwh',
        'efficiency_pct',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
            'power_w' => 'decimal:2',
            'voltage_v' => 'decimal:2',
            'current_a' => 'decimal:2',
            'frequency_hz' => 'decimal:2',
            'temperature_c' => 'decimal:1',
            'daily_kwh' => 'decimal:2',
            'monthly_kwh' => 'decimal:2',
            'yearly_kwh' => 'decimal:2',
            'total_kwh' => 'decimal:2',
            'efficiency_pct' => 'decimal:2',
        ];
    }

    public function inverter(): BelongsTo
    {
        return $this->belongsTo(Inverter::class);
    }
}
