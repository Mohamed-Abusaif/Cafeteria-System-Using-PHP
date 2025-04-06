<?php 

require_once '../utils/HelperTrait.php';
require_once '../middlewares/validator.middleware.php';
require_once '../models/Cart.php';
require_once '../models/CartProduct.php';
class CartController {
    use HelperTrait;

    public function __construct()
    {
        $id = $this->getIdFromUrl();
        $method = $_SERVER['REQUEST_METHOD'];

        switch ($method) {
			
			case 'POST':
				$this->createCart();
			case 'DELETE':
				$this->removeProductFromCart($id);
			default:
				$this->apiResponse(null, 'Method Not Allowed', 405);
		}
    }

    private function createCart(){
        $jsonData = json_decode(file_get_contents("php://input"), true);
        
		$validator = Validator::make($jsonData, [
			'user_id' => 'required|numeric|exists:users',
			'product_id' => 'required|numeric|exists:products',
		]);
		if ($validator->fails()) {
			$this->apiResponse((object)[], $validator->firstError(), 404);
		}
        $cart =  Cart::where('user_id', '=',$jsonData['user_id'])->first();

        if ($cart){
            Cart::update($cart['id'], [
                'updated_at' =>  date('Y-m-d H:i:s')
            ]);
        }else {
           $cart = Cart::create([
                'user_id' => $jsonData['user_id'],
                'updated_at' =>  date('Y-m-d H:i:s')
            ]);
        }
     
        $cartProduct = CartProduct::where('cart_id', '=',$cart['id'])->where('product_id', '=',$jsonData['product_id'])->first();

        if ($cartProduct){
            CartProduct::update($cartProduct['id'], [
                'quantity' => $cartProduct['quantity'] + 1
            ]);
        }else {
            CartProduct::create([
                'cart_id' => $cart['id'],
                'product_id' => $jsonData['product_id'],
                'quantity' => 1
            ]);
        }

		$this->apiResponse($jsonData, 'ok', 201);
    }

    private function removeProductFromCart($id){
        $jsonData = json_decode(file_get_contents("php://input"), true);
        
		$validator = Validator::make($jsonData, [
			'product_id' => 'required|numeric',
		]);
		if ($validator->fails()) {
			$this->apiResponse((object)[], $validator->firstError(), 404);
		}
        $cart = Cart::find($id);
        if (!$cart) {
            $this->apiResponse((object)[], 'Cart not found', 404);
		}
        $cartProduct = CartProduct::where('cart_id', '=',$id)->where('product_id', '=',$jsonData['product_id'])->first();
        if ($cartProduct) {
            CartProduct::delete($cartProduct['id']);
            $this->apiResponse((object)[], 'ok', 200);

        }else {
            $this->apiResponse((object)[], 'Product not existed in the cart', 404);
        }
    }
}

new CartController ();