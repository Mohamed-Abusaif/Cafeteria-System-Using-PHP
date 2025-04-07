<?php

use JetBrains\PhpStorm\NoReturn;

require_once '../models/User.php';
require_once '../models/Product.php';
require_once '../models/Category.php';
require_once '../models/Order.php';
require_once '../models/Room.php';
require_once '../utils/HelperTrait.php';

class DashboardController{
	use HelperTrait;
	#[NoReturn] public function __construct() {
		$method = $_SERVER['REQUEST_METHOD'];
		switch ($method) {
			case 'GET':
				$this->getDashboard();
			default:
				$this->apiResponse((object)[], 'Method Not Allowed', 405);
		}
	}

	#[NoReturn] public function getDashboard(): void {
		$totalUsers = User::where('deleted_at', 'is', null)->count();
		$totalRooms = Room::count();
		$totalProducts = Product::where('deleted_at', 'is', null)->count();
		$totalCategories = Category::count();
		$totalOrders = Order::where('deleted_at', 'is', null)->count();
		$this->apiResponse([
			'users' => $totalUsers,
			'categories' => $totalCategories,
			'products' => $totalProducts,
			'orders' => $totalOrders,
			'rooms' => $totalRooms,
		], 'ok', 200);
	}
}

new DashboardController();
