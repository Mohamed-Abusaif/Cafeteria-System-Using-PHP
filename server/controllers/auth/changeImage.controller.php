<?php

use JetBrains\PhpStorm\NoReturn;

require_once '../../utils/HelperTrait.php';
require_once '../../models/User.php';
require_once '../../middlewares/validator.middleware.php';
class ChangeImage {

  use HelperTrait;

  #[NoReturn] public function __construct() {
    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
      case 'POST':
        $this->changeImage();
      default:
        $this->apiResponse((object)[], 'Method Not Allowed', 405);
    }
  }
  #[NoReturn] private function changeImage($id): void {
    $user = User::find($id);
    if (!$user) {
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
    $previousPublicId = $user->public_id;
    $imageUrl = null;
    $publicId = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
      $file = $_FILES['image']['tmp_name'];
      try {
        $upload = new UploadApi();
        $response = $upload->upload($file, ['folder' => 'users']);
        $imageUrl = $response['secure_url'];
        $publicId = $response['public_id'];
        $user->image = $imageUrl;
        $user->public_id = $publicId;
        $user->save();
      } catch (\Exception $e) {
        $this->apiResponse(['error' => 'Image upload failed: ' . $e->getMessage()], 'error', 400);
      }

    }
    //to delete photo from cloudinary
    if ($previousPublicId && $publicId && $previousPublicId !== $publicId) {
      try {
        $cloudinary = new Cloudinary();
        $cloudinary->api->delete_resources($previousPublicId);
      } catch (\Cloudinary\Api\Exception $e) {
        $this->apiResponse(['error' => 'Failed to delete previous image: ' . $e->getMessage()], 'error', 400);
      }
    }
  }

}
new ChangeImage();