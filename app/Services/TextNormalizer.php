<?php

namespace App\Services;

class TextNormalizer
{
    private const STOPWORDS = [
        'dan', 'ke', 'di', 'dari', 'yang', 'untuk', 'dengan', 'secara', 'atau', 'per',
        'serta', 'lalu', 'sesuai', 'sebelum', 'setelah', 'dalam', 'pada', 'oleh', 'via',
        'the', 'a', 'an', 'of', 'to', 'and', 'for', 'in', 'on',
    ];

    /** lowercase, buang tanda baca, rapikan spasi */
    public function normalize(?string $text): string
    {
        $text = mb_strtolower((string) $text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * Kunci untuk mendeteksi aktivitas yang (hampir) sama:
     * token nama tanpa stopword, unik, diurutkan.
     * "Input Invoice ke SAP" dan "input invoice SAP" -> "input invoice sap"
     */
    public function key(string $activityName): string
    {
        $tokens = array_filter(
            explode(' ', $this->normalize($activityName)),
            fn (string $word) => $word !== '' && ! in_array($word, self::STOPWORDS, true),
        );
        $tokens = array_values(array_unique($tokens));
        sort($tokens);

        return mb_substr(implode(' ', $tokens), 0, 255);
    }

    /**
     * Kunci cache AI. Berubah kalau isi aktivitas, versi prompt, atau model berubah.
     */
    public function contentHash(string $activityName, ?string $description): string
    {
        return hash('sha256', implode('|', [
            $this->normalize($activityName),
            $this->normalize($description),
            config('workload.prompt_version'),
            config('workload.ai_model'),
        ]));
    }
}