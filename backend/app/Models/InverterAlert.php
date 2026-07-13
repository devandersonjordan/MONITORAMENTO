<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InverterAlert extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'inverter_id',
        'company_id',
        'type',
        'severity',
        'message',
        'data',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function inverter(): BelongsTo
    {
        return $this->belongsTo(Inverter::class);
    }

    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }
}
