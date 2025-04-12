<?php
require_once "Model.php";
require_once "Room.php";

class User extends Model {
	public static string $table = "users";

	public static function find($id): false|array {
		$user = parent::find($id);
		unset($user['password'], $user['public_id'], $user['created_at'], $user['updated_at'], $user['deleted_at'], $user['reset_token']);
		$user['room'] = Room::find($user['room_id']) ?: null;
		return $user;
	}

	public static function paginate($page, $limit = 10): false|array {
		$paginated = parent::paginate($page, $limit);
		foreach ($paginated['data'] as &$user) {
			unset($user['password'], $user['public_id'], $user['created_at'], $user['updated_at'], $user['deleted_at'], $user['reset_token']);
			$user['room'] = Room::find($user['room_id']) ?: null;
		}
		return $paginated;
	}
}