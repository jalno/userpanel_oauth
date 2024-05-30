<?php

namespace packages\userpanel_oauth\Controllers\Settings;

use packages\base\NotFound;
use packages\base\Options;
use packages\base\Response;
use packages\base\Utility\Password;
use packages\userpanel\Authentication;
use packages\userpanel\Controller;
use packages\userpanel\Date;
use packages\userpanel\User;
use packages\userpanel\View;
use packages\userpanel_oauth\Access;
use packages\userpanel_oauth\App;
use packages\userpanel_oauth\Authorization;
use packages\userpanel_oauth\Views;

class Accesses extends Controller
{
    private static function getAccess($data)
    {
        $types = Authorization::childrenTypes();
        $model = new Access();
        $model->with('user');
        if ($types) {
            $model->where('userpanel_users.type', $types, 'IN');
        } else {
            $model->where('userpanel_oauth_accesses.user_id', Authentication::getID());
        }
        $model->where('userpanel_oauth_accesses.id', $data['access']);
        $access = $model->getOne();
        if (!$access) {
            throw new NotFound();
        }

        return $access;
    }
    protected $authentication = true;

    public function search(): Response
    {
        Authorization::haveOrFail('accesses_search');
        $view = View::byName(Views\Settings\Accesses\Search::class);
        $this->response->setView($view);
        $me = Authentication::getID();
        $types = Authorization::childrenTypes();
        $app = new App();
        $app->with('user');
        if ($types) {
            $app->where('userpanel_users.type', $types, 'IN');
        } else {
            $app->where('userpanel_oauth_apps.user', $me);
        }
        $view->setApps($app->get());
        $inputs = $this->checkinputs([
            'id' => [
                'type' => 'number',
                'optional' => true,
            ],
            'user' => [
                'type' => User::class,
                'optional' => true,
                'query' => function ($query) use ($types, $me) {
                    if ($types) {
                        $query->where('type', $types, 'IN');
                    } else {
                        $query->where('id', $me);
                    }
                },
            ],
            'name' => [
                'type' => 'string',
                'optional' => true,
            ],
            'app' => [
                'type' => App::class,
                'optional' => true,
            ],
            'status' => [
                'values' => [Access::ACTIVE, Access::DEACTIVE],
                'optional' => true,
            ],
            'comparison' => [
                'values' => ['equals', 'startswith', 'contains'],
                'default' => 'contains',
                'optional' => true,
            ],
        ]);
        $model = new Access();
        $model->with('user');
        $model->with('app');
        foreach (['id', 'name', 'status'] as $item) {
            if (isset($inputs[$item]) and $inputs[$item]) {
                $comparison = $inputs['comparison'];
                if (in_array($item, ['id', 'status'])) {
                    $comparison = 'equals';
                }
                $model->where("userpanel_oauth_accesses.{$item}", $inputs[$item], $comparison);
            }
        }
        if (isset($inputs['user'])) {
            $model->where('userpanel_oauth_accesses.user_id', $inputs['user']->id);
        }
        if (isset($inputs['app'])) {
            $model->where('userpanel_oauth_accesses.app_id', $inputs['app']->id);
        }
        if ($types) {
            $model->where('userpanel_users.type', $types, 'IN');
        } else {
            $model->where('userpanel_oauth_accesses.user_id', $me);
        }
        $model->orderBy('userpanel_oauth_accesses.id', 'DESC');
        $model->pageLimit = $this->items_per_page;
        $accesses = $model->paginate($this->page);
        $view->setDataList($accesses);
        $view->setPaginate($this->page, $model->totalCount, $this->items_per_page);
        $this->response->setStatus(true);

        return $this->response;
    }

    public function add(): Response
    {
        Authorization::haveOrFail('accesses_add');
        $me = Authentication::getID();
        $types = Authorization::childrenTypes();
        $inputs = $this->checkinputs([
            'app' => [
                'type' => App::class,
                'query' => function ($query) {
                    $query->where('status', App::ACTIVE);
                },
            ],
            'user' => [
                'type' => User::class,
                'optional' => true,
                'default' => Authentication::getUser(),
                'query' => function ($query) use ($types, $me) {
                    if ($types) {
                        $query->where('type', $types, 'IN');
                    } else {
                        $query->where('id', $me);
                    }
                },
            ],
            'status' => [
                'type' => 'number',
                'values' => [Access::ACTIVE, Access::DEACTIVE],
                'optional' => true,
                'default' => Access::ACTIVE,
            ],
        ]);
        $tokenLifetime = Options::get('packages.userpanel_oauth.accesses.token_lifetime');
        $model = new Access();
        $model->app_id = $inputs['app']->id;
        $model->user_id = $inputs['user']->id;
        $model->code = Password::generate(32);
        $model->token = Password::generate(32);
        $model->create_at = Date::time();
        $model->expire_token_at = $tokenLifetime > 0 ? Date::time() + $tokenLifetime : 0;
        $model->status = $inputs['status'];
        $model->save();
        $this->response->setStatus(true);
        $access = $model->toArray();
        $access['code'] = $model->code;
        $access['token'] = $model->token;
        $this->response->setData($access, 'access');

        return $this->response;
    }

    public function update($data): Response
    {
        Authorization::haveOrFail('accesses_edit');
        $access = self::getAccess($data);
        $inputs = $this->checkinputs([
            'status' => [
                'values' => [Access::ACTIVE, Access::DEACTIVE],
                'optional' => true,
            ],
        ]);
        if (isset($inputs['status'])) {
            $access->status = $inputs['status'];
        }
        $access->save();
        $this->response->setStatus(true);
        $this->response->setData($access, 'access');

        return $this->response;
    }

    public function destroy($data): Response
    {
        $types = Authorization::childrenTypes();
        $access = self::getAccess($data);
        $access->delete();
        $this->response->setStatus(true);

        return $this->response;
    }
}
