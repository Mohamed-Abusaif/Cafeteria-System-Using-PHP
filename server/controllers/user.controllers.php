<?php
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use JetBrains\PhpStorm\NoReturn;

require_once '../models/User.php';
require_once '../utils/HelperTrait.php';
require '../vendor/autoload.php';

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
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {

      if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $file = $_FILES['image']['tmp_name'];
        try {
          $upload = new UploadApi();
          $response=  $upload->upload($file);
          $imageUrl = $response['secure_url'];
        } catch (\Exception $e) {
          $this->apiResponse(['error' => 'Image upload failed: ' . $e->getMessage()], 'error', 500);
          return;
        }
      } else {
        $this->apiResponse(['error' => 'No image uploaded or upload error'], 'error', 400);
        return;
      }

      if (isset($_POST['name'], $_POST['email'], $_POST['password'], $_POST['roomNo'], $_POST['confirm_password'])) {

        if ($_POST['password'] !== $_POST['confirm_password']) {
          $this->apiResponse(['error' => 'Password and confirm password do not match'], 'error', 400);
          return;
        }

        $hashedPassword = password_hash($_POST['password'], PASSWORD_BCRYPT);

        $user = User::create([
          'name' => $_POST['name'],
          'email' => $_POST['email'],
          'password' => $hashedPassword,
          'room_id' => $_POST['roomNo'],
          'image' => $imageUrl,
          'role'  =>"user"
        ]);
        $this->apiResponse($user, 'ok', 201);
      } else {
        $this->apiResponse(['error' => 'Missing required fields'], 'error', 400);
      }
    }
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
