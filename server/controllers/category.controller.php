<?php

use JetBrains\PhpStorm\NoReturn;

require_once '../models/Category.php';
require_once '../models/Product.php';
require_once '../utils/HelperTrait.php';
require_once '../middlewares/validator.middleware.php';

class CategoryController {
	use HelperTrait;

	#[NoReturn] public function __construct() {
		$id = $this->getIdFromUrl();
		$method = $_SERVER['REQUEST_METHOD'];
		switch ($method) {
			case 'GET':
				if ($id) $this->getOneCategory($id);
				else $this->getCategories();
			case 'POST':
				$this->createCategory();
			case 'PATCH':
				$this->updateCategory($id);
			case 'DELETE':
				$this->deleteCategory($id);
			default:
				$this->apiResponse(null, 'Method Not Allowed', 405);
		}
	}

	#[NoReturn] private function getCategories(): void {
		$categories = Category::all();
		$this->apiResponse($categories, 'ok', 200);
	}

	#[NoReturn] private function getOneCategory($id): void {
		$category = Category::find($id);
		if ($category) {
			$this->apiResponse($category, 'ok', 200);
		} else {
			$this->apiResponse((object)[], 'Category not found', 404);
		}
	}

	#[NoReturn] private function createCategory(): void {
		$jsonData = json_decode(file_get_contents("php://input"), true);
		$validator = Validator::make($jsonData, [
			'name' => 'required|string|unique:categories',
		]);
		if ($validator->fails()) {
			$this->apiResponse((object)[], $validator->firstError(), 404);
		}

		$category = Category::create([
			'name' => $jsonData['name'],
		]);
		$this->apiResponse($category, 'ok', 201);
	}

	#[NoReturn] private function updateCategory($id): void {
		$jsonData = json_decode(file_get_contents("php://input"), true);
		$validator = Validator::make($jsonData, [
			'name' => 'nullable|string|unique:categories,name,' . $id,
		]);
		if ($validator->fails()) {
			$this->apiResponse((object)[], $validator->firstError(), 404);
		}

		$category = Category::update($id, $jsonData);
		$this->apiResponse($category, 'ok', 200);
	}

	#[NoReturn] private function deleteCategory($id): void {

		//if the category has related products return error 
		$relatedProducts = Product::where('category_id', '=', $id)->count();
		if ($relatedProducts > 0) {
			$this->apiResponse((object)[], 'Category cannot be deleted because it has related products', 400);
		}

		$category = Category::delete($id);
		$this->apiResponse($category, 'ok', 200);
	}
}

new CategoryController();
?>