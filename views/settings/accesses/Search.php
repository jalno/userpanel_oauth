<?php
namespace packages\userpanel_oauth\views\settings\accesses;

use packages\base\views\traits\form;
use packages\userpanel\views\Listview;
use packages\userpanel_oauth\{Authorization, App};

class Search extends Listview {
	use form;

	public static function onSourceLoad() {
		self::$navigation = Authorization::is_accessed("accesses_search");
	}
	
	/** @var bool */
	protected static $navigation;

	/** @var App[] */
	protected $apps = [];

	/** @var bool */
	protected $canAdd;

	/** @var bool */
	protected $canEdit;

	/** @var bool */
	protected $canDelete;

	public function __construct(){
		$this->canAdd = Authorization::is_accessed("accesses_add");
		$this->canEdit = Authorization::is_accessed("accesses_edit");
		$this->canDelete = Authorization::is_accessed("accesses_delete");
	}

	/**
	 * Set list of apps
	 * 
	 * @param App[] $apps
	 * @return void
	 */
	public function setApps(array $apps): void {
		$this->apps = $apps;
	}

	/**
	 * Get list of apps
	 * 
	 * @return App[]
	 */
	public function getApps(): array {
		return $this->apps;
	}

	/**
	 * Export view to ajax or api requests.
	 * 
	 * @return array
	 */
	public function export() {
		$original = parent::export();
		$original['data']['apps'] = dbObject::objectToArray($this->apps);
		$original['data']['can_add'] = $this->canAdd;
		$original['data']['can_edit'] = $this->canEdit;
		$original['data']['can_delete'] = $this->canDelete;
		return $original;
	}
}
