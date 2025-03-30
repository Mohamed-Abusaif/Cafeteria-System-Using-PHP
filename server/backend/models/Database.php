<?php

class Database {
	private PDO $con;
	private static string $db_host;
	private static string $db_port;
	private static string $db_name;
	private static string $db_user;
	private static string $db_pass;
	private static ?Database $instance = null;

	private function __construct() {
		$env = parse_ini_file(__DIR__ . '/../.env');
		self::$db_host = $env['DB_HOST'];
		self::$db_port = $env['DB_PORT'];
		self::$db_name = $env['DB_DATABASE'];
		self::$db_user = $env['DB_USERNAME'];
		self::$db_pass = $env['DB_PASSWORD'];

		try {
			$dsn = "mysql:host=" . self::$db_host . ";port=" . self::$db_port . ";dbname=" . self::$db_name . ";charset=utf8mb4";
			$options = [
				PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES UTF8",
				PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
			];
			$this->con = new PDO($dsn, self::$db_user, self::$db_pass, $options);
		} catch (PDOException $e) {
			die("Connection failed: " . $e->getMessage());
		}
	}

	public static function getInstance(): Database {
		if (self::$instance === null) {
			self::$instance = new Database();
		}
		return self::$instance;
	}

	public function getConnection(): PDO {
		return $this->con;
	}
}
