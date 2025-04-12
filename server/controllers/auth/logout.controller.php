<?php

use JetBrains\PhpStorm\NoReturn;

require_once __DIR__ . '/../../utils/HelperTrait.php';

class Logout {
  use HelperTrait;

  #[NoReturn] public function __construct() {
    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
      case 'POST':
        $this->logout();
        break;
      default:
        $this->apiResponse((object)[], 'Method Not Allowed', 405);
    }
  }

  #[NoReturn] private function logout(): void {
    // Check if token cookie exists
    if (isset($_COOKIE['token'])) {
      // Clear the token cookie by setting expiration in the past
      setcookie("token", "", time() - 3600, "/", "", false, true);
    }
    
    // Return success response
    $this->apiResponse((object)[], 'Logged out successfully', 200);
  }
}

new Logout(); 