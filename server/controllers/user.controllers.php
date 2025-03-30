<?php

use JetBrains\PhpStorm\NoReturn;

require_once '../models/User.php';
require_once '../utils/HelperTrait.php';

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
		$jsonData = json_decode(file_get_contents("php://input"), true);
		$user = User::create([
			'name' => $jsonData['name'],
			'email' => $jsonData['email'],
			'password' => $jsonData['password'],
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
