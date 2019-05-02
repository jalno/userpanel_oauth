<?php
namespace packages\userpanel_oauth;

use packages\base\{db\dbObject, Packages};
use packages\userpanel\User;

class App extends dbObject {
	const ACTIVE = 1;
	const DEACTIVE = 2;
	
	protected $dbTable = "userpanel_oauth_apps";
	protected $primaryKey = "id";
	protected $dbFields = array(
		"name" => array("type" => "text", "required" => true),
		"user_id" => array("type" => "int", "required" => true),
		"token" => array("type" => "text", "required" => true, "unique" => true),
		"secret" => array("type" => "text", "required" => true),
		"logo" => array("type" => "text"),
		"ip" => array("type" => "text"),
		"status" => array("type" => "int", "required" => true),
	);
	protected $relations = array(
		"user" => array("hasOne", User::class, "user_id")
	);
	public function getLogoURL(): string {
		return Packages::package("userpanel_oauth")->url($this->logo);
	}

	/**
	 * Converts object data to an associative array.
	 *
	 * @return array Converted data
	 */
	public function toArray ($recursive = null) {
		$result = parent::toArray($recursive !== null ? $recursive : false);
		if ($this->logo) {
			$result['logo'] = $this->getLogoURL();
		}
		unset($result['secret']);
		if ($recursive === null) {
			$result['user'] = $this->user->toArray(false);
		}
		return $result;
	}
}
