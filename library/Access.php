<?php
namespace packages\userpanel_oauth;

use packages\base\db\dbObject;
use packages\userpanel\User;

class Access extends dbObject {

	const ACTIVE = 1;
	const DEACTIVE = 2;

	protected $dbTable = "userpanel_oauth_accesses";
	protected $primaryKey = "id";
	protected $dbFields = array(
		"user_id" => array("type" => "int", "required" => true),
		"app_id" => array("type" => "int", "required" => true),
		"code" => array("type" => "text", "required" => true, "unique" => true),
		"token" => array("type" => "text", "required" => true, "unique" => true),
		"create_at" => array("type" => "int", "required" => true),
		"lastip" => array("type" => "text"),
		"lastuse_at" => array("type" => "int"),
		"expire_token_at" => array("type" => "int"),
		"status" => array("type" => "int", "required" => true),
	);

	protected $relations = array(
		"user" => array("hasOne", User::class, "user_id"),
		"app" => array("hasOne", App::class, "app_id")
	);

	/**
	 * Converts object data to an associative array.
	 *
	 * @return array Converted data
	 */
	public function toArray ($recursive = null) {
		$result = parent::toArray($recursive !== null ? $recursive : false);
		unset($result['code'], $result['token']);
		if ($recursive === null) {
			$result['user'] = $this->user->toArray(false);
			$result['app'] = $this->app->toArray(false);
		}
		return $result;
	}
}
