<?php

use JetBrains\PhpStorm\NoReturn;

require(__DIR__ . '/../../models/User.php');
require(__DIR__.'/../../utils/HelperTrait.php');
require(__DIR__.'/../../middlewares/validator.middleware.php');
require(__DIR__ . '/../../utils/JwtHelper.php');

class Login {
  use HelperTrait;

  #[NoReturn] public function __construct() {
    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
      case 'POST':
        $this->login();
      case 'GET':
        $this->me();
			default:
        $this->apiResponse((object)[], 'Method Not Allowed', 405);
    }
  }

  #[NoReturn] private function login(): void {
    $jsonData = json_decode(file_get_contents("php://input"), true);
    $validator = Validator::make($jsonData, [
      'email' => 'required|email',
      'password' => 'required|string|min:8',
    ]);
    if ($validator->fails()) {
      $this->apiResponse((object)[], $validator->firstError(), 404);
    }
    $user = User::where("email", "=", $jsonData["email"])->first();
    if (!$user) {
      $this->apiResponse((object)[], 'email or password not valid', 404);
    }
    if ($user["deleted_at"]) {
      $this->apiResponse((object)[], 'account has deleted', 404);
    }
    if (!password_verify($jsonData["password"], $user["password"])) {
      $this->apiResponse((object)[], 'email or password not valid', 404);
    }
    $token = JwtHelper::generateToken($user);
    setcookie("token", $token, time() + 3600, "/", "", false, true);
    $this->apiResponse((object)[], "OK", 200);
  }

  #[NoReturn] public function me(): void {
	  $loggedInUser = $this->getLoggedInUser();
	  $this->apiResponse($loggedInUser, 'OK', 200);
  }
}

new Login();
