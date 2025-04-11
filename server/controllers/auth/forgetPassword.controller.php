<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../../vendor/autoload.php';
require(__DIR__ . '/../../models/User.php');
require(__DIR__.'/../../utils/HelperTrait.php');
require(__DIR__.'/../../middlewares/validator.middleware.php');


class ForgetPassword {

  use HelperTrait;

  #[NoReturn] public function __construct() {
    $method = $_SERVER['REQUEST_METHOD'];
    switch ($method) {
      case 'PATCH':
        $this->resetPassword();
      case 'POST':
        $this->forgetPassword();
      default:
        $this->apiResponse((object)[], 'Method Not Allowed', 405);
    }
  }

  private function forgetPassword(): void {
    $jsonData = json_decode(file_get_contents("php://input"), true);
    $validator = Validator::make($jsonData, [
      'email' => 'required|email',
    ]);
    if ($validator->fails()) {
      $this->apiResponse((object)[], $validator->firstError(), 404);
    }

    $email = $jsonData["email"];
    $user = User::where("email", "=", $email)->first();
    if (!$user) {
      $this->apiResponse((object)[], 'email or password not valid', 404);
    }

    $token = bin2hex(random_bytes(50));
    User::update($user['id'], ['reset_token' => $token]);
    $mail = new PHPMailer(true);
    try {
      $mail->isSMTP();
      $mail->Host = 'smtp.gmail.com';
      $mail->SMTPAuth = true;
      $mail->Username = 'handletlaravelemail@gmail.com';
      $mail->Password = 'nqrilnmlknoakffd';
      $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
      $mail->Port = 587;

      $mail->setFrom('cafeteria@example.com', 'Cafeteria');
//      $mail->addAddress($email);
      $mail->addAddress('nadaemam.me@gmail.com');
      $resetLink = "http://yourdomain.com/reset_password.php?token=$token";
      $mail->isHTML(true);
      $mail->Subject = 'Password Reset Request';
      $mail->Body = "Hello,<br><br>To reset your password, please click the following link: <a href='$resetLink'>$resetLink</a><br><br>If you did not request a password reset, please ignore this email.";

      $mail->send();
      $this->apiResponse((object)[], 'Password reset request sent to your email.', 200);
    } catch (Exception $e) {
      $this->apiResponse((object)[], $mail->ErrorInfo, 500);
    }
  }
  private function resetPassword(): void {
    $jsonData = json_decode(file_get_contents("php://input"), true);
    if (!$jsonData['reset_token']) {
      $this->apiResponse((object)[], "Token is missing.", 404);
    }
    $token = $jsonData['reset_token'];
   $user=User::where("reset_token", "=", $token)->first();
    if (!$user) {
      $this->apiResponse((object)[], 'Invalid or expired token.', 404);
    }
    $validator = Validator::make($jsonData, [
      'password' => 'required|string|min:8',
      'confirm_password' => 'required|string|min:8',
    ]);
    if ($validator->fails()) {
      $this->apiResponse((object)[], $validator->firstError(), 404);
    }
    if ($jsonData['password'] !== $jsonData['confirm_password']) {
      $this->apiResponse((object)[], 'Password and confirm password do not match', 404);
    }
    $hashedPassword = password_hash($jsonData['password'], PASSWORD_BCRYPT);
    $user = User::update($user['id'], ['password' => $hashedPassword, 'reset_token' => null]);
    $this->apiResponse($user, 'ok', 200);

  }
}
new ForgetPassword();

