<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'client_id',
        'plant_id',
        'company_id',
        'period_start',
        'period_end',
        'type',
        'data',
        'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }
}
