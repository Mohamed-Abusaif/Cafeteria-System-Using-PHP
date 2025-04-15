<?php

use JetBrains\PhpStorm\NoReturn;

require_once '../../utils/HelperTrait.php';
require_once '../../models/User.php';
require_once '../../middlewares/validator.middleware.php';

class ChangePassword {
	use HelperTrait;

	#[NoReturn] public function __construct() {
		$id = $this->getIdFromUrl();
		$method = $_SERVER['REQUEST_METHOD'];
		switch ($method) {
			case 'PATCH':
				$this->changePassword($id);
			default:
				$this->apiResponse((object)[], 'Method Not Allowed', 405);
		}
	}

	#[NoReturn] private function changePassword($id): void {
    $loggedInUser = $this->getLoggedInUser();
    if($loggedInUser['id'] !== $id ) {
      $this->apiResponse((object)[], 'Unauthorized', 401);
    }
    $jsonData = json_decode(file_get_contents("php://input"), true);
		$validator = Validator::make($jsonData, [
			'current_password' => 'required|string|min:8',
			'new_password' => 'required|string|min:8',
			'confirm_password' => 'required|string|min:8',
		]);
		if ($validator->fails()) {
			$this->apiResponse((object)[], $validator->firstError(), 404);
		}
		if ($jsonData['new_password'] !== $jsonData['confirm_password']) {
			$this->apiResponse((object)[], 'Password and confirm password do not match', 404);
		}

		$user = User::find($id);
		if (!$user) {
			$this->apiResponse((object)[], 'User not found', 404);
		}
		if (!password_verify($jsonData['current_password'], $user['password'])) {
			$this->apiResponse((object)[], 'Current password is not correct', 404);
		}
		$hashedPassword = password_hash($jsonData['new_password'], PASSWORD_BCRYPT);
		$user = User::update($id, ['password' => $hashedPassword]);
		$this->apiResponse($user, 'ok', 200);
	}
}

new ChangePassword();
