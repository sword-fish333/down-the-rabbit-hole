<?php

namespace App\Traits;

use App\Services\ValidationService;

/**
 * Gives a controller (or service) a single ValidationService instance and thin
 * proxies to it, so checks read as `$this->errorEncountered(...)` /
 * `$this->successfulCheck()` and return a result the caller maps to a response.
 */
trait ValidationHelper
{
    protected ValidationService $validator;

    public function initializeValidator(): void
    {
        $this->validator = new ValidationService;
    }

    protected function isSuccessfulCheck(): bool
    {
        return $this->validator->isSuccessfulCheck();
    }

    protected function errorEncountered($message, $status = null): ValidationService
    {
        return $this->validator->errorEncountered($message, $status);
    }

    protected function addValidatedItems(array $validated_items): ValidationService
    {
        return $this->validator->addValidatedItems($validated_items);
    }

    public function successfulCheck(): ValidationService
    {
        return $this->validator->successfulCheck();
    }
}
