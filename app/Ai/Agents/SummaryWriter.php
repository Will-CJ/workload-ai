<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class SummaryWriter implements Agent, HasStructuredOutput
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
            'summary' => $schema->string()->required(),
            'priority_rationales' => $schema->array()
                ->items($schema->object(fn ($schema) => [
                    'activity_id' => $schema->string()->required(),
                    'rationale' => $schema->string()->required(),
                ]))
                ->required(),
        ];
    }
}