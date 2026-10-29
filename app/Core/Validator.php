<?php
declare(strict_types=1);

namespace App\Core;

/** Tiny validator: Validator::make($data, ['name' => 'required|max:80', 'email' => 'email']) */
final class Validator
{
    private array $errors = [];

    public function __construct(private readonly array $data, array $rules)
    {
        foreach ($rules as $field => $ruleStr) {
            $value = $data[$field] ?? null;
            if (is_array($value) && isset($value['ar'])) {
                $value = trim((string)$value['ar']) !== '' ? $value['ar'] : (string)($value['en'] ?? '');
            }
            $value = is_scalar($value) ? trim((string)$value) : $value;
            foreach (explode('|', $ruleStr) as $rule) {
                [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);
                $label = t('fields.' . $field) !== 'fields.' . $field ? t('fields.' . $field) : ucfirst(str_replace('_', ' ', $field));
                switch ($name) {
                    case 'required':
                        if ($value === null || $value === '' || $value === []) $this->errors[$field] = t('validation.required', ['field' => $label]);
                        break;
                    case 'email':
                        if ($value !== '' && $value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) $this->errors[$field] = t('validation.email', ['field' => $label]);
                        break;
                    case 'max':
                        if (is_string($value) && mb_strlen($value) > (int)$arg) $this->errors[$field] = t('validation.max', ['field' => $label, 'max' => $arg]);
                        break;
                    case 'min':
                        if (is_string($value) && $value !== '' && mb_strlen($value) < (int)$arg) $this->errors[$field] = t('validation.min', ['field' => $label, 'min' => $arg]);
                        break;
                    case 'url':
                        if (is_string($value) && $value !== '' && !preg_match('#^(https?://|/|mailto:|tel:)#i', $value)) $this->errors[$field] = t('validation.url', ['field' => $label]);
                        break;
                    case 'in':
                        if ($value !== '' && $value !== null && !in_array((string)$value, explode(',', (string)$arg), true)) $this->errors[$field] = t('validation.invalid', ['field' => $label]);
                        break;
                    case 'slug':
                        if (is_string($value) && $value !== '' && !preg_match('/^[\p{L}\p{N}\-]+$/u', $value)) $this->errors[$field] = t('validation.slug', ['field' => $label]);
                        break;
                    case 'confirmed':
                        if (($data[$field . '_confirmation'] ?? '') !== $value) $this->errors[$field] = t('validation.confirmed', ['field' => $label]);
                        break;
                    case 'phone':
                        if (is_string($value) && $value !== '' && !preg_match('/^[+\d][\d\s\-()]{5,24}$/', $value)) $this->errors[$field] = t('validation.phone', ['field' => $label]);
                        break;
                }
                if (isset($this->errors[$field])) break;
            }
        }
    }

    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    public function fails(): bool { return $this->errors !== []; }
    public function errors(): array { return $this->errors; }
    public function first(): string { return (string)(reset($this->errors) ?: ''); }
}
