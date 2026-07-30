<?php

namespace App\Services\Llm;

/**
 * Decodes a structured-output response body.
 *
 * The providers constrain generation to the schema, so the happy path is a
 * plain json_decode. The brace-slice fallback exists only for the pathological
 * case of a provider wrapping the object in prose — it is a safety net, not the
 * mechanism (that distinction is why the old trailing-control-block parser was
 * replaced: there, scraping *was* the mechanism).
 */
trait DecodesJsonPayload
{
    /**
     * @return array<string, mixed>
     */
    private function decodePayload(string $text): array
    {
        $decoded = json_decode(trim($text), true);

        if (is_array($decoded)) {
            return $decoded;
        }

        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false || $end <= $start) {
            fullLog('Structured output was not JSON: '.mb_substr($text, 0, 500));

            return [];
        }

        $decoded = json_decode(substr($text, $start, $end - $start + 1), true);

        if (! is_array($decoded)) {
            fullLog('Structured output failed to decode: '.mb_substr($text, 0, 500));

            return [];
        }

        return $decoded;
    }
}
