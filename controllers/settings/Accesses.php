<?php
namespace packages\userpanel_oauth\controllers\settings;

use packages\base\{Response, NotFound, InputValidationException, Options, utility\Password};
use packages\userpanel\{Controller, View, Authentication, User, Date};
use packages\userpanel_oauth\{Authorization, views, App, Access};

class Accesses extends Controller {
	private static function getAccess($data) {
		$types = Authorization::childrenTypes();
		$model = new Access();
		$model->with("user");
		if ($types) {
			$model->where("userpanel_users.type", $types, "IN");
		} else {
			$model->where("userpanel_oauth_accesses.user_id", Authentication::getID());
		}
		$model->where("userpanel_oauth_accesses.id", $data["access"]);
		$access = $model->getOne();
		if (!$access) {
			throw new NotFound();
		}
		return $access;
	}
	protected $authentication = true;

	public function search(): Response {
		Authorization::haveOrFail("accesses_search");
		$view = view::byName(views\settings\Accesses\Search::class);
		$this->response->setView($view);
		$me = Authentication::getID();
		$types = Authorization::childrenTypes();
		$app = new App();
		$app->with("user");
		if ($types) {
			$app->where("userpanel_users.type", $types, "IN");
		} else {
			$app->where("userpanel_oauth_apps.user", $me);
		}
		$view->setApps($app->get());
		$inputs = $this->checkinputs(array(
			"id" => array(
				"type" => "number",
				"optional" => true,
			),
			"user" => array(
				"type" => User::class,
				"optional" => true,
				"query" => function ($query) use ($types, $me) {
					if ($types) {
						$query->where("type", $types, "IN");
					} else {
						$query->where("id", $me);
					}
				}
			),
			"name" => array(
				"type" => "string",
				"optional" => true,
			),
			"app" => array(
				"type" => App::class,
				"optional" => true,
			),
			"status" => array(
				"values" => [Access::ACTIVE, Access::DEACTIVE],
				"optional" => true,
			),
			"comparison" => array(
				"values" => ["equals", "startswith", "contains"],
				"default" => "contains",
				"optional" => true
			),
		));
		$model = new Access();
		$model->with("user");
		$model->with("app");
		foreach (array("id", "name", "status") as $item) {
			if (isset($inputs[$item]) and $inputs[$item]) {
				$comparison = $inputs["comparison"];
				if(in_array($item, array("id", "status"))){
					$comparison = "equals";
				}
				$model->where("userpanel_oauth_accesses.{$item}", $inputs[$item], $comparison);
			}
		}
		if (isset($inputs["user"])) {
			$model->where("userpanel_oauth_accesses.user_id", $inputs["user"]->id);
		}
		if (isset($inputs["app"])) {
			$model->where("userpanel_oauth_accesses.app_id", $inputs["app"]->id);
		}
		if ($types) {
			$model->where("userpanel_users.type", $types, "IN");
		} else {
			$model->where("userpanel_oauth_accesses.user_id", $me);
		}
		$model->orderBy("userpanel_oauth_accesses.id", "DESC");
		$model->pageLimit = $this->items_per_page;
		$accesses = $model->paginate($this->page);
		$view->setDataList($accesses);
		$view->setPaginate($this->page, $model->totalCount, $this->items_per_page);
		$this->response->setStatus(true);
		return $this->response;
	}
	public function add(): Response {
		Authorization::haveOrFail("accesses_add");
		$me = Authentication::getID();
		$types = authorization::childrenTypes();
		$inputs = $this->checkinputs(array(
			"app" => array(
				"type" => App::class,
				"query" => function ($query) {
					$query->where("status", App::ACTIVE);
				}
			),
			"user" => array(
				"type" => User::class,
				"optional" => true,
				"default" => Authentication::getUser(),
				"query" => function ($query) use ($types, $me) {
					if ($types) {
						$query->where("type", $types, "IN");
					} else {
						$query->where("id", $me);
					}
				}
			),
			"status" => array(
				"type" => "number",
				"values" => [Access::ACTIVE, Access::DEACTIVE],
				"optional" => true,
				"default" => Access::ACTIVE,
			),
		));
		$tokenLifetime = Options::get("packages.userpanel_oauth.accesses.token_lifetime");
		$model = new Access();
		$model->app_id = $inputs['app']->id;
		$model->user_id = $inputs['user']->id;
		$model->code = Password::generate(32);
		$model->token = Password::generate(32);
		$model->create_at = Date::time();
		$model->expire_token_at = $tokenLifetime > 0 ? Date::time() + $tokenLifetime : 0;
		$model->status = $inputs['status'];
		$model->save();
		$this->response->setStatus(true);
		$access = $model->toArray();
		$access['code'] = $model->code;
		$access['token'] = $model->token;
		$this->response->setData($access, "access");
		return $this->response;
	}
	public function update($data): response {
		Authorization::haveOrFail("accesses_edit");
		$access = self::getAccess($data);
		$inputs = $this->checkinputs(array(
			"status" => array(
				"values" => [Access::ACTIVE, Access::DEACTIVE],
				"optional" => true,
			),
		));
		if (isset($inputs['status'])) {
			$access->status = $inputs['status'];
		}
		$access->save();
		$this->response->setStatus(true);
		$this->response->setData($access, "access");
		return $this->response;
	}
	public function destroy($data): Response {
		$types = Authorization::childrenTypes();
		$access = self::getAccess($data);
		$access->delete();
		$this->response->setStatus(true);
		return $this->response;
	}
}
