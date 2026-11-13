<?php

namespace App\Models;

use App\Enums\Frequency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    protected $fillable = [
        'activity_code', 'employee_id', 'import_batch_id',
        'activity_name', 'description', 'frequency', 'volume', 'duration_minutes',
        'annual_minutes', 'fte', 'normalized_key', 'content_hash',
        'activity_group_id', 'estimated_fte_saving',
    ];

    protected function casts(): array
    {
        return [
            'frequency' => Frequency::class,
            'volume' => 'integer',
            'duration_minutes' => 'integer',
            'annual_minutes' => 'integer',
            'fte' => 'decimal:4',
            'estimated_fte_saving' => 'decimal:4',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function importBatch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ActivityGroup::class, 'activity_group_id');
    }

    public function analysis(): BelongsTo
    {
        return $this->belongsTo(AiAnalysis::class, 'content_hash', 'content_hash');
    }

    public function scopeForDepartment(Builder $query, int $departmentId): Builder
    {
        return $query->whereHas('employee', fn (Builder $q) => $q->where('department_id', $departmentId));
    }
}
