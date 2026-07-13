<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plant extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'client_id',
        'name',
        'power_kwp',
        'installation_date',
        'module_model',
        'module_qty',
        'inverter_model',
        'inverter_power_kw',
        'latitude',
        'longitude',
        'address',
        'installer_company',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'power_kwp' => 'decimal:2',
            'inverter_power_kw' => 'decimal:2',
            'installation_date' => 'date',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'module_qty' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function inverters(): HasMany
    {
        return $this->hasMany(Inverter::class);
    }
}
