<?php

use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
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
      case 'GET':
        $this->me();
        break;
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

  public function me(): void {
    $env = parse_ini_file(__DIR__ . '/../../.env');
    $secretKey = $env['JWT_SECRET'];

    if (!isset($_COOKIE['token'])) {
      $this->apiResponse((object)[], 'Token not found in cookies', 401);
    }
    $token = $_COOKIE['token'];

    try {
      $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
      $user = User::find($decoded->id);
      $this->apiResponse($user, 'OK', 200);
    } catch (ExpiredException $e) {
      $this->apiResponse((object)[], 'Token expired: ' . $e->getMessage(), 401);
    } catch (SignatureInvalidException $e) {
      $this->apiResponse((object)[], 'Invalid signature: ' . $e->getMessage(), 401);
    } catch (Exception $e) {
      $this->apiResponse((object)[], 'Invalid token: ' . $e->getMessage(), 400);
    }
  }
}

new Login();
