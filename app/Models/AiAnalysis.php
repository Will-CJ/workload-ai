<?php

namespace App\Models;

use App\Enums\AnalysisStatus;
use App\Enums\AutomationLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiAnalysis extends Model
{
    protected $table = 'ai_analyses';

    protected $fillable = [
        'content_hash', 'group_code', 'automation_level', 'reason', 'recommendation',
        'status', 'attempts', 'error', 'model', 'prompt_version', 'raw_response', 'analyzed_at',
    ];

    protected function casts(): array
    {
        return [
            'automation_level' => AutomationLevel::class,
            'status' => AnalysisStatus::class,
            'attempts' => 'integer',
            'raw_response' => 'array',
            'analyzed_at' => 'datetime',
        ];
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'content_hash', 'content_hash');
    }

    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', AnalysisStatus::Success);
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', AnalysisStatus::Failed);
    }
}
