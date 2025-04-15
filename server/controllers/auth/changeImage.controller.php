<?php

use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use JetBrains\PhpStorm\NoReturn;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once '../../utils/HelperTrait.php';
require_once '../../models/User.php';
require_once '../../middlewares/validator.middleware.php';

$env = parse_ini_file(__DIR__ . '/../../.env');
$cloudinary_url = 'cloudinary://' . $env['API_KEY'] . ':' . $env['API_SECRET'] . '@' . $env['CLOUD_NAME'] . '?secure=true';
Configuration::instance($cloudinary_url);

class ChangeImage {
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
    // TODO: role user , same user id
    $loggedInUser = $this->getLoggedInUser();
    if($loggedInUser['id'] !== $id ) {
      $this->apiResponse(null, 'Unauthorized', 401);
    }
    $user = User::find($id);
    if (!$user) {
      $this->apiResponse((object)[], 'user not found', 404);
    }

    $validator = Validator::make($_POST, [
      'image' => 'required|file|mimes:jpeg,png,jpg|max:2048',
    ]);
    if ($validator->fails()) {
      $this->apiResponse((object)[], $validator->firstError(), 400);
    }

    // TO GET OLD IMG DATA
    $previousPublicId = $user['public_id'];
    $publicId = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
      $file = $_FILES['image']['tmp_name'];
      try {
        $upload = new UploadApi();
        $response = $upload->upload($file, ['folder' => 'users']);
        $imageUrl = $response['secure_url'];
        $publicId = $response['public_id'];
        $user = User::update($id, [
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
	  $this->apiResponse($user, 'OK', 200);
  }
}

new ChangeImage();
