<?php

require_once "Model.php";
require_once "Category.php";

class Product extends Model {
  public static string $table = "products";
	public static function find($id) {
		$product = parent::find($id);
		if(!$product) return null;
		$product['category'] = Category::find($product['category_id']);
		return $product;
	}

	public static function paginate($page, $limit = 10): false|array {
		$paginated = parent::paginate($page, $limit);
		foreach ($paginated['data'] as &$product) {
			$product['category'] = Category::find($product['category_id']);
		}
		return $paginated;
	}
}
