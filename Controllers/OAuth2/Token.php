<?php

namespace packages\userpanel_oauth\Controllers\OAuth2;

use packages\base\DB;
use packages\base\HTTP;
use packages\base\InputValidationException;
use packages\base\Options;
use packages\base\Response;
use packages\base\Utility\Password;
use packages\userpanel\Controller;
use packages\userpanel\Date;
use packages\userpanel\User;
use packages\userpanel_oauth\Access;
use packages\userpanel_oauth\App;
use packages\userpanel_oauth\Validators;

class Token extends Controller
{
    protected $authentication = false;

    public function code()
    {
        HTTP::$request['get']['ajax'] = 1;
        $this->response = new Response();
        try {
            $inputs = $this->checkinputs([
                'grant_type' => [
                    'type' => 'string',
                    'values' => ['authorization_code', 'refresh_token', 'password'],
                ],
                'client_id' => [
                    'type' => Validators\AppTokenValidator::class,
                ],
            ]);
            if ('authorization_code' == $inputs['grant_type']) {
                $inputs = array_merge($inputs, $this->checkinputs([
                    'code' => [
                        'type' => 'string',
                    ],
                    'redirect_uri' => [
                        'type' => 'url',
                        'protocols' => null,
                    ],
                ]));
            } elseif ('refresh_token' == $inputs['grant_type']) {
                $inputs = array_merge($inputs, $this->checkinputs([
                    'refresh_token' => [
                        'type' => 'string',
                    ],
                ]));
                $inputs['code'] = $inputs['refresh_token'];
            } elseif ('password' == $inputs['grant_type']) {
                $inputs = array_merge($inputs, $this->checkinputs([
                    'username' => [
                        'type' => ['cellphone', 'email'],
                    ],
                    'password' => [
                        'type' => 'string',
                    ],
                ]));
            }
            $access = null;
            if ('authorization_code' == $inputs['grant_type'] or 'refresh_token' == $inputs['grant_type']) {
                $access = $this->handleCodeToken($inputs);
            } elseif ('password' == $inputs['grant_type']) {
                $access = $this->handlePasswordToken($inputs);
            }
            if ($access) {
                $this->response->setStatus(true);
                $this->response->setData($access->token, 'access_token');
                $this->response->setData('bearer', 'token_type');
                if ($access->expire_token_at > 0) {
                    $this->response->setData($access->expire_token_at - Date::time(), 'expires_in');
                }
                $this->response->setData($access->code, 'refresh_token');
            }
        } catch (InputValidationException $e) {
            $this->response->setStatus(false);
            $this->response->setHttpCode(400);
            $this->response->setData('invalid_request', 'error');
        }

        return $this->response;
    }

    private function handleCodeToken(array $inputs): Access
    {
        $access = (new Access())
                ->with('user')
                ->with('app')
                ->where('userpanel_oauth_accesses.app_id', $inputs['client_id']->id)
                ->where('userpanel_oauth_accesses.status', Access::ACTIVE)
                ->where('userpanel_oauth_apps.status', App::ACTIVE)
                ->where('userpanel_users.status', User::active)
                ->where('userpanel_oauth_accesses.code', $inputs['code'])
                ->getOne();
        if (!$access) {
            $this->response->setStatus(false);
            $this->response->setHttpCode(400);
            $this->response->setData('invalid_grant', 'error');

            return null;
        }
        $tokenLifetime = intval(Options::get('packages.userpanel_oauth.accesses.token_lifetime'));
        if ($tokenLifetime > 0) {
            $access->code = Password::generate(32);
        }
        $access->token = Password::generate(32);
        $access->expire_token_at = $tokenLifetime > 0 ? Date::time() + $tokenLifetime : 0;
        $access->save();

        return $access;
    }

    private function handlePasswordToken(array $inputs): Access
    {
        $p = new DB\Parenthesis();
        $p->where('email', $inputs['username']);
        $p->orwhere('cellphone', $inputs['username']);
        $user = (new User())
            ->where('status', User::active)
            ->where($p)
            ->getOne();
        if (!$user) {
            throw new InputValidationException('username');
        }
        if (!$user->password_verify($inputs['password'])) {
            $log = new \packages\userpanel\Log();
            $log->title = t('log.wrongLogin');
            $log->type = \packages\userpanel\Logs\WrongLogin::class;
            $log->user = $user->id;
            $log->parameters = [
                'user' => $user,
                'wrongpaswd' => $inputs['password'],
            ];
            $log->save();
            throw new InputValidationException('password');
        }
        $tokenLifetime = Options::get('packages.userpanel_oauth.accesses.token_lifetime');
        $access = new Access();
        $access->user_id = $user->id;
        $access->app_id = $inputs['client_id']->id;
        $access->code = Password::generate(32);
        $access->token = Password::generate(32);
        $access->create_at = Date::time();
        $access->expire_token_at = $tokenLifetime > 0 ? Date::time() + $tokenLifetime : 0;
        $access->status = Access::ACTIVE;
        $access->save();

        return $access;
    }
}
