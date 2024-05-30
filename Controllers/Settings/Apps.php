<?php

namespace packages\userpanel_oauth\Controllers\Settings;

use packages\base\Image;
use packages\base\NotFound;
use packages\base\Packages;
use packages\base\Response;
use packages\base\Utility\Password;
use packages\base\View;
use packages\userpanel;
use packages\userpanel\Authentication;
use packages\userpanel\Controller;
use packages\userpanel\User;
use packages\userpanel_oauth\App;
use packages\userpanel_oauth\Authorization;
use packages\userpanel_oauth\Views;

class Apps extends Controller
{
    private static function getApp($data): App
    {
        $types = Authorization::childrenTypes();
        $model = new App();
        $model->with('user');
        if ($types) {
            $model->where('userpanel_users.type', $types, 'IN');
        } else {
            $model->where('userpanel_oauth_apps.user_id', Authentication::getID());
        }
        $model->where('userpanel_oauth_apps.id', $data['app']);
        $app = $model->getOne();
        if (!$app) {
            throw new NotFound();
        }

        return $app;
    }

    private static function saveLogo(Image $image): string
    {
        $tmp = $image->getFile();
        $image->resize(250, 250)->saveToFile($tmp);
        $filename = 'storage/public/apps/'.$tmp->md5().'.'.$image->getExtension();
        $file = Packages::package('userpanel_oauth')->getFile($filename);
        $dir = $file->getDirectory();
        if (!$dir->exists()) {
            $dir->make(true);
        }
        $tmp->copyTo($file);

        return $filename;
    }
    protected $authentication = true;

    public function search(): Response
    {
        Authorization::haveOrFail('apps_search');
        $view = View::byName(Views\Settings\Apps\Search::class);
        $this->response->setView($view);
        $inputs = $this->checkinputs([
            'id' => [
                'type' => 'number',
                'optional' => true,
            ],
            'search_user' => [
                'type' => 'number',
                'optional' => true,
            ],
            'name' => [
                'type' => 'string',
                'optional' => true,
            ],
            'token' => [
                'type' => 'string',
                'optional' => true,
            ],
            'comparison' => [
                'values' => ['equals', 'startswith', 'contains'],
                'default' => 'contains',
                'optional' => true,
            ],
        ]);
        $types = Authorization::childrenTypes();
        $app = new App();
        $app->with('user');
        foreach (['id', 'name', 'token'] as $item) {
            if (isset($inputs[$item])) {
                $comparison = $inputs['comparison'];
                if (in_array($item, ['id'])) {
                    $comparison = 'equals';
                }
                $app->where("userpanel_oauth_apps.{$item}", $inputs[$item], $comparison);
            }
        }
        if (isset($inputs['search_user'])) {
            $app->where('userpanel_oauth_apps.user_id', $inputs['search_user']);
        }
        if ($types) {
            $app->where('userpanel_users.type', $types, 'IN');
        } else {
            $app->where('userpanel_oauth_apps.user_id', Authentication::getID());
        }
        $app->orderBy('userpanel_oauth_apps.id', 'DESC');
        $app->pageLimit = $this->items_per_page;
        $apps = $app->paginate($this->page);
        $view->setDataList($apps);
        $view->setPaginate($this->page, $app->totalCount, $this->items_per_page);
        $this->response->setStatus(true);

        return $this->response;
    }

    public function store(): Response
    {
        Authorization::haveOrFail('apps_add');
        $types = Authorization::childrenTypes();
        $inputs = $this->checkinputs([
            'logo' => [
                'type' => 'image',
                'optional' => true,
            ],
            'name' => [
                'type' => 'string',
            ],
            'ip' => [
                'type' => 'ip4',
                'optional' => true,
            ],
            'user' => [
                'type' => User::class,
                'optional' => true,
                'default' => Authentication::getUser(),
                'query' => function ($query) use ($types) {
                    if ($types) {
                        $query->where('type', $types, 'IN');
                    } else {
                        $query->where('id', Authentication::getID());
                    }
                },
            ],
            'status' => [
                'type' => 'int',
                'values' => [App::ACTIVE, App::DEACTIVE],
            ],
        ]);
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
        $this->response->setData($appData, 'app');
        $this->response->Go(userpanel\url('settings/apps'));

        return $this->response;
    }

    public function update($data): Response
    {
        Authorization::haveOrFail('apps_edit');
        $app = self::getApp($data);
        $types = Authorization::childrenTypes();
        $inputs = $this->checkinputs([
            'logo' => [
                'type' => 'image',
                'optional' => true,
            ],
            'logo_remove' => [
                'type' => 'bool',
                'optional' => true,
            ],
            'name' => [
                'type' => 'string',
            ],
            'ip' => [
                'type' => 'ip4',
                'optional' => true,
                'empty' => true,
            ],
            'user' => [
                'type' => User::class,
                'optional' => true,
                'query' => function ($query) use ($types) {
                    if ($types) {
                        $query->where('type', $types, 'IN');
                    } else {
                        $query->where('id', Authentication::getID());
                    }
                },
            ],
            'status' => [
                'type' => 'int',
                'values' => [App::ACTIVE, App::DEACTIVE],
                'optional' => true,
            ],
        ]);
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
        $this->response->setData($app->toArray(), 'app');
        $this->response->Go(userpanel\url('settings/apps'));

        return $this->response;
    }

    public function destroy($data): Response
    {
        $app = self::getApp($data);
        $app->delete();
        $this->response->setStatus(true);
        $this->response->Go(userpanel\url('settings/apps'));

        return $this->response;
    }
}
