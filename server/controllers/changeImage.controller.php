<?php

use JetBrains\PhpStorm\NoReturn;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Configuration\Configuration;

require_once __DIR__ . '/../vendor/autoload.php';
require_once '../utils/HelperTrait.php';
require_once '../models/Product.php';
require_once '../middlewares/validator.middleware.php';

$env = parse_ini_file(__DIR__ . '/../.env');
$cloudinary_url = 'cloudinary://' . $env['API_KEY'] . ':' . $env['API_SECRET'] . '@' . $env['CLOUD_NAME'] . '?secure=true';
Configuration::instance($cloudinary_url);

class ChangeImageProduct {
  use HelperTrait;

  #[NoReturn] public function __construct() {
	  $id = $this->getIdFromUrl();
    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
      case 'POST':
        $this->changeImage($id);
      default:
        $this->apiResponse((object)[], 'Method Not Allowed', 405);
    }
  }

  #[NoReturn] private function changeImage($id): void {
	  $loggedInUser = $this->getLoggedInUser();
	  if ($loggedInUser['role'] !== 'Admin') {
		  $this->apiResponse((object)[], 'Unauthorized', 401);
	  }

	  $product = Product::find($id);
    if (!$product) {
      $this->apiResponse((object)[], 'product not found', 404);
    }

    $validator = Validator::make($_POST, [
      'image' => 'required|file|mimes:jpeg,png,jpg|max:2048',
    ]);
    if ($validator->fails()) {
      $this->apiResponse((object)[], $validator->firstError(), 400);
    }

    // TO GET OLD IMG DATA
    $previousPublicId = $product['public_id'];
    $publicId = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
      $file = $_FILES['image']['tmp_name'];
      try {
        $upload = new UploadApi();
        $response = $upload->upload($file, ['folder' => 'products']);
        $imageUrl = $response['secure_url'];
        $publicId = $response['public_id'];
				$product = Product::update($id, [
          'image' => $imageUrl,
          'public_id' => $publicId
        ]);
      } catch (Exception $e) {
        $this->apiResponse((object)[], 'Image upload failed: ' . $e->getMessage(), 400);
      }
    }

    //to delete photo from cloudinary
    if ($previousPublicId && $publicId && $previousPublicId !== $publicId) {
	    $this->deleteImageFromCloudinary($previousPublicId);
    }
	  $this->apiResponse($product, 'OK', 200);
  }
}

new ChangeImageProduct();
