<?php

use JetBrains\PhpStorm\NoReturn;

require '../models/User.php';
require '../models/Cart.php';
require '../models/Order.php';
require_once '../utils/HelperTrait.php';
require '../vendor/autoload.php';
require_once '../middlewares/validator.middleware.php';
require_once __DIR__ . '/../vendor/autoload.php';

class UserController {
  use HelperTrait;

  #[NoReturn] public function __construct() {
    $id = $this->getIdFromUrl();
    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
      case 'GET':
        if ($id) $this->getOneUser($id);
        else $this->getUsers();
      case 'POST':
        $this->createUser();
      case 'PATCH':
        $this->updateUser($id);
      case 'DELETE':
        $this->deleteUser($id);
      default:
        $this->apiResponse(null, 'Method Not Allowed', 405);
    }
  }

  #[NoReturn] private function getUsers(): void {
	  $page = $_GET['page'] ?? 1;
		$limit = $_GET['limit'] ?? 10;
	  $users = User::where('deleted_at', 'is', null);
	  if(isset($_GET['name']) && $_GET['name'] !== ''){
		  $users = $users->where('name', 'like', '%'.$_GET['name'].'%');
	  }
	  if(isset($_GET['role']) && $_GET['role'] !== ''){
		  $users = $users->where('role', '=', $_GET['role']);
	  }

		$users = $users->sort('id', 'desc')->paginate($page, $limit);
	  $this->apiResponse($users, 'ok', 200);
  }

  #[NoReturn] private function getOneUser($id): void {
    $user = User::find($id);
    if ($user) {
      $this->apiResponse($user, 'ok', 200);
    } else {
      $this->apiResponse((object)[], 'User not found', 404);
    }
  }

  #[NoReturn] private function createUser(): void {
	  $jsonData = json_decode(file_get_contents("php://input"), true);
    $validator = Validator::make($jsonData, [
      'name' => 'required|string',
      'email' => 'required|email|unique:users',
      'password' => 'required|string|min:8',
      'confirm_password' => 'required|string|min:8',
      'room_id' => 'nullable|numeric|exists:rooms',
      'gender' => 'required|string|in:Male,Female',
      'role' => 'required|string|in:User,Admin',
    ]);
    if ($validator->fails()) {
      $this->apiResponse((object)[], $validator->firstError(), 404);
    }
    if ($jsonData['password'] !== $jsonData['confirm_password']) {
      $this->apiResponse(['error' => 'Password and confirm password do not match'], 'error', 400);
    }

    if ($jsonData['gender'] === 'Male') {
      $imageUrl='https://static.vecteezy.com/system/resources/previews/046/409/821/non_2x/avatar-profile-icon-in-flat-style-male-user-profile-illustration-on-isolated-background-man-profile-sign-business-concept-vector.jpg';
    } else if ($jsonData['gender'] === 'Female') {
      $imageUrl='https://i.pinimg.com/736x/4c/30/b9/4c30b9de7fe46ffb20d4ee4229509541.jpg';
    }
    $hashedPassword = password_hash($jsonData['password'], PASSWORD_BCRYPT);
    $user = User::create([
      'name' => $jsonData['name'],
      'email' => $jsonData['email'],
      'password' => $hashedPassword,
      'room_id' => $jsonData['room_id'] ?? null,
      'image' => $imageUrl ?? null,
      'role' => $jsonData['role'],
    ]);
    $this->apiResponse($user, 'ok', 201);
  }

  #[NoReturn] private function updateUser($id): void {
    $user =User::find($id);
    if (!$user) {
      $this->apiResponse((object)[], 'user not found', 404);
    }
    $jsonData = json_decode(file_get_contents("php://input"), true);
    if(!$jsonData){
     $this->apiResponse((object)[], 'There is no data to update', 404);
    }
		// TODO: check role from token if it is admin change ['room_id', 'role'] else it is user change ['name']
	  $allowedFields = ['name', 'room_id', 'role'];
    $jsonData = array_intersect_key($jsonData, array_flip($allowedFields));
    $validator = Validator::make($jsonData, [
      'name' => 'nullable|string',
      'room_id' => 'nullable|numeric|exists:rooms',
      'role' => 'nullable|string|in:User,Admin',
    ]);
    if ($validator->fails()) {
      $this->apiResponse((object)[], $validator->firstError(), 404);
    }
    if ($jsonData){
      $user= User::update($id,$jsonData);
      $this->apiResponse($user, 'ok', 200);
    }

    $this->apiResponse((object)[], 'You are not allowed to update this', 402);
  }

  #[NoReturn] private function deleteUser($id): void {
    $user = User::find($id);
    if (!$user) {
      $this->apiResponse((object)[], 'user not found', 404);
    }

    $publicId = $user['public_id'];
    if ($publicId) {
      $this->deleteImageFromCloudinary($publicId);
    }

    $relatedCarts = Cart::where('user_id', '=', $id)->count();
		$relatedOrders = Order::where('user_id', '=', $id)->count();
    if ($relatedCarts > 0 || $relatedOrders > 0) {
      User::update($id, [
        'room_id' => null,
        'image' => null,
        'public_id' => null,
        'deleted_at' => date('Y-m-d H:i:s'),
      ]);
    } else {
      User::delete($id);
    }
    $this->apiResponse((object)[], 'User deleted successfully', 200);
  }
}

new UserController();
