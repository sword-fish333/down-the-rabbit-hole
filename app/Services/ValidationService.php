<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * The result of a multi-step check: did it pass, what went wrong, what HTTP
 * status that maps to, and whatever the check produced along the way.
 *
 * Services return one of these rather than throwing, so a controller can branch
 * on the outcome and map it to a response without catching anything:
 *
 *     $result = $this->authService->attemptLogin($email, $password);
 *     if (! $result->isSuccessfulCheck()) {
 *         return back()->with('error', $result->getFirstError());
 *     }
 *
 * Deliberately small. Every method here is on a live path — a result object
 * with accessors nobody calls is just a second, unreliable description of what
 * the check actually returns.
 */
class ValidationService
{
    private bool $success = true;

    /** @var array<int, string> */
    private array $errors = [];

    /** @var array<string, mixed> */
    private array $validatedItems = [];

    private ?int $status = null;

    /**
     * @param  string|array<int, string>  $errors
     */
    public function errorEncountered(string|array $errors, ?int $status = Response::HTTP_BAD_REQUEST): static
    {
        $this->success = false;
        $this->errors = is_array($errors) ? array_values($errors) : [$errors];
        $this->status = $status;

        return $this;
    }

    public function successfulCheck(): static
    {
        $this->success = true;

        return $this;
    }

    public function isSuccessfulCheck(): bool
    {
        return $this->success;
    }

    public function status(): int
    {
        return $this->status ?? ($this->success ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);
    }

    public function getFirstError(): string
    {
        return (string) (reset($this->errors) ?: '');
    }

    /**
     * @param  array<string, mixed>  $items
     */
    public function addValidatedItems(array $items): static
    {
        $this->validatedItems = array_merge($this->validatedItems, $items);
        $this->success = true;

        return $this;
    }

    /**
     * A key the check promised to produce. Missing means the caller and the
     * service disagree about the contract, which is a bug rather than a state
     * to handle — so it raises rather than quietly returning null.
     */
    public function getValidatedItem(string $key): mixed
    {
        if (! array_key_exists($key, $this->validatedItems)) {
            throw new RuntimeException("ValidationService has no validated item named [{$key}].");
        }

        return $this->validatedItems[$key];
    }
}
