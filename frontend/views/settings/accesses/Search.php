<?php
namespace themes\clipone\views\settings\accesses;

use packages\userpanel;
use themes\clipone\{Navigation, ViewTrait, views\ListTrait, views\FormTrait, views\Dashboard};
use packages\userpanel\{Authentication};
use packages\userpanel_oauth\{Authorization, User, Access, views\settings\accesses\Search as ParentView};

class Search extends ParentView {
	use ViewTrait, ListTrait, FormTrait;
	public static function onSourceLoad() {
		parent::onSourceLoad();
		if (!parent::$navigation) {
			return;
		}
		$settings = Dashboard::getSettingsMenu();
		$item = new Navigation\MenuItem("accesses");
		$item->setTitle(t("userpanel_oauth.accesses"));
		$item->setURL(userpanel\url("settings/accesses"));
		$item->setIcon("fa fa-share-alt-square");
		$settings->addItem($item);
	}
	protected $multiuser;
	public function __beforeLoad() {
		$this->multiuser = (bool) Authorization::childrenTypes();
		$this->setTitle(t("userpanel_oauth.accesses"));
		$this->addBodyClass("userpanel_oauth-accesses");
		$this->setButtons();
		$this->setFormData();
		navigation::active("settings/accesses");
	}

	protected function getAppsForSelect(): array {
		$apps = array();
		foreach ($this->getApps() as $app) {
			$apps[] = array(
				"title" => $app->id . "-" . $app->name,
				"value" => $app->id,
			);
		}
		return $apps;
	}
	protected function getStatusForSelect(): array {
		return array(
			array(
				"title" => t("userpanel_oauth.access.status.active"),
				"value" => Access::ACTIVE,
			),
			array(
				"title" => t("userpanel_oauth.access.status.deactive"),
				"value" => Access::DEACTIVE,
			),
		);
	}
	protected function getComparisonsForSelect(): array {
		return array(
			array(
				"title" => t("search.comparison.contains"),
				"value" => "contains"
			),
			array(
				"title" => t("search.comparison.equals"),
				"value" => "equals"
			),
			array(
				"title" => t("search.comparison.startswith"),
				"value" => "startswith"
			)
		);
	}

	private function setFormData() {
		if (!$this->multiuser or !$this->canAdd) {
			return;
		}
		$user = $this->getDataForm("user");
		if ($user) {
			$user = User::byId($user);
			if ($user) {
				$this->setDataForm($user->getFullName(), "user_name");
			}
		} else {
			$this->setDataForm(Authentication::getID(), "user");
			$this->setDataForm(Authentication::getUser()->getFullName(), "user_name");
		}
	}
	private function setButtons() {
		$this->setButton("accesses_delete", $this->canDelete, array(
			"title" => t("userpanel.delete"),
			"icon" => "fa fa-times",
			"classes" => array("btn", "btn-xs", "btn-bricky", "btn-delete"),
		));
	}
}
