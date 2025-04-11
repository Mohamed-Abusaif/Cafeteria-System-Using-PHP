<?php

require_once "Model.php";
class Product extends Model {
  public static string $table = "products";

  // Get all active (not deleted) products
  public static function all(): false|array {
    self::init();
    $stmt = self::$con->prepare("SELECT * FROM " . static::$table . " WHERE deleted_at IS NULL");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
  
  // Find a product by ID that hasn't been soft deleted
  public static function find($id) {
    self::init();
    $stmt = self::$con->prepare("SELECT * FROM " . static::$table . " WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute(array($id));
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }
  
  // Soft delete a product
  public static function softDelete($id): bool {
    self::init();
    $stmt = self::$con->prepare("UPDATE " . static::$table . " SET deleted_at = NOW() WHERE id = ?");
    return $stmt->execute(array($id));
  }
  
  // Override where method to include deleted_at IS NULL by default
  public static function where($column, $operator, $value): Model {
    if (empty(self::$conditions)) {
      self::$conditions[] = "deleted_at IS NULL";
    }
    self::$conditions[] = "$column $operator :$column";
    self::$params[$column] = $value;
    return new static;
  }
  
  // Get all products including deleted ones
  public static function withTrashed(): Model {
    return new static;
  }
  
  // Restore a soft-deleted product
  public static function restore($id): bool {
    self::init();
    $stmt = self::$con->prepare("UPDATE " . static::$table . " SET deleted_at = NULL WHERE id = ?");
    return $stmt->execute(array($id));
  }
}