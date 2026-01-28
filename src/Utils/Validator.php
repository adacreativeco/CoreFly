<?php

declare(strict_types=1);

namespace CoreFly\Utils;

class Validator
{
    /**
     * @var array
     */
    private array $data;

    /**
     * @var array
     */
    private array $errors = [];

    /**
     * Validator constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Check if fields are present and not empty.
     *
     * @param string ...$fields
     * @return self
     */
    public function required(string ...$fields): self
    {
        foreach ($fields as $field) {
            if (!isset($this->data[$field]) || trim((string)$this->data[$field]) === '') {
                $this->addError($field, 'This field is required');
            }
        }
        return $this;
    }

    /**
     * Check if a field is a valid email.
     *
     * @param string $field
     * @return self
     */
    public function email(string $field): self
    {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'Invalid email format');
        }
        return $this;
    }

    /**
     * Add an error message for a field.
     *
     * @param string $field
     * @param string $message
     */
    public function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    /**
     * Check if validation passed.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return empty($this->errors);
    }

    /**
     * Get validation errors.
     *
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
