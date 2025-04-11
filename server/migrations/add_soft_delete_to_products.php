<?php

require_once __DIR__ . '/../models/Database.php';

$db = Database::getInstance();
$conn = $db->getConnection();

// Check connection
if (!$conn) {
    die("Connection failed");
}

$sql = "ALTER TABLE products ADD COLUMN deleted_at TIMESTAMP NULL DEFAULT NULL";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    echo "Added deleted_at column to products table successfully\n";
} catch (PDOException $e) {
    echo "Error adding deleted_at column: " . $e->getMessage() . "\n";
} 