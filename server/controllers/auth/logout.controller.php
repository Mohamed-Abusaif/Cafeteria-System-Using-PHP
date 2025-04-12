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
      default:
        $this->apiResponse((object)[], 'Method Not Allowed', 405);
    }
  }

  #[NoReturn] private function logout(): void {
    if (isset($_COOKIE['token'])) {
      setcookie("token", "", time() - 3600, "/", "", false, true);
    }
    $this->apiResponse((object)[], 'Logged out successfully', 200);
  }
}

new Logout(); 
