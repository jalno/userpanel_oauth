<?php
namespace themes\clipone\Views\UserPanelOAuth\Settings\Apps;

use packages\userpanel;
use packages\userpanel\{Authentication, User};
use packages\userpanel_oauth\{Views\Settings\Apps\Search as ParentView, Authorization, App};
use themes\clipone\{Navigation, Navigation\MenuItem, ViewTrait, Views\ListTrait, Views\FormTrait, Views\Dashboard};

class Search extends parentView {
	use ViewTrait, ListTrait, FormTrait;

	public static function onSourceLoad() {
		parent::onSourceLoad();
		if (!parent::$navigation) {
			return;
		}
		$settings = Dashboard::getSettingsMenu();
		$item = new MenuItem("apps");
		$item->setTitle(t("userpanel_oauth.apps"));
		$item->setURL(userpanel\url("settings/apps"));
		$item->setIcon("fa fa-cubes");
		$settings->addItem($item);
	}

	/** @var bool */
	protected $multiuser;

	public function __beforeLoad() {
		$this->multiuser = (bool) Authorization::childrenTypes();
		$this->setTitle(t("userpanel_oauth.apps"));
		$this->addBodyClass("userpanel_oauth-apps");
		$this->setButtons();
		$this->setFormData();
		Navigation::active("settings/apps");
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
	
	protected function getStatusForSelect(): array {
		return array(
			array(
				"title" => t("userpanel_oauth.app.status.active"),
				"value" => App::ACTIVE,
			),
			array(
				"title" => t("userpanel_oauth.app.status.deactive"),
				"value" => App::DEACTIVE,
			),
		);
	}

	private function setFormData(): void {
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
	private function setButtons(): void {
		$this->setButton("apps_edit", $this->canEdit, array(
			"title" => t("userpanel.edit"),
			"icon" => "fa fa-edit",
			"classes" => ["btn", "btn-xs", "btn-teal", "btn-edit"],
		));
		$this->setButton("apps_delete", $this->canDelete, array(
			"title" => t("userpanel.delete"),
			"icon" => "fa fa-times",
			"classes" => ["btn", "btn-xs", "btn-bricky", "btn-delete"],
		));
	}
}
