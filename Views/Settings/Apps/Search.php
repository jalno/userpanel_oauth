<?php
namespace packages\userpanel_oauth\Views\Settings\Apps;

use packages\base\Views\Traits\Form;
use packages\userpanel\Views\ListView;
use packages\userpanel_oauth\Authorization;

class Search extends ListView {
	use Form;
	
	public static function onSourceLoad() {
		self::$navigation = Authorization::is_accessed("apps_search");
	}

	/** @var bool */
	protected static $navigation;
	
	/** @var bool */
	protected $canAdd;
	
	/** @var bool */
	protected $canEdit;
	
	/** @var bool */
	protected $canDelete;

	public function __construct() {
		$this->canAdd = Authorization::is_accessed("apps_add");
		$this->canEdit = Authorization::is_accessed("apps_edit");
		$this->canDelete = Authorization::is_accessed("apps_delete");
	}
	
	/**
	 * Export view to ajax or api requests.
	 * 
	 * @return array
	 */
	public function export() {
		$original = parent::export();
		$original['data']['can_add'] = $this->canAdd;
		$original['data']['can_edit'] = $this->canEdit;
		$original['data']['can_delete'] = $this->canDelete;
		return $original;
	}
}
