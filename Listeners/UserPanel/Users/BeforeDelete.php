<?php

namespace packages\userpanel_oauth\Listeners\UserPanel\Users;

use packages\base\{View\Error};
use packages\userpanel\Events as UserpanelEvents;
use packages\userpanel_oauth\Access;
use packages\userpanel_oauth\App;
use packages\userpanel_oauth\Authorization;

use function packages\userpanel\url;

class BeforeDelete
{
    public function check(UserpanelEvents\Users\BeforeDelete $event): void
    {
        $this->checkApps($event);
        $this->checkAccesses($event);
    }

    private function checkApps(UserpanelEvents\Users\BeforeDelete $event): void
    {
        $user = $event->getUser();
        $hasApp = (new App())->where('user_id', $user->id)->has();
        if (!$hasApp) {
            return;
        }
        $message = t('error.packages.userpanel_oauth.error.apps.user.delete_user_warn.message');
        $error = new Error('packages.userpanel_oauth.error.apps.user.delete_user_warn');
        $error->setType(Error::WARNING);
        if (Authorization::is_accessed('apps_search')) {
            $message .= '<br> '.t('packages.userpanel_oauth.error.apps.delete_user_warn.view_apps').' ';
            $error->setData([
                [
                    'txt' => '<i class="fa fa-search"></i> '.t('packages.userpanel_oauth.error.apps.delete_user_warn.view_apps_btn'),
                    'type' => 'btn-warning',
                    'link' => url('settings/apps', [
                        'search_user' => $user->id,
                    ]),
                ],
            ], 'btns');
        } else {
            $message .= '<br> '.t('packages.userpanel_oauth.error.apps.delete_user_warn.view_apps.tell_someone');
        }
        $error->setMessage($message);

        $event->addError($error);
    }

    private function checkAccesses(UserpanelEvents\Users\BeforeDelete $event): void
    {
        $user = $event->getUser();
        $hasApp = (new Access())->where('user_id', $user->id)->has();
        if (!$hasApp) {
            return;
        }
        $message = t('error.packages.userpanel_oauth.error.accesses.user.delete_user_warn.message');
        $error = new Error('packages.userpanel_oauth.error.accesses.user.delete_user_warn');
        $error->setType(Error::WARNING);
        if (Authorization::is_accessed('apps_search')) {
            $message .= '<br> '.t('packages.userpanel_oauth.error.accesses.delete_user_warn.view_accesses').' ';
            $error->setData([
                [
                    'txt' => '<i class="fa fa-search"></i> '.t('packages.userpanel_oauth.error.accesses.delete_user_warn.view_accesses_btn'),
                    'type' => 'btn-warning',
                    'link' => url('settings/accesses', [
                        'user' => $user->id,
                    ]),
                ],
            ], 'btns');
        } else {
            $message .= '<br> '.t('packages.userpanel_oauth.error.accesses.delete_user_warn.view_accesses.tell_someone');
        }
        $error->setMessage($message);

        $event->addError($error);
    }
}
