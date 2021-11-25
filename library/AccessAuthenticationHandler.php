<?php
namespace packages\userpanel_oauth;

use packages\base\{http, Exception};
use packages\userpanel\{Authentication, User, Date};

class AccessAuthenticationHandler implements Authentication\IHandler {

	public static function getAccessByToken(string $token): ?Access {
		$access = Access::with("app")
						->with("user")
						->where("userpanel_oauth_apps.status", App::ACTIVE)
						->where("userpanel_users.status", User::active)
						->where("userpanel_oauth_accesses.token", $token)
						->where("userpanel_oauth_accesses.status", Access::ACTIVE)
						->getOne();
		if (!$access) {
			return null;
		}

		$ip = Http::$client['ip'] ?? null;

		if ($access->app->ip and $access->app->ip != $ip) {
			return null;
		}

		if ($ip) {
			$access->lastip = $ip;
		}

		$access->lastuse_at = Date::time();
		$access->save();

		return $access;
	}

	/** @var Accecss|null */
	protected $access;
	
	/**
	 * Check authentication of user.
	 * Validator can use http fields directly.
	 * 
	 * @return User|null
	 */
	public function check(): ?User {
		if ($this->access) {
			return $this->access->user;
		}
		$header = http::getHeader("authorization");
		if (!$header) {
			return null;
		}
		$header = explode(" ", $header, 2);
		if (count($header) != 2 or strtolower($header[0]) != "bearer") {
			return null;
		}

		$access = self::getAccessByToken($header[1]);

		if (!$access) {
			return null;
		}

		$this->access = $access;

		return $this->access->user;
	}

	/**
	 * Earse all the sign of current-user from memory.
	 * 
	 * @return void
	 */
	public function forget(): void {
		$this->access = null;
	}
}
