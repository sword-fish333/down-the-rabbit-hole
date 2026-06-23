<?php

namespace App\Contracts;

use App\DTOs\LlmStreamResult;
use App\DTOs\TurnPlan;

/**
 * The single seam over the LLM provider. Swap App\Services\Llm\AnthropicClient
 * for an SDK-backed or BYOK implementation without touching the chat services.
 */
interface LlmClient
{
    /**
     * Stream one assistant turn, forwarding each text delta to $onText as it
     * arrives, and return the assembled result once the stream closes.
     *
     * @param  callable(string): void  $onText
     */
    public function streamTurn(TurnPlan $plan, callable $onText): LlmStreamResult;
}
