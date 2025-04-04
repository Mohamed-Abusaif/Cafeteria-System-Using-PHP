<?php

class Validator {
	private array $data;
	private array $rules;
	private array $errors = [];

	public static function make($data, array $rules): self {
		$validator = new self();
		if ($data === null) {
			$validator->addError('input', 'Invalid or empty JSON input');
			return $validator;
		}

		$validator->data = $data;
		$validator->rules = $rules;
		$validator->validate();
		return $validator;
	}

	private function validate(): void {
		foreach ($this->rules as $field => $ruleString) {
			$rules = is_string($ruleString) ? explode('|', $ruleString) : $ruleString;
			$value = $this->getValue($field, $this->data);

			// Skip validation if field is nullable and value is null
			if (in_array('nullable', $rules) && ($value === null || $value === '')) {
				continue;
			}

			foreach ($rules as $rule) {
				if ($rule === 'nullable') {
					continue; // Already handled above
				}

				$ruleName = $rule;
				$param = null;

				if (str_contains($rule, ':')) {
					[$ruleName, $param] = explode(':', $rule, 2);
				}

				$this->applyValidationRule($field, $value, $ruleName, $param);
			}
		}
	}

	private function applyValidationRule(string $field, $value, string $ruleName, $param): void {
		switch ($ruleName) {
			case 'required':
				if ($value === null || $value === '') {
					$this->addError($field, "The $field field is required.");
				}
				break;

			case 'string':
				if ($value !== null && !is_string($value)) {
					$this->addError($field, "The $field must be a string.");
				}
				break;

			case 'email':
				if ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
					$this->addError($field, "The $field must be a valid email address.");
				}
				break;

			case 'min':
				if ($value !== null && strlen((string)$value) < (int)$param) {
					$this->addError($field, "The $field must be at least $param characters.");
				}
				break;

			case 'array':
				if ($value !== null && !is_array($value)) {
					$this->addError($field, "The $field must be an array.");
				}
				break;

			case 'numeric':
				if ($value !== null && !is_numeric($value)) {
					$this->addError($field, "The $field must be a number.");
				}
				break;

			case 'boolean':
				if ($value !== null && !is_bool($value)) {
					$this->addError($field, "The $field must be a boolean.");
				}
				break;

			case 'unique':
				if ($value !== null) {
					$parts = explode(',', $param);
					$table = $parts[0] ?? null;
					$column = $parts[1] ?? $field;
					$ignoreId = $parts[2] ?? null;

					if ($this->existsInDatabase($table, $column, $value, $ignoreId)) {
						$this->addError($field, "The $field has already been taken.");
					}
				}
				break;
		}
	}

	private function existsInDatabase(string $table, string $field, $value, $ignoreId = null): bool {
		$tableModel = match ($table) {
			"users" => User::class,
			"rooms" => Room::class,
			default => null,
		};

		if ($ignoreId !== null) {
			$count = $tableModel::where($field, "=", $value)->where('id', '!=', $ignoreId)->count();
		} else {
			$count = $tableModel::where($field, "=", $value)->count();
		}
		return $count > 0;
	}

	private function getValue(string $field, array $data) {
		$keys = explode('.', $field);
		$current = $data;

		foreach ($keys as $key) {
			if (!array_key_exists($key, $current)) {
				return null;
			}
			$current = $current[$key];
		}

		return $current;
	}

	private function addError($field, $message): void {
		$this->errors[$field][] = $message;
	}

	public function fails(): bool {
		return !empty($this->errors);
	}

	public function errors(): array {
		return $this->errors;
	}

	public function firstError(): ?string {
		if (empty($this->errors)) return null;
		$firstField = array_key_first($this->errors);
		return $this->errors[$firstField][0] ?? null;
	}
}

//
//	private function roleExists($roleId): bool {
//		$pdo = new PDO("mysql:host=localhost;dbname=your_db", "root", "");
//		$stmt = $pdo->prepare("SELECT id FROM roles WHERE id = ?");
//		$stmt->execute([$roleId]);
//		return $stmt->fetch() !== false;
//	}