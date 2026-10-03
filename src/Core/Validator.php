<?php
declare(strict_types=1);

namespace App\Core;

final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @var array<string, mixed> */
    private array $validated = [];

    private function __construct(
        private readonly array $data,
        private readonly array $rules,
    ) {}

    public static function make(array $data, array $rules): self
    {
        $v = new self($data, $rules);
        $v->validate();
        return $v;
    }

    private function validate(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;
            $rules = explode('|', $ruleString);

            /*
             * Short-circuit: if the field is `nullable` and the value is empty,
             * skip ALL remaining rules for that field. This matches Laravel's
             * behavior and lets "nullable|min:8" accept an empty value.
             */
            $isNullable = in_array('nullable', $rules, true);
            if ($isNullable && ($value === null || $value === '')) {
                $this->validated[$field] = $value;
                continue;
            }

            $stop = false;

            foreach ($rules as $rule) {
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);

                /* `nullable` is a no-op here — already handled above */
                if ($name === 'nullable') {
                    continue;
                }

                /* After a hard failure, skip rules that only apply to non-empty strings */
                if ($stop && in_array($name, ['required', 'email', 'min', 'max', 'alpha_dash', 'numeric'], true)) {
                    continue;
                }

                $failed = $this->apply($field, $value, $name, $arg);
                if ($failed === false) {
                    $stop = true;
                }
            }

            if (!isset($this->errors[$field])) {
                $this->validated[$field] = $value;
            }
        }
    }

    private function apply(string $field, mixed $value, string $name, ?string $arg): bool
    {
        $label = ucwords(str_replace(['_', '-'], ' ', $field));

        switch ($name) {
            case 'required':
                if ($value === null || $value === '') {
                    return $this->fail($field, $label . ' is required.');
                }
                return true;

            case 'email':
                if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return $this->fail($field, $label . ' must be a valid email address.');
                }
                return true;

            case 'min':
                $min = (int) $arg;
                if (is_string($value) && mb_strlen(trim($value)) < $min) {
                    return $this->fail($field, $label . ' must be at least ' . $min . ' characters.');
                }
                return true;

            case 'max':
                $max = (int) $arg;
                if (is_string($value) && mb_strlen(trim($value)) > $max) {
                    return $this->fail($field, $label . ' must not exceed ' . $max . ' characters.');
                }
                return true;

            case 'alpha_dash':
                if ($value && !preg_match('/^[A-Za-z0-9_-]+$/', (string) $value)) {
                    return $this->fail($field, $label . ' may only contain letters, numbers, dashes and underscores.');
                }
                return true;

            case 'numeric':
                if ($value !== null && $value !== '' && !is_numeric($value)) {
                    return $this->fail($field, $label . ' must be a number.');
                }
                return true;

            case 'confirmed':
                $other = $this->data[$field . '_confirmation'] ?? null;
                if ($value !== $other) {
                    return $this->fail($field, $label . ' confirmation does not match.');
                }
                return true;

            case 'unique':
                // Syntax: unique:table,column
                [$table, $column] = array_pad(explode(',', (string) $arg, 2), 2, 'id');
                $count = (int) db()->scalar(
                    "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = :v",
                    ['v' => $value]
                );
                if ($count > 0) {
                    return $this->fail($field, $label . ' is already taken.');
                }
                return true;

            case 'unique_except':
                // Syntax: unique_except:table,column,ignoreColumn,ignoreValue
                [$table, $column, $ic, $iv] = array_pad(explode(',', (string) $arg, 4), 4, '');
                $count = (int) db()->scalar(
                    "SELECT COUNT(*) FROM `{$table}` WHERE `{$column}` = :v AND `{$ic}` <> :e",
                    ['v' => $value, 'e' => $iv]
                );
                if ($count > 0) {
                    return $this->fail($field, $label . ' is already taken.');
                }
                return true;

            case 'in':
                $allowed = explode(',', (string) $arg);
                if ($value !== null && !in_array((string) $value, $allowed, true)) {
                    return $this->fail($field, $label . ' contains an invalid value.');
                }
                return true;

            case 'nullable':
                // Already handled in validate(); this case is defensive.
                return true;

            default:
                return true;
        }
    }

    private function fail(string $field, string $message): bool
    {
        $this->errors[$field] = $message;
        return false;
    }

    /* ============================================================
       Public API
       ============================================================ */

    public function fails(): bool
    {
        return !empty($this->errors);
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        foreach ($this->errors as $msg) {
            return $msg;
        }
        return null;
    }

    /**
     * Alias of firstError() — kept for backward compatibility.
     */
    public function firstString(): ?string
    {
        return $this->firstError();
    }

    public function validated(): array
    {
        return $this->validated;
    }
}