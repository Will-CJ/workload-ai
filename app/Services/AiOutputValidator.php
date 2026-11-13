<?php

namespace App\Services;

use App\Enums\AutomationLevel;

class AiOutputValidator
{
    /** @return list<string> daftar error; kosong berarti valid */
    public function analysis(array $output): array
    {
        $errors = [];

        if (! in_array($output['automation_level'] ?? null, AutomationLevel::values(), true)) {
            $errors[] = 'automation_level harus salah satu dari high, medium, low';
        }

        foreach (['reason', 'recommendation'] as $field) {
            $length = mb_strlen(trim((string) ($output[$field] ?? '')));
            if ($length < 10) {
                $errors[] = "{$field} kosong atau terlalu pendek";
            } elseif ($length > 1000) {
                $errors[] = "{$field} terlalu panjang";
            }
        }

        return $errors;
    }

    /**
     * Hanya menerima key yang dikirim dan group_code yang ada di taksonomi.
     *
     * @return array<string, string> key => group_code
     */
    public function grouping(array $assignments, array $validKeys, array $validCodes): array
    {
        $map = [];

        foreach ($assignments as $item) {
            $key = (string) ($item['key'] ?? '');
            $code = (string) ($item['group_code'] ?? '');

            if (in_array($key, $validKeys, true) && in_array($code, $validCodes, true)) {
                $map[$key] = $code;
            }
        }

        return $map;
    }
}