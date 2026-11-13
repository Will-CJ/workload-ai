<?php

namespace App\Ai\Agents;

use App\Enums\AutomationLevel;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class ActivityAnalyzer implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(private string $systemPrompt = '') {}

    public function instructions(): Stringable|string
    {
        return $this->systemPrompt;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'automation_level' => $schema->string()->enum(AutomationLevel::values())->required(),
            'reason' => $schema->string()->required(),
            'recommendation' => $schema->string()->required(),
        ];
    }
}