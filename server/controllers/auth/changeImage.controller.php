<?php

use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use JetBrains\PhpStorm\NoReturn;
require_once 'login.controller.php';
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
    $method = $_SERVER['REQUEST_METHOD'];
    $userData=Login::me();
    $id=$userData->id;
    switch ($method) {
      case 'POST':
        $this->changeImage($id);

      default:
        $this->apiResponse((object)[], 'Method Not Allowed', 405);
    }
  }

  #[NoReturn] private function changeImage($id): void {
    global $env;
    $userData = (object)User::find($id);
    if (!$userData) {
      $this->apiResponse((object)[], 'user not found', 404);
    }
    //get new photo
    $validator = Validator::make($_FILES, [
      'image' => 'required|file|mimes:jpeg,png,jpg|max:2048',
    ]);

    if ($validator->fails()) {
      $this->apiResponse((object)[], $validator->firstError(), 400);
    }
    // TO GET  OLD IMG DATA
    $previousPublicId = $userData->public_id;
    $imageUrl = null;
    $publicId = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
      $file = $_FILES['image']['tmp_name'];
      try {
        $upload = new UploadApi();
        $response = $upload->upload($file, ['folder' => 'users']);
        $imageUrl = $response['secure_url'];
        $publicId = $response['public_id'];
      $user= User::update($id, [
          'image' => $imageUrl,
          'public_id' => $publicId
        ]);
      } catch (\Exception $e) {
        $this->apiResponse(['error' => 'Image upload failed: ' . $e->getMessage()], 'error', 400);
      }
    }
    //to delete photo from cloudinary
    if ($previousPublicId && $publicId && $previousPublicId !== $publicId) {
      try {
        $config = Configuration::instance();
        $config->cloud->cloudName = $env['CLOUD_NAME'];
        $config->cloud->apiKey =  $env['API_KEY'];
        $config->cloud->apiSecret = $env['API_SECRET'];
        $config->url->secure = true;
        $cloudinary = new Cloudinary($config);
        $cloudinary->uploadApi()->destroy($previousPublicId, $options = []);
        $this->apiResponse($user, 'OK', 200);
      } catch (\Cloudinary\Api\Exception $e) {
        $this->apiResponse(['error' => 'Failed to delete previous image: ' . $e->getMessage()], 'error', 400);
      }
    }
  }


}
new ChangeImage();