<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'company_id',
        'competence',
        'due_date',
        'amount_cents',
        'consumption_kwh',
        'injected_kwh',
        'compensated_kwh',
        'previous_balance_kwh',
        'current_balance_kwh',
        'credits_received_kwh',
        'credits_used_kwh',
        'tariff',
        'flag',
        'icms_value',
        'pis_value',
        'cofins_value',
        'public_lighting_value',
        'pdf_path',
        'ocr_status',
        'raw_ocr_data',
    ];

    protected function casts(): array
    {
        return [
            'competence' => 'date',
            'due_date' => 'date',
            'amount_cents' => 'integer',
            'consumption_kwh' => 'decimal:2',
            'injected_kwh' => 'decimal:2',
            'compensated_kwh' => 'decimal:2',
            'previous_balance_kwh' => 'decimal:2',
            'current_balance_kwh' => 'decimal:2',
            'credits_received_kwh' => 'decimal:2',
            'credits_used_kwh' => 'decimal:2',
            'tariff' => 'decimal:6',
            'icms_value' => 'decimal:2',
            'pis_value' => 'decimal:2',
            'cofins_value' => 'decimal:2',
            'public_lighting_value' => 'decimal:2',
            'raw_ocr_data' => 'array',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    protected function amount(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->amount_cents !== null ? $this->amount_cents / 100 : null,
        );
    }
}
