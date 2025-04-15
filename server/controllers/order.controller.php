<?php

use JetBrains\PhpStorm\NoReturn;

require_once '../utils/HelperTrait.php';
require_once '../middlewares/validator.middleware.php';
require_once '../models/Order.php';
require_once '../models/User.php';
require_once '../models/Cart.php';
require_once '../models/CartProduct.php';
require_once '../models/Product.php';
require_once '../models/OrderProduct.php';

class OrderController {
  use HelperTrait;

  #[NoReturn] public function __construct() {
    $id = $this->getIdFromUrl();
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
      case 'GET':
        if ($id) $this->getOneOrder($id);
        else $this->getOrders();
      case 'POST':
        $this->createOrder();
      case 'PATCH':
        $this->updateOrder($id);
      case 'DELETE':
        $this->cancelOrder($id);
      default:
        $this->apiResponse(null, 'Method Not Allowed', 405);
    }
  }

  #[NoReturn] private function getOrders(): void {
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 10;
    if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
      $startDate = date('Y-m-d H:i:s', strtotime($_GET['start_date']));
      $endDate = date('Y-m-d H:i:s', strtotime($_GET['end_date']));

      if ($startDate && $endDate) {
	      Order::whereBetween('created_at', [$startDate, $endDate]);
      }
    }

    if (isset($_GET['user_id'])) {
      Order::where('user_id', '=', $_GET['user_id']);
    }

    if (isset($_GET['deleted_at'])) {
      Order::where('deleted_at', 'is', null);
    }

    if (isset($_GET['status'])) {
      $validStatuses = ['processing', 'delivered', 'done', 'canceled'];
      if (in_array($_GET['status'], $validStatuses)) {
        Order::where('status', '=', $_GET['status']);
      }
    }

    $orders = Order::sort('created_at', 'DESC')->paginate($page, $limit);
    foreach ($orders['data'] as &$order) {
      $user = User::find($order['user_id']);
      $room = Room::find($order['room_id']);
      $order['user'] = [
        "name" => $user['name']
      ];
      $order['room'] = [
        "name" => $room['name']
      ];
      $order['products'] = $this->getOrderProducts($order['id']);
    }

    $this->apiResponse($orders, 'ok', 200);
  }

  #[NoReturn] private function getOneOrder($id): void {
    $order = Order::find($id);
    if (!$order) {
      $this->apiResponse((object)[], 'Order not found', 404);
    }

    $order['products'] = $this->getOrderProducts($id);
    $this->apiResponse($order, 'ok', 200);
  }

  private function getOrderProducts($orderId): array {
    $orderProducts = OrderProduct::where('order_id', '=', $orderId)->get();

    $products = [];
    foreach ($orderProducts as $orderProduct) {
      $product = Product::find($orderProduct['product_id']);
      if ($product) {
        $products[] = [
          'id' => $orderProduct['id'],
          'product_id' => $orderProduct['product_id'],
          'product_name' => $product['name'],
          'quantity' => $orderProduct['quantity'],
          'price' => $orderProduct['price']
        ];
      }
    }

    return $products;
  }

  #[NoReturn] private function createOrder(): void {
    $jsonData = json_decode(file_get_contents("php://input"), true);
    $validator = Validator::make($jsonData, [
      'Admin_id' => 'nullable|numeric|exists:users',
      'user_id' => 'required|numeric|exists:users',
      'room_id' => 'required|numeric|exists:rooms',
      'notes' => 'nullable|string',
    ]);

    if ($validator->fails()) {
      $this->apiResponse((object)[], $validator->firstError(), 400);
    }
    
    if(is_null($jsonData['Admin_id'])){
      $cart = Cart::where('user_id', '=', $jsonData['user_id'])->first();
    }else{
      $cart = Cart::where('user_id', '=', $jsonData['Admin_id'])->first();
    }
    
    if (!$cart) {
      $this->apiResponse((object)[], 'Cart not found', 400);
    }

    $cartProducts = CartProduct::where('cart_id', '=', $cart['id'])->get();
    if (empty($cartProducts)) {
      $this->apiResponse((object)[], 'Cart is empty', 400);
    }

    $totalPrice = 0;
    $productItems = [];
    foreach ($cartProducts as $cartProduct) {
      $product = Product::find($cartProduct['product_id']);
      if ($product) {
        $itemPrice = $product['price'] * $cartProduct['quantity'];
        $totalPrice += $itemPrice;

        $productItems[] = [
          'product_id' => $product['id'],
          'quantity' => $cartProduct['quantity'],
          'price' => $product['price']
        ];
      }
    }

    $order = Order::create([
      'user_id' => $jsonData['user_id'],
      'room_id' => $jsonData['room_id'],
      'status' => 'processing',
      'total_price' => $totalPrice,
      'notes' => $jsonData['notes'] ?? null,
    ]);

    foreach ($productItems as $item) {
      OrderProduct::create([
        'order_id' => $order['id'],
        'product_id' => $item['product_id'],
        'quantity' => $item['quantity'],
        'price' => $item['price']
      ]);
    }

    // Clear the cart
    foreach ($cartProducts as $cartProduct) {
      CartProduct::delete($cartProduct['id']);
    }

    $order['products'] = $this->getOrderProducts($order['id']);
    $this->apiResponse($order, 'Order created successfully', 201);
  }

  #[NoReturn] private function updateOrder($id): void {
    $order = Order::find($id);
    if (!$order) {
      $this->apiResponse((object)[], 'Order not found', 404);
    }

    $jsonData = json_decode(file_get_contents("php://input"), true);

    // Enforce only status updates in this method
    if (!isset($jsonData['status'])) {
      $this->apiResponse((object)[], 'Only status updates are allowed', 400);
    }

    $validator = Validator::make($jsonData, [
      'status' => 'required|string|in:processing,delivered,done',
    ]);

    if ($validator->fails()) {
      $this->apiResponse((object)[], $validator->firstError(), 400);
    }

    $currentStatus = $order['status'];
    $newStatus = $jsonData['status'];

    // Define allowed status transitions
    $allowedTransitions = [
      'processing' => ['delivered'],
      'delivered' => ['done'],
      'done' => []
    ];

    if ($currentStatus === $newStatus) {
      $this->apiResponse((object)[], 'Order is already in ' . $newStatus . ' status', 400);
    } elseif (!in_array($newStatus, $allowedTransitions[$currentStatus])) {
      $this->apiResponse(
        (object)[],
        "Cannot change status from '$currentStatus' to '$newStatus'. Allowed transitions: " .
        implode(', ', $allowedTransitions[$currentStatus]),
        400
      );
    }

    $updatedOrder = Order::update($id, [
      'status' => $newStatus,
    ]);

    $updatedOrder['products'] = $this->getOrderProducts($id);
    $this->apiResponse($updatedOrder, "Order status updated from '$currentStatus' to '$newStatus'", 200);
  }

  #[NoReturn] private function cancelOrder($id): void {
    $order = Order::find($id);
    if (!$order) {
      $this->apiResponse((object)[], 'Order not found', 404);
    }

    if ($order['status'] !== 'processing') {
      $this->apiResponse((object)[], 'Only orders with processing status can be canceled', 400);
    }

    $updatedOrder = Order::update($id, [
      'status' => 'canceled',
      'deleted_at' => date('Y-m-d H:i:s')
    ]);

    $this->apiResponse($updatedOrder, 'Order canceled successfully', 200);
  }
}

new OrderController();