<?php

namespace App\Models;

use App\Enums\AnalysisStatus;
use App\Enums\SummaryScope;
use Illuminate\Database\Eloquent\Model;

class AiSummary extends Model
{
    protected $fillable = [
        'scope', 'scope_id', 'input_hash', 'summary', 'priorities',
        'status', 'attempts', 'error', 'model', 'prompt_version', 'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'scope' => SummaryScope::class,
            'status' => AnalysisStatus::class,
            'priorities' => 'array',
            'attempts' => 'integer',
            'generated_at' => 'datetime',
        ];
    }

    public static function for(SummaryScope $scope, int $scopeId = 0): ?self
    {
        return static::query()->where('scope', $scope)->where('scope_id', $scopeId)->first();
    }
}
