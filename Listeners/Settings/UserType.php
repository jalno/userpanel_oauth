<?php

namespace packages\userpanel_oauth\Listeners\Settings;

use packages\userpanel\UserType\Permissions;

class UserType
{
    public function permissions(): void
    {
        $permissions = [
            'apps_search',
            'apps_add',
            'apps_edit',
            'apps_delete',
            'accesses_search',
            'accesses_add',
            'accesses_edit',
            'accesses_delete',
        ];
        foreach ($permissions as $permission) {
            Permissions::add('userpanel_oauth_'.$permission);
        }
    }
}
