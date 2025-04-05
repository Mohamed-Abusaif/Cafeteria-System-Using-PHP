<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Accept"); 
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

use JetBrains\PhpStorm\NoReturn;

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
}
