<?php

require_once '../utils/HelperTrait.php';
require_once '../middlewares/validator.middleware.php';
require_once '../models/Order.php';
require_once '../models/User.php';
require_once '../models/Cart.php';
require_once '../models/CartProduct.php';
require_once '../models/Product.php';

class OrderController {
    use HelperTrait;

    public function __construct()
    {
        $id = $this->getIdFromUrl();
        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
            case 'GET':
                if ($id) {
                    $this->getOneOrder($id);
                } else {
                    $this->getOrders();
                }
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

    private function getOrders() {
        Order::init();
        $orders = [];

        if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
            $startDate = date('Y-m-d H:i:s', strtotime($_GET['start_date']));
            $endDate = date('Y-m-d H:i:s', strtotime($_GET['end_date']));
            
            if ($startDate && $endDate) {
                Order::where('created_at', '>=', $startDate);
                Order::where('created_at', '<=', $endDate);
            }
        }

        if (isset($_GET['user_id'])) {
            Order::where('user_id', '=', $_GET['user_id']);
        }

        if (isset($_GET['status'])) {
            $validStatuses = ['processing', 'out for delivery', 'done'];
            if (in_array($_GET['status'], $validStatuses)) {
                Order::where('status', '=', $_GET['status']);
            }
        }

        $orders = Order::get();

        $this->apiResponse($orders, 'ok', 200);
    }

    private function getOneOrder($id) {
        $order = Order::find($id);
        if (!$order) {
            $this->apiResponse((object)[], 'Order not found', 404);
        }
        $this->apiResponse($order, 'ok', 200);
    }

    private function createOrder() {
        $jsonData = json_decode(file_get_contents("php://input"), true);
        
        $validator = Validator::make($jsonData, [
            'user_id' => 'required|numeric|exists:users',
            'room_id' => 'required|numeric|exists:rooms',
            'notes' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            $this->apiResponse((object)[], $validator->firstError(), 400);
        }

        $cart = Cart::where('user_id', '=', $jsonData['user_id'])->first();
        
        if (!$cart) {
            $this->apiResponse((object)[], 'Cart not found', 400);
        }

        $cartProducts = CartProduct::where('cart_id', '=', $cart['id'])->get();
        
        if (empty($cartProducts)) {
            $this->apiResponse((object)[], 'Cart is empty', 400);
        }

        $totalPrice = 0;
        foreach ($cartProducts as $cartProduct) {
            $product = Product::find($cartProduct['product_id']);
            if ($product) {
                $totalPrice += $product['price'] * $cartProduct['quantity'];
            }
        }

        $order = Order::create([
            'user_id' => $jsonData['user_id'],
            'room_id' => $jsonData['room_id'],
            'status' => 'processing',
            'total_price' => $totalPrice,
            'notes' => $jsonData['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        $cartProducts = CartProduct::where('cart_id', '=', $cart['id'])->get();
        foreach ($cartProducts as $cartProduct) {
            CartProduct::delete($cartProduct['id']);
        }

        $this->apiResponse($order, 'Order created successfully', 201);
    }

    private function updateOrder($id) {
        $order = Order::find($id);
        if (!$order) {
            $this->apiResponse((object)[], 'Order not found', 404);
        }

        $jsonData = json_decode(file_get_contents("php://input"), true);
        
        if (isset($jsonData['status'])) {
            $validator = Validator::make($jsonData, [
                'status' => 'required|string|in:processing,out for delivery,done',
            ]);
            
            if ($validator->fails()) {
                $this->apiResponse((object)[], $validator->firstError(), 400);
            }

            $updatedOrder = Order::update($id, [
                'status' => $jsonData['status'],
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            $this->apiResponse($updatedOrder, 'Order status updated successfully', 200);
        } else {
            $validator = Validator::make($jsonData, [
                'notes' => 'nullable|string',
                'room_id' => 'nullable|numeric|exists:rooms',
            ]);
            
            if ($validator->fails()) {
                $this->apiResponse((object)[], $validator->firstError(), 400);
            }

            $updateData = [];
            if (isset($jsonData['notes'])) $updateData['notes'] = $jsonData['notes'];
            if (isset($jsonData['room_id'])) $updateData['room_id'] = $jsonData['room_id'];
            $updateData['updated_at'] = date('Y-m-d H:i:s');

            $updatedOrder = Order::update($id, $updateData);

            $this->apiResponse($updatedOrder, 'Order updated successfully', 200);
        }
    }

    private function cancelOrder($id) {
        $order = Order::find($id);
        if (!$order) {
            $this->apiResponse((object)[], 'Order not found', 404);
        }

        if ($order['status'] !== 'processing') {
            $this->apiResponse((object)[], 'Only orders with processing status can be canceled', 400);
        }

        $updatedOrder = Order::update($id, [
            'deleted_at' => date('Y-m-d H:i:s')
        ]);

        $this->apiResponse($updatedOrder, 'Order canceled successfully', 200);
    }
}

new OrderController();