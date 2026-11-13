<?php

namespace App\Services;

use RuntimeException;

class PromptRepository
{
    public function version(): string
    {
        return (string) config('workload.prompt_version');
    }

    /** Membaca resources/prompts/{name}.{version}.md lalu mengganti {{placeholder}}. */
    public function render(string $name, array $vars = []): string
    {
        $path = resource_path("prompts/{$name}.{$this->version()}.md");

        if (! is_file($path)) {
            throw new RuntimeException("File prompt tidak ditemukan: {$path}");
        }

        $replace = [];
        foreach ($vars as $key => $value) {
            $replace['{{'.$key.'}}'] = is_array($value)
                ? json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
                : (string) $value;
        }

        return strtr(file_get_contents($path), $replace);
    }
}