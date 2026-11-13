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

        /** @return list<string> */
    public function summary(array $output): array
    {
        $errors = [];
        $length = mb_strlen(trim((string) ($output['summary'] ?? '')));

        if ($length < 50) {
            $errors[] = 'summary kosong atau terlalu pendek';
        } elseif ($length > 3000) {
            $errors[] = 'summary terlalu panjang';
        }

        if (! is_array($output['priority_rationales'] ?? null)) {
            $errors[] = 'priority_rationales harus berupa array';
        }

        return $errors;
    }

    /** @return array<string, string> activity_id => rationale (hanya id yang ada di daftar prioritas) */
    public function rationales(array $items, array $validIds): array
    {
        $map = [];

        foreach ($items as $item) {
            $id = (string) ($item['activity_id'] ?? '');
            $text = trim((string) ($item['rationale'] ?? ''));

            if (in_array($id, $validIds, true) && $text !== '') {
                $map[$id] = $text;
            }
        }

        return $map;
    }
}