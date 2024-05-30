<?php

namespace themes\clipone\Views\UserPanelOAuth\Settings\Accesses;

use packages\userpanel;
use packages\userpanel\Authentication;
use packages\userpanel\User;
use packages\userpanel_oauth\Access;
use packages\userpanel_oauth\Authorization;
use packages\userpanel_oauth\Views\Settings\Accesses\Search as ParentView;
use themes\clipone\Navigation;
use themes\clipone\Views\Dashboard;
use themes\clipone\Views\FormTrait;
use themes\clipone\Views\ListTrait;
use themes\clipone\ViewTrait;

class Search extends ParentView
{
    use ViewTrait;
    use ListTrait;
    use FormTrait;

    public static function onSourceLoad()
    {
        parent::onSourceLoad();
        if (!parent::$navigation) {
            return;
        }
        $settings = Dashboard::getSettingsMenu();
        $item = new Navigation\MenuItem('accesses');
        $item->setTitle(t('userpanel_oauth.accesses'));
        $item->setURL(userpanel\url('settings/accesses'));
        $item->setIcon('fa fa-share-alt-square');
        $settings->addItem($item);
    }
    protected $multiuser;

    public function __beforeLoad()
    {
        $this->multiuser = (bool) Authorization::childrenTypes();
        $this->setTitle(t('userpanel_oauth.accesses'));
        $this->addBodyClass('userpanel_oauth-accesses');
        $this->setButtons();
        $this->setFormData();
        Navigation::active('settings/accesses');
    }

    protected function getAppsForSelect(): array
    {
        $apps = [];
        foreach ($this->getApps() as $app) {
            $apps[] = [
                'title' => $app->id.'-'.$app->name,
                'value' => $app->id,
            ];
        }

        return $apps;
    }

    protected function getStatusForSelect(): array
    {
        return [
            [
                'title' => t('userpanel_oauth.access.status.active'),
                'value' => Access::ACTIVE,
            ],
            [
                'title' => t('userpanel_oauth.access.status.deactive'),
                'value' => Access::DEACTIVE,
            ],
        ];
    }

    protected function getComparisonsForSelect(): array
    {
        return [
            [
                'title' => t('search.comparison.contains'),
                'value' => 'contains',
            ],
            [
                'title' => t('search.comparison.equals'),
                'value' => 'equals',
            ],
            [
                'title' => t('search.comparison.startswith'),
                'value' => 'startswith',
            ],
        ];
    }

    private function setFormData()
    {
        if (!$this->multiuser or !$this->canAdd) {
            return;
        }
        $user = $this->getDataForm('user');
        if ($user) {
            $user = User::byId($user);
            if ($user) {
                $this->setDataForm($user->getFullName(), 'user_name');
            }
        } else {
            $this->setDataForm(Authentication::getID(), 'user');
            $this->setDataForm(Authentication::getUser()->getFullName(), 'user_name');
        }
    }

    private function setButtons()
    {
        $this->setButton('accesses_delete', $this->canDelete, [
            'title' => t('userpanel.delete'),
            'icon' => 'fa fa-times',
            'classes' => ['btn', 'btn-xs', 'btn-bricky', 'btn-delete'],
        ]);
    }
}
