<?php
namespace packages\userpanel_oauth;

use packages\userpanel\Authorization as UserPanelAuthorization;

class Authorization extends UserPanelAuthorization {

	public static function is_accessed($permission, $prefix = 'userpanel_oauth') {
		return parent::is_accessed($permission, $prefix);
	}

	public static function haveOrFail($permission, $prefix = 'userpanel_oauth') {
		parent::haveOrFail($permission, $prefix);
	}
}
