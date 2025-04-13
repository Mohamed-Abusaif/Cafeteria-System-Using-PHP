<?php

use JetBrains\PhpStorm\NoReturn;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Random\RandomException;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once(__DIR__ . '/../../models/User.php');
require_once(__DIR__.'/../../utils/HelperTrait.php');
require_once(__DIR__.'/../../middlewares/validator.middleware.php');

class ForgetPassword {
  use HelperTrait;

  #[NoReturn] public function __construct() {
    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
      case 'POST':
        $this->forgetPassword();
      case 'PATCH':
        $this->resetPassword();
      default:
        $this->apiResponse((object)[], 'Method Not Allowed', 405);
    }
  }

  #[NoReturn] private function forgetPassword(): void {
    $jsonData = json_decode(file_get_contents("php://input"), true);
    $validator = Validator::make($jsonData, [
      'email' => 'required|email',
    ]);
    if ($validator->fails()) {
      $this->apiResponse((object)[], $validator->firstError(), 404);
    }

    $email = $jsonData["email"];
    $user = User::where("email", "=", $email)->first();
    if (!$user || $user["deleted_at"]) {
      $this->apiResponse((object)[], 'email not found', 404);
    }

    try {
      $token = bin2hex(random_bytes(50));
    } catch (RandomException $e) {
      $this->apiResponse((object)[], $e->getMessage(), 500);
    }
    User::update($user['id'], ['reset_token' => $token]);
    $mail = new PHPMailer(true);
    $env = parse_ini_file(__DIR__ . '/../../.env');
    try {
      $mail->isSMTP();
      $mail->Host = 'smtp.gmail.com';
      $mail->SMTPAuth = true;
      $mail->Username = $env['EMAIL_USER'];
      $mail->Password = $env['EMAIL_PASS'];
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mail->Port = 587;

      $name = $user['name'];
      $mail->setFrom('cafeteria@example.com', 'Cafeteria');
      $mail->addAddress($email, $name);
      $resetLink = $env['APP_VUE_URL'] . "/reset-password?token=$token";
      $mail->isHTML(true);
      $mail->Subject = 'Password Reset Request';
      $mail->Body = "Hello, $name <br><br>To reset your password, please click the following link: <a href='$resetLink'>$resetLink</a><br><br>If you did not request a password reset, please ignore this email.";

      $mail->send();
      $this->apiResponse((object)[], 'Password reset request sent to your email.', 200);
    } catch (Exception $e) {
      $this->apiResponse((object)[], $e->getMessage(), 500);
    }
  }

  #[NoReturn] private function resetPassword(): void {
    $jsonData = json_decode(file_get_contents("php://input"), true);
    $validator = Validator::make($jsonData, [
      'reset_token' => 'required|string',
      'password' => 'required|string|min:8',
      'confirm_password' => 'required|string|min:8',
    ]);
    if ($validator->fails()) {
      $this->apiResponse((object)[], $validator->firstError(), 404);
    }
    if ($jsonData['password'] !== $jsonData['confirm_password']) {
      $this->apiResponse((object)[], 'Password and confirm password do not match', 404);
    }

    $user = User::where("reset_token", "=", $jsonData['reset_token'])->first();
    if (!$user || $user["deleted_at"]) {
      $this->apiResponse((object)[], 'Invalid token.', 404);
    }

    $hashedPassword = password_hash($jsonData['password'], PASSWORD_BCRYPT);
    $user = User::update($user['id'], ['password' => $hashedPassword, 'reset_token' => null]);
    $this->apiResponse($user, 'Password reset successfully', 200);
  }
}

new ForgetPassword();