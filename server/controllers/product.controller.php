<?php

use JetBrains\PhpStorm\NoReturn;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Configuration\Configuration;

require_once __DIR__ . '/../vendor/autoload.php';
require_once '../models/Product.php';
require_once '../models/Category.php';
require_once '../models/CartProduct.php';
require_once '../models/OrderProduct.php';
require_once '../utils/HelperTrait.php';
require_once '../middlewares/validator.middleware.php';

$env = parse_ini_file(__DIR__ . '/../.env');
$cloudinary_url = 'cloudinary://' . $env['API_KEY'] . ':' . $env['API_SECRET'] . '@' . $env['CLOUD_NAME'] . '?secure=true';
Configuration::instance($cloudinary_url);

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
		$page = $_GET['page'] ?? 1;
		$limit = $_GET['limit'] ?? 10;
		$products = Product::where('deleted_at', 'is', null);
		if(isset($_GET['name']) && $_GET['name'] !== ''){
			$products = $products->where('name', 'like', '%'.$_GET['name'].'%');
		}
		if(isset($_GET['category_id']) && $_GET['category_id'] !== ''){
			$products = $products->where('category_id', '=', $_GET['category_id']);
		}
		if(isset($_GET['availability']) && $_GET['availability'] !== ''){
			$products = $products->where('availability', '=', $_GET['availability']);
		}

		$products = $products->sort('id', 'desc')->paginate($page, $limit);
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
		$loggedInUser = $this->getLoggedInUser();
		if ($loggedInUser['role'] !== 'Admin') {
			$this->apiResponse((object)[], 'Unauthorized', 401);
		}

		$validator = Validator::make($_POST, [
			'name' => 'required|string',
			'price' => 'required|numeric',
			'image' => 'required|file|mimes:jpg,jpeg,png,gif|max:2048',
			'category_id' => 'required|exists:categories',
			'availability' => 'nullable|in:available,unavailable',
		]);
		if ($validator->fails()) {
			$this->apiResponse((object)[], $validator->firstError(), 400);
		}

		$imageUrl = null;
		$publicId = null;
		if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
			$file = $_FILES['image']['tmp_name'];
			try {
				$upload = new UploadApi();
				$response = $upload->upload($file, ['folder' => 'products']);
				$imageUrl = $response['secure_url'];
				$publicId = $response['public_id'];
			} catch (Exception $e) {
				$this->apiResponse((object)[], 'Image upload failed: ' . $e->getMessage(), 400);
			}
		}

		$product = Product::create([
			'name' => $_POST['name'],
			'price' => $_POST['price'],
			'image' => $imageUrl,
			'public_id' => $publicId,
			'category_id' => $_POST['category_id'],
			'availability' => $_POST['availability'] ?? 'available',
		]);
		$this->apiResponse($product, 'Product created successfully', 201);
	}

	#[NoReturn] private function updateProduct($id): void {
		$loggedInUser = $this->getLoggedInUser();
		if ($loggedInUser['role'] !== 'Admin') {
			$this->apiResponse((object)[], 'Unauthorized', 401);
		}

		$jsonData = json_decode(file_get_contents("php://input"), true);
		$allowedFields = ['name', 'price', 'category_id', 'availability'];
		$jsonData = array_intersect_key($jsonData, array_flip($allowedFields));
		$validator = Validator::make($jsonData, [
			'name' => 'nullable|string',
			'price' => 'nullable|numeric',
			'category_id' => 'nullable|exists:categories',
			'availability' => 'nullable|in:available,unavailable',
		]);
		if ($validator->fails()) {
			$this->apiResponse((object)[], $validator->firstError(), 400);
		}

		$product = Product::update($id, $jsonData);
		$this->apiResponse($product, 'Product updated successfully', 200);
	}

	#[NoReturn] private function deleteProduct($id): void {
		$loggedInUser = $this->getLoggedInUser();
		if ($loggedInUser['role'] !== 'Admin') {
			$this->apiResponse((object)[], 'Unauthorized', 401);
		}

		$product = Product::find($id);
		if (!$product) {
			$this->apiResponse((object)[], 'Product not found', 404);
		}

		$relatedCarts = CartProduct::where('product_id', '=', $id)->count();
		$relatedOrders = OrderProduct::where('product_id', '=', $id)->count();
		if ($relatedCarts > 0 || $relatedOrders > 0) {
			Product::update($id, ['deleted_at' => date('Y-m-d H:i:s')]);
		} else {
			$publicId = $product['public_id'];
			$this->deleteImageFromCloudinary($publicId);
			Product::delete($id);
		}
		$this->apiResponse((object)[], 'Product deleted successfully', 200);
	}
}

new ProductController();
