<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'logo_path',
        'cnpj',
        'phone',
        'email',
        'plan',
        'max_clients',
        'max_plants',
        'status',
    ];

    protected $casts = [
        'max_clients' => 'integer',
        'max_plants' => 'integer',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function plants(): HasMany
    {
        return $this->hasMany(Plant::class);
    }

    public function inverters(): HasMany
    {
        return $this->hasMany(Inverter::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
