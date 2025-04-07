<?php

use JetBrains\PhpStorm\NoReturn;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

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
        break;
      case 'GET':
        $this->me();
        break;
      default:
        $this->apiResponse((object)[], 'Method Not Allowed', 405);
    }
  }

  #[NoReturn] private function login() {
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
    setcookie("token", $token, time() + 3600, "/", "", true, true);
    $this->apiResponse((object)[], "OK", 200);
  }

  public function me() {
    $env = parse_ini_file(__DIR__ . '/../../.env');
    $secretKey = $env['JWT_SECRET'];

    if (!isset($_COOKIE['token'])) {
      $this->apiResponse((object)[], 'Token not found in cookies', 401);
    }
    $token = $_COOKIE['token'];
    try {
      $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
      $user = (array) $decoded;

      unset($user['password']);
      $user = (object) $user;

      $this->apiResponse($user, 'OK', 200);


    } catch (\Firebase\JWT\ExpiredException $e) {
      $this->apiResponse($user, 'Token expired: ', 401);

    } catch (\Firebase\JWT\SignatureInvalidException $e) {
      $this->apiResponse($user, 'Invalid signature: ' , 401);

    } catch (Exception $e) {
      $this->apiResponse($user, 'Invalid token: ' , 400);

    }
  }
}

new Login();