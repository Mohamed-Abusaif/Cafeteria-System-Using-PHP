<?php

use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use JetBrains\PhpStorm\NoReturn;

require_once '../models/User.php';
require_once '../utils/HelperTrait.php';
require '../vendor/autoload.php';
require_once '../middlewares/validator.middleware.php';
require_once __DIR__ . '/../vendor/autoload.php';

$env = parse_ini_file(__DIR__ . '/../.env');
$cloudinary_url = 'cloudinary://' . $env['API_KEY'] . ':' . $env['API_SECRET'] . '@' . $env['CLOUD_NAME'] . '?secure=true';
Configuration::instance($cloudinary_url);

class UserController {
  use HelperTrait;

  #[NoReturn] public function __construct() {
    $id = $this->getIdFromUrl();
    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
      case 'GET':
        $this->getUsers();
      case 'POST':
        $this->createUser();
      case 'PATCH':
        if (isset($_FILES['image'])) {
          $this->updateImage($id);
        } else {
          $this->updateUser($id);
        }
      case 'DELETE':
        $this->deleteUser($id);
      default:
        $this->apiResponse(null, 'Method Not Allowed', 405);
    }
  }

  #[NoReturn] private function getUsers(): void {
//		$users = User::all();
//		$users = User::sort('age', 'asc')->sort('rate', 'desc')->get();
    $users = User::paginate(1, 2);
    $this->apiResponse($users, 'ok', 201);
  }

  #[NoReturn] private function createUser(): void {
    $validator = Validator::make($_POST, [
      'name' => 'required|string',
      'email' => 'required|email|unique:users',
      'password' => 'required|string|min:8',
      'confirm_password' => 'required|string|min:8',
//      'image' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
      'room_id' => 'nullable|numeric|exists:rooms',
      'gender' => 'required|string|in:Male,Female',
    ]);
    if ($validator->fails()) {
      $this->apiResponse((object)[], $validator->firstError(), 404);
    }
    if ($_POST['password'] !== $_POST['confirm_password']) {
      $this->apiResponse(['error' => 'Password and confirm password do not match'], 'error', 400);
    }

    if ($_POST['gender'] === 'Male') {
      $imageUrl='https://static.vecteezy.com/system/resources/previews/046/409/821/non_2x/avatar-profile-icon-in-flat-style-male-user-profile-illustration-on-isolated-background-man-profile-sign-business-concept-vector.jpg';
    }
    if ($_POST['gender'] === 'Female') {
      $imageUrl='https://i.pinimg.com/736x/4c/30/b9/4c30b9de7fe46ffb20d4ee4229509541.jpg';
    }
    $hashedPassword = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $user = User::create([
      'name' => $_POST['name'],
      'email' => $_POST['email'],
      'password' => $hashedPassword,
      'room_id' => $_POST['room_id'] ?? null,
      'image' => $imageUrl ?? null,
      'role' => "user",
    ]);
    $this->apiResponse($user, 'ok', 201);
  }

  #[NoReturn] private function updateUser($id): void {
    $user = User::find($id);
    if (!$user) {
      $this->apiResponse((object)[], 'user not found', 404);
    }
    $jsonData = json_decode(file_get_contents("php://input"), true);
    if ($user->role === "Admin") {
      $allowedFields = ['room_id', 'role'];
    } else if ($user->role === "User") {
      $allowedFields = ['name'];
    }
    $jsonData = array_intersect_key($jsonData, array_flip($allowedFields));
    $validator = Validator::make($jsonData, [
      'name' => 'nullable|string',
      'room_id' => 'nullable|numeric|exists:rooms',
      'role' => 'nullable|string|in:User,Admin',
    ]);
    if ($validator->fails()) {
      $this->apiResponse((object)[], $validator->firstError(), 404);
    }

    $user->update($jsonData);
    $this->apiResponse($user, 'ok', 200);
  }


  #[NoReturn] private function updateImage($id): void {
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

  #[NoReturn] private function deleteUser($id): void {
    $user = User::find($id);
    if (!$user) {
      $this->apiResponse((object)[], 'user not found', 404);
    }
    $user = User::update($id, [
      'room_id' => null,
      'image' => null,
      'deleted_at' => date('Y-m-d H:i:s'),
    ]);
    //delete the photo from cloudinary
    $publicId = $user->public_id;
    if ($publicId) {
      try {
        $cloudinary = new Cloudinary();
        $cloudinary->api->delete_resources($publicId);
      } catch (\Cloudinary\Api\Exception $e) {
        $this->apiResponse(['error' => 'Failed to delete previous image: ' . $e->getMessage()], 'error', 400);
      }
    }
    $this->apiResponse($user, 'ok', 200);
  }

}

new UserController();
