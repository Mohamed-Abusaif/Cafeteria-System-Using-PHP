<?php

class Validator {
	private array $data;
	private array $rules;
	protected array $files;
	private array $errors = [];

	public static function make($data, array $rules, $files = null): self {
		$validator = new self();
		if ($data === null) {
			$validator->addError('input', 'Invalid or empty JSON input');
			return $validator;
		}

		$validator->data = $data;
		$validator->rules = $rules;
		$validator->files = $files ?? $_FILES;
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
				$fileUploaded = isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK;
				if (($value === null || $value === '') && !$fileUploaded) {
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

			case 'in':
				$allowedValues = explode(',', $param);
				if (!in_array($value, $allowedValues)) {
					$this->addError($field, "The $field must be one of the following: " . implode(', ', $allowedValues) . '.');
				}
				break;

			case 'file':
				if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
					$this->addError($field, "The $field must be a valid uploaded file.");
				}
				break;

			case 'mimes':
				if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
					$allowedMimes = explode(',', $param);
					$fileMime = mime_content_type($_FILES[$field]['tmp_name']);
					$ext = pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION);

					$mimeMapping = [
						'jpeg' => 'image/jpeg',
						'jpg'  => 'image/jpeg',
						'png'  => 'image/png',
						'gif'  => 'image/gif',
					];

					if (!in_array($ext, $allowedMimes) || !in_array($fileMime, $mimeMapping)) {
						$this->addError($field, "The $field must be a file of type: " . implode(', ', $allowedMimes) . ".");
					}
				}
				break;

			case 'max':
				if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
					$maxSizeKB = (int)$param;
					$fileSizeKB = $_FILES[$field]['size'] / 1024;
					if ($fileSizeKB > $maxSizeKB) {
						$this->addError($field, "The $field must not be greater than $maxSizeKB kilobytes.");
					}
				} elseif (is_string($value)) {
					if (strlen($value) > (int)$param) {
						$this->addError($field, "The $field must not be greater than $param characters.");
					}
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

			case 'exists':
				if ($value !== null) {
					if (!$this->recordExists($param, $value)) {
						$this->addError($field, "The selected $field is invalid.");
					}
				}
				break;
		}
	}

	private function existsInDatabase(string $table, string $field, $value, $ignoreId = null): bool {
		$tableModel = match ($table) {
			"users" => User::class,
			"rooms" => Room::class,
			"orders" => Order::class,
			"products" => Product::class,
			"carts" => Cart::class,
			"categories" => Category::class,
			default => null,
		};

		if ($ignoreId !== null) {
			$count = $tableModel::where($field, "=", $value)->where('id', '!=', $ignoreId)->count();
		} else {
			$count = $tableModel::where($field, "=", $value)->count();
		}
		return $count > 0;
	}

	private function recordExists(string $table, $value): bool {
		require_once '../models/User.php';
		require_once '../models/Room.php';
		require_once '../models/Order.php';
		require_once '../models/Cart.php';
		require_once '../models/Product.php';
		require_once '../models/Category.php';

		$tableModel = match ($table) {
			"users" => User::class,
			"rooms" => Room::class,
			"orders" => Order::class,
			"products" => Product::class,
			"carts" => Cart::class,
			"categories" => Category::class,
			default => null,
		};

		return (bool)$tableModel::find($value);
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
