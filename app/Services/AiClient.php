<?php

namespace App\Services;

use Laravel\Ai\Enums\Lab;
use RuntimeException;

class AiClient
{
    /**
     * Memanggil agent dan mengembalikan hanya key yang diminta sebagai array.
     *
     * @param  list<string>  $keys
     */
    public function prompt(object $agent, string $message, array $keys): array
    {
        $this->maybeSimulateFailure();

        $response = $agent->prompt(
            $message,
            provider: $this->provider(),
            model: (string) config('workload.ai_model'),
            timeout: (int) config('workload.ai_timeout'),
        );

        $output = [];
        foreach ($keys as $key) {
            $output[$key] = isset($response[$key]) ? $response[$key] : null;
        }

        return $output;
    }

    public function provider(): Lab
    {
        return Lab::tryFrom((string) config('workload.ai_provider')) ?? Lab::Gemini;
    }

    /** Untuk demo: AI_SIMULATE_FAILURE_RATE=0.3 membuat ~30% panggilan gagal. */
    private function maybeSimulateFailure(): void
    {
        $rate = (float) config('workload.ai_simulate_failure_rate');

        if ($rate > 0 && mt_rand() / mt_getrandmax() < $rate) {
            throw new RuntimeException('Simulasi kegagalan API AI (AI_SIMULATE_FAILURE_RATE).');
        }
    }
}