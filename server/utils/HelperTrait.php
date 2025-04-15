<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept");
header("Access-Control-Allow-Credentials: true");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

use JetBrains\PhpStorm\NoReturn;
use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../models/User.php';

trait HelperTrait {
	#[NoReturn] public function apiResponse($data, $message, $statusCode): void {
		http_response_code($statusCode);
		header("Content-Type: application/json");
		echo json_encode([
			'data' => $data,
			'message' => $message,
			'statusCode' => $statusCode
		]);
		exit;
	}

	public function getIdFromUrl(): ?int {
		$url = $_SERVER['REQUEST_URI'];
		$urlParts = explode('/', $url);
		return (int)end($urlParts);
	}

	public function deleteImageFromCloudinary($publicId): void {
		$env = parse_ini_file(__DIR__ . '/../.env');
		$config = Configuration::instance();
		$config->cloud->cloudName = $env['CLOUD_NAME'];
		$config->cloud->apiKey =  $env['API_KEY'];
		$config->cloud->apiSecret = $env['API_SECRET'];
		$config->url->secure = true;
		$cloudinary = new Cloudinary($config);
		$cloudinary->uploadApi()->destroy($publicId, options: ['invalidate' => true]);
	}
  public function getLoggedInUser(): array|null|false {
    $env = parse_ini_file(__DIR__ . '/../.env');
    $secretKey = $env['JWT_SECRET'];
    if (!isset($_COOKIE['token'])) {
      $this->apiResponse((object)[], 'Token not found in cookies', 401);
    }
    $token = $_COOKIE['token'];

    try {
      $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
      $user = User::find($decoded->id);
      unset($user['password']);
      return $user;
    } catch (ExpiredException $e) {
      $this->apiResponse((object)[], 'Token expired: ' . $e->getMessage(), 401);
    } catch (SignatureInvalidException $e) {
      $this->apiResponse((object)[], 'Invalid signature: ' . $e->getMessage(), 401);
    } catch (Exception $e) {
      $this->apiResponse((object)[], 'Invalid token: ' . $e->getMessage(), 400);
    }
  }
}
