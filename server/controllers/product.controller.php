<?php

use JetBrains\PhpStorm\NoReturn;

require_once '../models/Product.php';
require_once '../models/Category.php';
require_once '../utils/HelperTrait.php';
require_once '../middlewares/validator.middleware.php';

class ProductController {
	use HelperTrait;

	#[NoReturn] public function __construct() {
		$id = $this->getIdFromUrl();
		$method = $_SERVER['REQUEST_METHOD'];
		switch ($method) {
			case 'GET':
				if ($id) $this->getOneProduct($id);
				else $this->getProducts();
			case 'POST':
				$this->createProduct();
			case 'PATCH':
				$this->updateProduct($id);
			case 'DELETE':
				$this->deleteProduct($id);
			default:
				$this->apiResponse(null, 'Method Not Allowed', 405);
		}
	}

	#[NoReturn] private function getProducts(): void {
		$products = Product::all();
		$this->apiResponse($products, 'ok', 200);
	}

	#[NoReturn] private function getOneProduct($id): void {
		$product = Product::find($id);
		if ($product) {
			$this->apiResponse($product, 'ok', 200);
		} else {
			$this->apiResponse((object)[], 'Product not found', 404);
		}
	}

	#[NoReturn] private function createProduct(): void {
		$jsonData = json_decode(file_get_contents("php://input"), true);
		$validator = Validator::make($jsonData, [
			'name' => 'required|string',
			'price' => 'required|numeric',
			'category_id' => 'required|exists:categories',
			'availability' => 'nullable|in:available,unavailable',
		]);
		if ($validator->fails()) {
			$this->apiResponse((object)[], $validator->firstError(), 400);
		}

		// Handle file upload for product image
		if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
			$this->apiResponse((object)[], 'Product image is required', 400);
		}

		$validator = Validator::make(null, [], $_FILES);
		$validator->rules = [
			'image' => 'required|file|mimes:jpg,jpeg,png,gif|max:2048',
		];
		$validator->validate();
		
		if ($validator->fails()) {
			$this->apiResponse((object)[], $validator->firstError(), 400);
		}

		// Process file upload
		$uploadDir = '../uploads/products/';
		if (!file_exists($uploadDir)) {
			mkdir($uploadDir, 0777, true);
		}

		$fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
		$fileName = uniqid() . '.' . $fileExtension;
		$targetPath = $uploadDir . $fileName;

		if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
			$this->apiResponse((object)[], 'Failed to upload image', 500);
		}

		// Create product with image path
		$product = Product::create([
			'name' => $jsonData['name'],
			'price' => $jsonData['price'],
			'category_id' => $jsonData['category_id'],
			'image' => 'uploads/products/' . $fileName,
			'availability' => $jsonData['availability'] ?? 'available',
		]);
		
		$this->apiResponse($product, 'Product created successfully', 201);
	}

	#[NoReturn] private function updateProduct($id): void {
		$jsonData = json_decode(file_get_contents("php://input"), true);
		$validator = Validator::make($jsonData, [
			'name' => 'nullable|string',
			'price' => 'nullable|numeric',
			'category_id' => 'nullable|exists:categories',
			'availability' => 'nullable|in:available,unavailable',
		]);
		if ($validator->fails()) {
			$this->apiResponse((object)[], $validator->firstError(), 400);
		}

		// Handle file upload for product image if provided
		if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
			$validator = Validator::make(null, [], $_FILES);
			$validator->rules = [
				'image' => 'required|file|mimes:jpg,jpeg,png,gif|max:2048',
			];
			$validator->validate();
			
			if ($validator->fails()) {
				$this->apiResponse((object)[], $validator->firstError(), 400);
			}

			// Process file upload
			$uploadDir = '../uploads/products/';
			if (!file_exists($uploadDir)) {
				mkdir($uploadDir, 0777, true);
			}

			$fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
			$fileName = uniqid() . '.' . $fileExtension;
			$targetPath = $uploadDir . $fileName;

			if (!move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
				$this->apiResponse((object)[], 'Failed to upload image', 500);
			}

			// Add image path to update data
			$jsonData['image'] = 'uploads/products/' . $fileName;
			
			// Delete old image if exists
			$oldProduct = Product::find($id);
			if ($oldProduct && isset($oldProduct->image)) {
				$oldImagePath = '../' . $oldProduct->image;
				if (file_exists($oldImagePath)) {
					unlink($oldImagePath);
				}
			}
		}

		$product = Product::update($id, $jsonData);
		$this->apiResponse($product, 'Product updated successfully', 200);
	}

	#[NoReturn] private function deleteProduct($id): void {
		// Check if product exists and isn't already deleted
		$product = Product::find($id);
		if (!$product) {
			$this->apiResponse((object)[], 'Product not found', 404);
		}

		// Soft delete the product instead of hard deleting
		Product::softDelete($id);
		$this->apiResponse((object)[], 'Product deleted successfully', 200);
	}
}

new ProductController();
?>
