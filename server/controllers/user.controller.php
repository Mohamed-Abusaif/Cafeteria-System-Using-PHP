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
		$method = $_SERVER['REQUEST_METHOD'];
		switch ($method) {
			case 'GET':
				$this->getUsers();
			case 'POST':
				$this->createUser();
			case 'PATCH':
				$this->updateUser();
			case 'DELETE':
				$this->deleteUser();
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
      'image' => 'nullable|file|mimes:jpeg,png,jpg|max:2048',
      'roomNo' => 'nullable|numeric|exists:rooms',
      'gender' => 'required|string|in:Male,Female',
    ]);
    if ($validator->fails()) {
      $this->apiResponse((object)[], $validator->firstError(), 404);
    }
    if ($_POST['password'] !== $_POST['confirm_password']) {
      $this->apiResponse(['error' => 'Password and confirm password do not match'], 'error', 400);
    }

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
      $file = $_FILES['image']['tmp_name'];
      try {
        $upload = new UploadApi();
        $response=  $upload->upload($file, ['folder' => 'users']);
        $imageUrl = $response['secure_url'];
      } catch (\Exception $e) {
        $this->apiResponse(['error' => 'Image upload failed: ' . $e->getMessage()], 'error', 400);
      }
    } else {
      $this->apiResponse(['error' => 'No image uploaded or upload error'], 'error', 400);
    }

    $hashedPassword = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $user = User::create([
      'name' => $_POST['name'],
      'email' => $_POST['email'],
      'password' => $hashedPassword,
      'room_id' => $_POST['roomNo'] ?? null,
      'image' => $imageUrl ?? null,
      'role' => "user",
      'gender' => $_POST['gender']
    ]);
    $this->apiResponse($user, 'ok', 201);
  }

  #[NoReturn] private function updateUser(): void {
		$jsonData = json_decode(file_get_contents("php://input"), true);
		$user = User::update($jsonData['id'], $jsonData);
		$this->apiResponse($user, 'ok', 200);
	}

	#[NoReturn] private function deleteUser(): void {
		$jsonData = json_decode(file_get_contents("php://input"), true);
		$user = User::delete($jsonData['id']);
		$this->apiResponse($user, 'ok', 200);
	}
}

new UserController();
