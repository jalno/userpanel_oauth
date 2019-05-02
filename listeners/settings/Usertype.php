<?php
namespace packages\userpanel_oauth\listeners\settings;

use packages\userpanel\Usertype\Permissions;

class Usertype {
	public function permissions(): void {
		$permissions = array(
			'apps_search',
			'apps_add',
			'apps_edit',
			'apps_delete',
			'accesses_search',
			'accesses_add',
			'accesses_edit',
			'accesses_delete',
		);
		foreach ($permissions as $permission) {
			Permissions::add('userpanel_oauth_'.$permission);
		}
	}
}
