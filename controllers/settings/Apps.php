<?php
namespace packages\userpanel_oauth\controllers\settings;

use packages\base\{Response, NotFound, View, utility\Password, Packages, Image};
use packages\userpanel;
use packages\userpanel\{Controller, User, Authentication};
use packages\userpanel_oauth\{Authorization, App, views};

class Apps extends Controller {

	private static function getApp($data): App {
		$types = authorization::childrenTypes();
		$model = new App();
		$model->with("user");
		if ($types) {
			$model->where("userpanel_users.type", $types, "IN");
		} else {
			$model->where("userpanel_oauth_apps.user_id", authentication::getID());
		}
		$model->where("userpanel_oauth_apps.id", $data["app"]);
		$app = $model->getOne();
		if (!$app) {
			throw new NotFound();
		}
		return $app;
	}
	private static function saveLogo(Image $image): string {
		$tmp = $image->getFile();
		$image->resize(250, 250)->saveToFile($tmp);
		$filename = "storage/public/apps/" . $tmp->md5() . "." . $image->getExtension();
		$file = Packages::package("userpanel_oauth")->getFile($filename);
		$dir = $file->getDirectory();
		if (!$dir->exists()) {
			$dir->make(true);
		}
		$tmp->copyTo($file);
		return $filename;
	}
	protected $authentication = true;

	public function search(): Response {
		Authorization::haveOrFail("apps_search");
		$view = view::byName(views\settings\apps\Search::class);
		$this->response->setView($view);
		$inputs = $this->checkinputs(array(
			"id" => array(
				"type" => "number",
				"optional" => true,
			),
			"search_user" => array(
				"type" => "number",
				"optional" => true,
			),
			"name" => array(
				"type" => "string",
				"optional" => true,
			),
			"token" => array(
				"type" => "string",
				"optional" => true,
			),
			"comparison" => array(
				"values" => array("equals", "startswith", "contains"),
				"default" => "contains",
				"optional" => true
			),
		));
		$types = Authorization::childrenTypes();
		$app = new App();
		$app->with("user");
		foreach (["id", "name", "token"] as $item) {
			if (isset($inputs[$item])) {
				$comparison = $inputs["comparison"];
				if(in_array($item, ["id"])){
					$comparison = "equals";
				}
				$app->where("userpanel_oauth_apps.{$item}", $inputs[$item], $comparison);
			}
		}
		if (isset($inputs["search_user"])) {
			$app->where("userpanel_oauth_apps.user_id", $inputs["search_user"]);
		}
		if ($types) {
			$app->where("userpanel_users.type", $types, "IN");
		} else {
			$app->where("userpanel_oauth_apps.user_id", Authentication::getID());
		}
		$app->orderBy("userpanel_oauth_apps.id", "DESC");
		$app->pageLimit = $this->items_per_page;
		$apps = $app->paginate($this->page);
		$view->setDataList($apps);
		$view->setPaginate($this->page, $app->totalCount, $this->items_per_page);
		$this->response->setStatus(true);
		return $this->response;
	}

	public function store(): Response {
		Authorization::haveOrFail("apps_add");
		$types = Authorization::childrenTypes();
		$inputs = $this->checkinputs(array(
			"logo" => array(
				"type" => "image",
				"optional" => true,
			),
			"name" => array(
				"type" => "string",
			),
			"ip" => array(
				"type" => "ip4",
				"optional" => true,
			),
			"user" => array(
				"type" => User::class,
				"optional" => true,
				"default" => Authentication::getUser(),
				"query" => function ($query) use ($types) {
					if ($types) {
						$query->where("type", $types, "IN");
					} else {
						$query->where("id", Authentication::getID());
					}
				}
			),
			"status" => array(
				"type" => "int",
				"values" => [App::ACTIVE, App::DEACTIVE]
			)
		));
		$app = new App();
		$app->user_id = $inputs['user']->id;
		$app->name = $inputs['name'];
		$app->token = Password::generate(32);
		$app->secret = Password::generate(32);
		$app->status = App::ACTIVE;
		if (isset($inputs['ip'])) {
			$app->ip = $inputs['ip'];
		}
		if (isset($inputs['logo'])) {
			$app->logo = self::saveLogo($inputs['logo']);
		}
		$app->save();
		$this->response->setStatus(true);
		$appData = $app->toArray();
		$appData['secret'] = $app->secret;
		$this->response->setData($appData, "app");
		$this->response->Go(userpanel\url("settings/apps"));
		return $this->response;
	}
	public function update($data): Response {
		Authorization::haveOrFail("apps_edit");
		$app = self::getApp($data);
		$types = Authorization::childrenTypes();
		$inputs = $this->checkinputs(array(
			"logo" => array(
				"type" => "image",
				"optional" => true,
			),
			"logo_remove" => array(
				"type" => "bool",
				"optional" => true,
			),
			"name" => array(
				"type" => "string",
			),
			"ip" => array(
				"type" => "ip4",
				"optional" => true,
				"empty" => true,
			),
			"user" => array(
				"type" => User::class,
				"optional" => true,
				"query" => function ($query) use ($types) {
					if ($types) {
						$query->where("type", $types, "IN");
					} else {
						$query->where("id", Authentication::getID());
					}
				}
			),
			"status" => array(
				"type" => "int",
				"values" => [App::ACTIVE, App::DEACTIVE],
				"optional" => true,
			)
		));
		if (isset($inputs['user'])) {
			$app->user_id = $inputs['user']->id;
		}
		foreach (['name', 'ip', 'status'] as $key) {
			if (isset($inputs[$key])) {
				$app->$key = $inputs[$key];
			}
		}
		if (isset($inputs['logo'])) {
			$app->logo = self::saveLogo($inputs['logo']);
		} elseif (isset($inputs['logo_remove'])) {
			$app->logo = null;
		}
		$app->save();
		$this->response->setStatus(true);
		$this->response->setData($app->toArray(), "app");
		$this->response->Go(userpanel\url("settings/apps"));
		return $this->response;
	}
	public function destroy($data): response {
		$app = self::getApp($data);
		$app->delete();
		$this->response->setStatus(true);
		$this->response->Go(userpanel\url("settings/apps"));
		return $this->response;
	}
}
