<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use BelongsToCompany, HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'password',
        'role',
        'phone',
        'whatsapp',
        'cpf_cnpj',
        'address',
        'city',
        'state',
        'zip',
        'distributor',
        'uc_number',
        'meter_number',
        'equatorial_login',
        'equatorial_password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'equatorial_login',
        'equatorial_password',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'equatorial_login' => 'encrypted',
            'equatorial_password' => 'encrypted',
        ];
    }

    public function plants(): HasMany
    {
        return $this->hasMany(Plant::class, 'client_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'client_id');
    }

    public function scopeClients(Builder $query): Builder
    {
        return $query->where('role', 'client');
    }

    public function scopeEmployees(Builder $query): Builder
    {
        return $query->where('role', 'employee');
    }
}
