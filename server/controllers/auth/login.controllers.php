<?php

use JetBrains\PhpStorm\NoReturn;

require_once '../../utils/HelperTrait.php';
require_once '../../models/User.php';
require_once '../../utils/JwtHelper.php';

class Login {
  use HelperTrait;

  #[NoReturn] public function __construct() {
    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
      case 'POST':
        $jsonData = json_decode(file_get_contents("php://input"), true);

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
      $token=  JwtHelper::generateToken($user);
        $this->apiResponse($token, "OK", 200);


//        case 'GET':
//          $hashed_password = password_hash("123456", PASSWORD_DEFAULT);
//          $this->apiResponse($hashed_password, 'account has deleted', 404);


      default:
        $this->apiResponse((object)[], 'Method Not Allowed', 405);

    }

  }
}

new Login();