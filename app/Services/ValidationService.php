<?php


namespace App\Services;


use Symfony\Component\HttpFoundation\Response;

class ValidationService
{
    private bool $success;
    private array $errors = [];
    private array $validatedItems = [];
    private ?int $status = null;
    private ?array $additional_data = [];

    public function __construct()
    {
        $this->success = true;
    }

    public function errorEncountered(string|array $errors, $status = Response::HTTP_BAD_REQUEST, ?array $additional_data = []): static
    {
        $this->success = false;
        $this->errors = is_array($errors) ? $errors : [$errors];
        $this->status = $status;
        $this->additional_data = $additional_data;
        return $this;
    }

    public function successfulCheck(): static
    {
        $this->success = true;
        return $this;
    }

    public function status(): int
    {
        if ($this->status) {
            return $this->status;
        }
        return $this->success ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST;
    }

    public function getAdditionalData(): array
    {
        return $this->additional_data;
    }

    public function setStatus(int $status): void
    {
        $this->status = $status;
    }

    public function isSuccessfulCheck(): bool
    {
        return $this->success;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getFirstError(): string
    {
        return current($this->errors);
    }

    public function addValidatedItems(array $items): static
    {
        $this->validatedItems = array_merge($this->validatedItems, $items);
        $this->success = true;
        return $this;
    }

    public function getArrayOfValidatedItems($keys): array
    {
        $foundItems = [];
        foreach ($keys as $key) {
            foreach ($this->validatedItems as $k => $validatedItem) {
                if ($key === $k) {
                    $foundItems[] = $validatedItem;

                }
            }
        }
        return $foundItems;
    }

    public function getAllValidatedItems(): array
    {
        return $this->validatedItems;
    }

    /**
     * @throws \Exception
     */
    public function getValidatedItem($keys)
    {
        if (is_array($keys)) {
            $this->checkIfValidKeys($keys);
            return $this->getArrayOfValidatedItems($keys);
        } else {
            if (!array_key_exists($keys, $this->validatedItems)) {
                throw new \Exception('Invalid key in ValidationService. Keys passed:' . json_encode($keys));
            }
            return $this->validatedItems[$keys];
        }
    }

    public function hasValidatedItem(string $key): bool
    {
        return isset($this->validatedItems[$key]) && $this->validatedItems[$key];
    }


    private function checkIfValidKeys($keys): void
    {
        if (array_diff_key(array_flip($keys), $this->validatedItems)) {
            throw new \Exception('Invalid key in ValidationService. Keys passed:' . json_encode($keys));
        }
    }
}
