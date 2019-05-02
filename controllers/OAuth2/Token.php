<?php
namespace packages\userpanel_oauth\controllers\OAuth2;

use packages\base\{InputValidationException, NotFound, View, Options, utility\Password};
use packages\userpanel\{Controller, Date, Authentication, User};
use packages\userpanel_oauth\{validators, views, Access, App};

class Token extends Controller {

	protected $authentication = false;

	public function code() {
		try {
			$inputs = $this->checkinputs(array(
				'grant_type' => array(
					'type' => 'string',
					'values' => ['authorization_code']
				),
				'client_id' => array(
					'type' => validators\AppTokenValidator::class,
				),
				'redirect_uri' => array(
					'type' => 'url',
					'protocols' => null,
				),
				'code' => array(
					'type' => 'string',
				)
			));
			$access = (new Access())
						->with("user")
						->with("app")
						->where("userpanel_oauth_accesses.app_id", $inputs['client_id']->id)
						->where("userpanel_oauth_accesses.status", Access::ACTIVE)
						->where("userpanel_oauth_apps.status", App::ACTIVE)
						->where("userpanel_users.status", User::active)
						->where("userpanel_oauth_accesses.code", $inputs['code'])
						->getOne();
			if (!$access) {
				$this->response->setStatus(false);
				$this->response->setHttpCode(400);
				$this->response->setData('invalid_grant', 'error');
				return $this->response;
			}
			$tokenLifetime = intval(Options::get("packages.userpanel_oauth.accesses.token_lifetime"));
			if ($tokenLifetime > 0) {
				$access->code = Password::generate(32);
			}
			$access->token = Password::generate(32);
			$access->expire_token_at = $tokenLifetime > 0 ? Date::time() + $tokenLifetime : 0;
			$access->save();

			$this->response->setStatus(true);
			$this->response->setData($access->token, 'access_token');
			$this->response->setData("bearer", 'token_type');
			if ($tokenLifetime > 0) {
				$this->response->setData($tokenLifetime, 'expires_in');
			}
			$this->response->setData($access->code, 'refresh_token');
		} catch (InputValidationException $e) {
			$this->response->setStatus(false);
			$this->response->setHttpCode(400);
			$this->response->setData('invalid_request', 'error');
		}
		return $this->response;
	}
}
