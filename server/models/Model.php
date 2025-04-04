<?php
require_once "Database.php";

abstract class Model {
	protected static PDO $con;
	protected static string $table;
	protected static string $pagination;
	protected static array $params = [];
	protected static array $sorting = [];
	protected static array $conditions = [];

	public static function init(): void {
		self::$con = Database::getInstance()->getConnection();
	}

	public static function all(): false|array {
		self::init();
		$stmt = self::$con->prepare("SELECT * FROM " . static::$table);
		$stmt->execute();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public static function find($id) {
		self::init();
		$stmt = self::$con->prepare("SELECT * FROM " . static::$table . " WHERE id = ?");
		$stmt->execute(array($id));
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	public static function create($data): false|array {
		self::init();
		$columns = implode(", ", array_keys($data));
		$placeholders = ":" . implode(", :", array_keys($data));
		$stmt = self::$con->prepare("INSERT INTO " . static::$table . " ($columns) VALUES ($placeholders)");
		foreach ($data as $key => $val) {
			$stmt->bindValue(":$key", $val);
		}
		$stmt->execute();
		$lastId = self::$con->lastInsertId();
		return self::find($lastId);
	}

	public static function update($id, $data): false|array {
		self::init();
		$setPart = implode(", ", array_map(function ($col) {
			return "$col = :$col";
		}, array_keys($data)));

		$stmt = self::$con->prepare("UPDATE " . static::$table . " SET $setPart WHERE id = :id");
		foreach ($data as $key => $val) {
			$stmt->bindValue(":$key", $val);
		}
		$stmt->bindValue(":id", $id);
		$stmt->execute();
		return self::find($id);
	}

	public static function delete($id): bool {
		self::init();
		$stmt = self::$con->prepare("DELETE FROM " . static::$table . " WHERE id = ?");
		return $stmt->execute(array($id));
	}

	public static function where($column, $operator, $value): Model {
		self::$conditions[] = "$column $operator :$column";
		self::$params[$column] = $value;
		return new static;
	}

	public static function sort($column, $order): Model {
		self::$sorting[] = "$column $order";
		return new static;
	}

	public static function get(): false|array {
		$stmt = self::prepare();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	public static function first() {
		$stmt = self::prepare();
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}

	public static function count(): int {
		$stmt = self::prepare();
		return $stmt->rowCount();
	}

	public static function paginate($page, $limit = 10): false|array {
		$offset = ($page - 1) * $limit;
		self::$pagination = " LIMIT $limit OFFSET $offset";
		$stmt = self::prepare();
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}

	private static function prepare(): PDOStatement|false {
		self::init();
		$query = "SELECT * FROM " . static::$table;
		if (!empty(self::$conditions)) {
			$query .= " WHERE " . implode(" AND ", self::$conditions);
		}
		if (!empty(self::$sorting)) {
			$query .= " ORDER BY " . implode(", ", self::$sorting);
		}
		if (!empty(self::$pagination)) {
			$query .= self::$pagination;
		}
		$stmt = self::$con->prepare($query);
		foreach (self::$params as $key => $val) {
			$stmt->bindValue(":$key", $val);
		}
		$stmt->execute();
		self::$params = [];
		self::$sorting = [];
		self::$conditions = [];
		return $stmt;
	}
}
