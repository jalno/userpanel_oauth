<?php
namespace packages\userpanel_oauth\controllers\OAuth2;

use packages\base\{InputValidationException, NotFound, View, Options, utility\Password};
use packages\userpanel\{Controller, Date, Authentication};
use packages\userpanel_oauth\{validators, views, Access};

class Authorize extends Controller {
	public static function getRedirectURI(string $uri, ?string $state, ?array $parameters = []) {
		if (!isset($parameters['state']) and $state) {
			$parameters['state'] = $state;
		}
		$uri = parse_url($uri);
		if (isset($uri["query"])) {
			parse_str($uri["query"], $query);
		} else {
			$query = [];
		}
		$query = array_replace($query, $parameters);
		$uri['query'] = $query;
		$scheme = isset($uri["scheme"]) ? $uri["scheme"] . "://" : "";
		$host = isset($uri["host"]) ? $uri["host"] : "";
		$port = isset($uri["port"]) ? ":" . $uri["port"] : "";
		$user = isset($uri["user"]) ? $uri["user"] : "";
		$pass = isset($uri["pass"]) ? ":" . $uri["pass"]  : "";
		$pass = ($user || $pass) ? "$pass@" : "";
		$path = isset($uri["path"]) ? $uri["path"] : "";
		$query = isset($uri["query"]) ? "?" . (is_array($uri["query"]) ? http_build_query($uri["query"]) : $uri["query"]) : "";
		$fragment = isset($uri["fragment"]) ? "#" . $uri["fragment"] : "";
		return $scheme . $user . $pass . $host . $port . $path . $query . $fragment;
	}
	
	protected $authentication = true;

	/**
	 * Show prompt form to user.
	 * if inputs validation fails, instead of showing errors it will show notfound error.
	 * 
	 * @throws NotFound
	 */
	public function prompt() {
		try {
			$inputs = $this->checkinputs(array(
				'response_type' => array(
					'type' => 'string',
					'values' => ['code']
				),
				'client_id' => array(
					'type' => validators\AppTokenValidator::class,
				),
				'redirect_uri' => array(
					'type' => 'url',
					'protocols' => null,
				),
				'state' => array(
					'type' => 'string',
					'optional' => true,
				)
			));
			$view = View::byName(views\Prompt::class);
			$view->setApp($inputs['client_id']);
			$view->setRedirect($inputs['redirect_uri']);
			$view->setRejectRedirect(self::getRedirectURI($inputs['redirect_uri'], $inputs['state'] ?? null, array(
				'error' => 'access_denied'
			)));
			if (isset($inputs['state'])) {
				$view->setState($inputs['state']);
			}
			$this->response->setStatus(true);
			$this->response->setView($view);
		} catch (InputValidationException $e) {
			throw new NotFound();
		}
		return $this->response;
	}

	/**
	 * Confrim authrozation and create new access.
	 * if inputs validation fails, instead of showing errors it will show notfound error.
	 * 
	 * @throws NotFound
	 */
	public function confrim() {
		try {
			$inputs = $this->checkinputs(array(
				'response_type' => array(
					'type' => 'string',
					'values' => ['code']
				),
				'client_id' => array(
					'type' => validators\AppTokenValidator::class,
				),
				'redirect_uri' => array(
					'type' => 'url',
					'protocols' => null,
				),
				'state' => array(
					'type' => 'string',
					'optional' => true,
				)
			));
			$tokenLifetime = Options::get("packages.userpanel_oauth.accesses.token_lifetime");
			$access = new Access();
			$access->user_id = Authentication::getID();
			$access->app_id = $inputs['client_id']->id;
			$access->code = Password::generate(32);
			$access->token = Password::generate(32);
			$access->create_at = Date::time();
			$access->expire_token_at = $tokenLifetime > 0 ? Date::time() + $tokenLifetime : 0;
			$access->status = Access::ACTIVE;
			$access->save();
			$redirectTo = self::getRedirectURI($inputs['redirect_uri'], $inputs['state'] ?? null, array(
				'code' => $access->code
			));
			$this->response->setStatus(true);
			$this->response->Go($redirectTo);
		} catch (InputValidationException $e) {
			throw new NotFound();
		}
		return $this->response;
	}
}
