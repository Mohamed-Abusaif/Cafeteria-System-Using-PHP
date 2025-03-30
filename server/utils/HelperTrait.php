<?php

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
}
