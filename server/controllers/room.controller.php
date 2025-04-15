<?php

use JetBrains\PhpStorm\NoReturn;

require_once '../models/Room.php';
require_once '../models/Order.php';
require_once '../models/User.php';
require_once '../utils/HelperTrait.php';
require_once '../middlewares/validator.middleware.php';

class RoomController {
	use HelperTrait;

	#[NoReturn] public function __construct() {
		$id = $this->getIdFromUrl();
		$method = $_SERVER['REQUEST_METHOD'];
		switch ($method) {
			case 'GET':
				if ($id) $this->getOneRoom($id);
				else $this->getRooms();
			case 'POST':
				$this->createRoom();
			case 'PATCH':
				$this->updateRoom($id);
			case 'DELETE':
				$this->deleteRoom($id);
			default:
				$this->apiResponse(null, 'Method Not Allowed', 405);
		}
	}

	#[NoReturn] private function getRooms(): void {
		$loggedInUser = $this->getLoggedInUser();
		if (!$loggedInUser) {
			$this->apiResponse((object)[], 'Unauthorized', 401);
		}

		$rooms = Room::all();
		$this->apiResponse($rooms, 'ok', 200);
	}

	#[NoReturn] private function getOneRoom($id): void {
		$room = Room::find($id);
		if ($room) {
			$this->apiResponse($room, 'ok', 200);
		} else {
			$this->apiResponse((object)[], 'Room not found', 404);
		}
	}

	#[NoReturn] private function createRoom(): void {
		$loggedInUser = $this->getLoggedInUser();
		if ($loggedInUser['role'] !== 'Admin') {
			$this->apiResponse((object)[], 'Unauthorized', 401);
		}

		$jsonData = json_decode(file_get_contents("php://input"), true);
		$validator = Validator::make($jsonData, [
			'name' => 'required|string|unique:rooms',
			'description' => 'nullable|string',
		]);
		if ($validator->fails()) {
			$this->apiResponse((object)[], $validator->firstError(), 404);
		}

		$room = Room::create([
			'name' => $jsonData['name'],
			'description' => $jsonData['description'],
		]);
		$this->apiResponse($room, 'ok', 201);
	}

	#[NoReturn] private function updateRoom($id): void {
		$loggedInUser = $this->getLoggedInUser();
		if ($loggedInUser['role'] !== 'Admin') {
			$this->apiResponse((object)[], 'Unauthorized', 401);
		}

		$jsonData = json_decode(file_get_contents("php://input"), true);
		$validator = Validator::make($jsonData, [
			'name' => 'nullable|string|unique:rooms,name,' . $id,
			'description' => 'nullable|string',
		]);
		if ($validator->fails()) {
			$this->apiResponse((object)[], $validator->firstError(), 404);
		}

		$room = Room::update($id, $jsonData);
		$this->apiResponse($room, 'ok', 200);
	}

	#[NoReturn] private function deleteRoom($id): void {
		$loggedInUser = $this->getLoggedInUser();
		if ($loggedInUser['role'] !== 'Admin') {
			$this->apiResponse((object)[], 'Unauthorized', 401);
		}

		$relatedOrders = Order::where('room_id', '=', $id)->count();
		$relatedUsers = User::where('room_id', '=', $id)->count();
		if ($relatedOrders > 0 || $relatedUsers > 0) {
			$this->apiResponse((object)[], 'Room cannot be deleted because it has related orders or users', 400);
		}
		$room = Room::delete($id);
		$this->apiResponse($room, 'ok', 200);
	}
}

new RoomController();
