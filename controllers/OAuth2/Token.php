<?php
namespace packages\userpanel_oauth\controllers\OAuth2;

use packages\base\{InputValidationException, Options, utility\Password, http, Response, db};
use packages\userpanel\{Controller, Date, User};
use packages\userpanel_oauth\{validators, Access, App};

class Token extends Controller {

	protected $authentication = false;

	public function code() {
		http::$request['get']['ajax'] = 1;
		$this->response = new Response();
		try {
			$inputs = $this->checkinputs(array(
				'grant_type' => array(
					'type' => 'string',
					'values' => ['authorization_code', 'refresh_token', 'password']
				),
				'client_id' => array(
					'type' => validators\AppTokenValidator::class,
				),
				'redirect_uri' => array(
					'type' => 'url',
					'protocols' => null,
				),
			));
			if ($inputs['grant_type'] == 'authorization_code') {
				$inputs = array_merge($inputs, $this->checkinputs(array(
					'code' => array(
						'type' => 'string',
					),
				)));
			} elseif ($inputs['grant_type'] == 'refresh_token') {
				$inputs = array_merge($inputs, $this->checkinputs(array(
					'refresh_token' => array(
						'type' => 'string',
					),
				)));
				$inputs['code'] = $inputs['refresh_token'];
			} elseif ($inputs['grant_type'] == 'password') {
				$inputs = array_merge($inputs, $this->checkinputs(array(
					'username' => array(
						'type' => ['cellphone', 'email'],
					),
					'password' => array(
						'type' => 'string',
					),
				)));
			}
			$access = null;
			if ($inputs['grant_type'] == 'authorization_code' or $inputs['grant_type'] == 'refresh_token') {
				$access = $this->handleCodeToken($inputs);
			} elseif ($inputs['grant_type'] == 'password') {
				$access = $this->handlePasswordToken($inputs);
			}
			if ($access) {
				$this->response->setStatus(true);
				$this->response->setData($access->token, 'access_token');
				$this->response->setData("bearer", 'token_type');
				if ($access->expire_token_at > 0) {
					$this->response->setData($access->expire_token_at - Date::time(), 'expires_in');
				}
				$this->response->setData($access->code, 'refresh_token');
			}
		} catch (InputValidationException $e) {
			$this->response->setStatus(false);
			$this->response->setHttpCode(400);
			$this->response->setData('invalid_request', 'error');
		}
		return $this->response;
	}
	private function handleCodeToken(array $inputs): Access {
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
			return null;
		}
		$tokenLifetime = intval(Options::get("packages.userpanel_oauth.accesses.token_lifetime"));
		if ($tokenLifetime > 0) {
			$access->code = Password::generate(32);
		}
		$access->token = Password::generate(32);
		$access->expire_token_at = $tokenLifetime > 0 ? Date::time() + $tokenLifetime : 0;
		$access->save();

		return $access;
	}

	private function handlePasswordToken(array $inputs): Access {
		$p = new db\Parenthesis();
		$p->where("email", $inputs['username']);
		$p->orwhere("cellphone", $inputs['username']);
		$user = (new User)
			->where("status", User::active)
			->where($p)
			->getOne();
		if (!$user) {
			throw new InputValidationException("username");
		}
		if (!$user->password_verify($inputs['password'])) {
			$log = new \packages\userpanel\Log();
			$log->title = t("log.wrongLogin");
			$log->type = \packages\userpanel\logs\WrongLogin::class;
			$log->user = $user->id;
			$log->parameters = [
				'user' => $user,
				'wrongpaswd' => $inputs['password']
			];
			$log->save();
			throw new InputValidationException('password');
		}
		$tokenLifetime = Options::get("packages.userpanel_oauth.accesses.token_lifetime");
		$access = new Access();
		$access->user_id = $user->id;
		$access->app_id = $inputs['client_id']->id;
		$access->code = Password::generate(32);
		$access->token = Password::generate(32);
		$access->create_at = Date::time();
		$access->expire_token_at = $tokenLifetime > 0 ? Date::time() + $tokenLifetime : 0;
		$access->status = Access::ACTIVE;
		$access->save();
		return $access;
	}
}
