<?php

namespace App\Services\Llm;

use Generator;
use Psr\Http\Message\StreamInterface;

/**
 * Reads a Server-Sent Events body and yields each `data:` frame JSON-decoded.
 * Shared by the provider clients — they differ in the shape of the frames, not
 * in how the wire is read. Stops early if the learner disconnects so the caller
 * can still persist the partial turn.
 */
trait ReadsSseStream
{
    /**
     * @return Generator<int, array<string, mixed>>
     */
    private function sseFrames(StreamInterface $body): Generator
    {
        $buffer = '';

        while (! $body->eof()) {
            if (connection_aborted()) {
                break;
            }

            $buffer .= $body->read(8192);

            while (($pos = strpos($buffer, "\n")) !== false) {
                $line = trim(substr($buffer, 0, $pos));
                $buffer = substr($buffer, $pos + 1);

                if ($line === '' || ! str_starts_with($line, 'data:')) {
                    continue;
                }

                $data = json_decode(trim(substr($line, 5)), true);

                if (is_array($data)) {
                    yield $data;
                }
            }
        }
    }
}
