<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;

class JwtHelper {
  private static string $secretKey = '';
  private static string $algorithm = "HS256";

  public static function loadEnv(): void {
    $env = parse_ini_file(__DIR__ . '/../.env');
    self::$secretKey = $env['JWT_SECRET'];
  }
  public static function generateToken(array $payload, int $expiry = 3600): string {
    if (empty(self::$secretKey)) {
      self::loadEnv();
    }
    $issuedAt = time();
    $payload['iat'] = $issuedAt;
    $payload['exp'] = $issuedAt + $expiry;

    return JWT::encode($payload, self::$secretKey, self::$algorithm);
  }
}