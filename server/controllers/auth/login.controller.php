<?php

use JetBrains\PhpStorm\NoReturn;

require_once '../../utils/HelperTrait.php';
require_once '../../models/User.php';
require_once '../../utils/JwtHelper.php';
require_once '../../middlewares/validator.middleware.php';


class Login {
  use HelperTrait;

  #[NoReturn] public function __construct() {
    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
      case 'POST':
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
        $this->apiResponse($token, "OK", 200);
      default:
        $this->apiResponse((object)[], 'Method Not Allowed', 405);
    }
  }
}

new Login();