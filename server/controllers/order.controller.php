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
require_once '../models/Room.php';
require_once '../models/Database.php';

class OrderController {
  use HelperTrait;

  #[NoReturn] public function __construct() {
    $id = $this->getIdFromUrl();
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
      case 'GET':
        if ($id) $this->getOneOrder($id);
        else $this->getOrdersWithJoin();
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

  #[NoReturn] private function getOrdersWithJoin(): void {
    $loggedInUser = $this->getLoggedInUser();
	  if (!$loggedInUser) {
		  $this->apiResponse((object)[], 'Unauthorized', 401);
	  }
    
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 10;
    
    Order::init();
    $con = Database::getInstance()->getConnection();
    $query = "SELECT o.*, u.name as user_name, r.name as room_name 
              FROM orders o
              JOIN users u ON o.user_id = u.id
              JOIN rooms r ON o.room_id = r.id
              WHERE 1=1";
    
    $params = [];
    if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
      $startDate = date('Y-m-d H:i:s', strtotime($_GET['start_date']));
      $endDate = date('Y-m-d H:i:s', strtotime($_GET['end_date']));
      
      if ($startDate && $endDate) {
        $query .= " AND o.created_at BETWEEN :start_date AND :end_date";
        $params['start_date'] = $startDate;
        $params['end_date'] = $endDate;
      }
    }
    
    if (isset($_GET['user_id'])) {
      $query .= " AND o.user_id = :user_id";
      $params['user_id'] = $_GET['user_id'];
    }
    if (isset($_GET['status'])) {
      $validStatuses = ['processing', 'delivered', 'done', 'canceled'];
      if (in_array($_GET['status'], $validStatuses)) {
        $query .= " AND o.status = :status";
        $params['status'] = $_GET['status'];
      }
    }
    
    $countQuery = str_replace("SELECT o.*, u.name as user_name, r.name as room_name", "SELECT COUNT(*) as total", $query);
    $countStmt = $con->prepare($countQuery);
    foreach ($params as $key => $val) {
      $countStmt->bindValue(":$key", $val);
    }
    $countStmt->execute();
    $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($total / $limit);
    
    $query .= " ORDER BY o.created_at DESC";
    $query .= " LIMIT :limit OFFSET :offset";
    $offset = ($page - 1) * $limit;
    $stmt = $con->prepare($query);
    foreach ($params as $key => $val) {
      $stmt->bindValue(":$key", $val);
    }
    $stmt->bindValue(":limit", (int)$limit, PDO::PARAM_INT);
    $stmt->bindValue(":offset", (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $ordersData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $orderIds = array_column($ordersData, 'id');
    $products = [];
    
    if (!empty($orderIds)) {
      $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
      $productQuery = "SELECT op.*, p.name as product_name, op.order_id 
                      FROM order_products op
                      JOIN products p ON op.product_id = p.id
                      WHERE op.order_id IN ($placeholders)";
      
      $productStmt = $con->prepare($productQuery);
      $productStmt->execute($orderIds);
      $productResults = $productStmt->fetchAll(PDO::FETCH_ASSOC);
      foreach ($productResults as $product) {
        $orderId = $product['order_id'];
        if (!isset($products[$orderId])) {
          $products[$orderId] = [];
        }
        $products[$orderId][] = [
          'id' => $product['id'],
          'product_id' => $product['product_id'],
          'product_name' => $product['product_name'],
          'quantity' => $product['quantity'],
          'price' => $product['price']
        ];
      }
    }
    
    $orders = [];
    foreach ($ordersData as $order) {
      $orderId = $order['id'];
      $orders[] = [
        'id' => $orderId,
        'user_id' => $order['user_id'],
        'room_id' => $order['room_id'],
        'status' => $order['status'],
        'total_price' => $order['total_price'],
        'notes' => $order['notes'],
        'created_at' => $order['created_at'],
        'updated_at' => $order['updated_at'],
        'user' => [
          'name' => $order['user_name']
        ],
        'room' => [
          'name' => $order['room_name']
        ],
        'products' => $products[$orderId] ?? []
      ];
    }
    
    $result = [
      'total' => (int)$total,
      'total_pages' => (int)$totalPages,
      'current_page' => (int)$page,
      'per_page' => (int)$limit,
      'data' => $orders,
    ];
    $this->apiResponse($result, 'ok', 200);
  }

  #[NoReturn] private function getOneOrder($id): void {
    $order = Order::find($id);
    if (!$order) {
      $this->apiResponse((object)[], 'Order not found', 404);
    }

    $order['products'] = $this->getOrderProducts($id);
    $this->apiResponse($order, 'ok', 200);
  }

  #[NoReturn] private function createOrder(): void {
	  $loggedInUser = $this->getLoggedInUser();
	  if (!$loggedInUser) {
		  $this->apiResponse((object)[], 'Unauthorized', 401);
	  }

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

    $user = User::find($jsonData['user_id']);
    if($user['role'] !== 'User'){
      $this->apiResponse((object)[], 'User not found', 400);
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
		$this->apiResponse($order, 'Order created successfully', 201);
  }

  #[NoReturn] private function updateOrder($id): void {
	  $loggedInUser = $this->getLoggedInUser();
	  if (!$loggedInUser) {
		  $this->apiResponse((object)[], 'Unauthorized', 401);
	  }

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
		$this->apiResponse($updatedOrder, "Order status updated from '$currentStatus' to '$newStatus'", 200);
  }

  #[NoReturn] private function cancelOrder($id): void {
	  $loggedInUser = $this->getLoggedInUser();
	  if (!$loggedInUser) {
		  $this->apiResponse((object)[], 'Unauthorized', 401);
	  }

    $order = Order::find($id);
    if (!$order) {
      $this->apiResponse((object)[], 'Order not found', 404);
    }

    if ($order['status'] !== 'processing') {
      $this->apiResponse((object)[], 'Only orders with processing status can be canceled', 400);
    }

    $updatedOrder = Order::update($id, [
      'status' => 'canceled',
    ]);
    $this->apiResponse($updatedOrder, 'Order canceled successfully', 200);
  }

  /****************** ****************** Helper Functions ****************** ******************/
  private function getOrderProducts($orderId): array {
    $products = [];
    $orderProducts = OrderProduct::where('order_id', '=', $orderId)->get();
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
}

new OrderController();
