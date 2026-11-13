<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class ActivityGrouper implements Agent, HasStructuredOutput
{
    use Promptable;

    /** @param list<string> $groupCodes */
    public function __construct(
        private string $systemPrompt = '',
        private array $groupCodes = [],
    ) {}

    public function instructions(): Stringable|string
    {
        return $this->systemPrompt;
    }

    public function schema(JsonSchema $schema): array
    {
        $groupCode = $schema->string();
        if ($this->groupCodes !== []) {
            $groupCode = $groupCode->enum($this->groupCodes);
        }

        return [
            'assignments' => $schema->array()
                ->items($schema->object(fn ($schema) => [
                    'key' => $schema->string()->required(),
                    'group_code' => $groupCode->required(),
                ]))
                ->required(),
        ];
    }
}