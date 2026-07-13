<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inverter extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'plant_id',
        'company_id',
        'brand',
        'model',
        'serial_number',
        'api_credentials',
        'status',
        'last_communication_at',
    ];

    protected function casts(): array
    {
        return [
            'api_credentials' => 'encrypted:array',
            'last_communication_at' => 'datetime',
        ];
    }

    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    public function readings(): HasMany
    {
        return $this->hasMany(InverterReading::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(InverterAlert::class);
    }

    public function scopeOnline(Builder $query): Builder
    {
        return $query->where('status', 'online');
    }

    public function scopeOffline(Builder $query): Builder
    {
        return $query->where('status', 'offline');
    }
}
