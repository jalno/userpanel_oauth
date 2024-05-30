<?php

namespace packages\userpanel_oauth;

use packages\base\DB\DBObject;
use packages\userpanel\User;

class Access extends DBObject
{
    public const ACTIVE = 1;
    public const DEACTIVE = 2;

    protected $dbTable = 'userpanel_oauth_accesses';
    protected $primaryKey = 'id';
    protected $dbFields = [
        'user_id' => ['type' => 'int', 'required' => true],
        'app_id' => ['type' => 'int', 'required' => true],
        'code' => ['type' => 'text', 'required' => true, 'unique' => true],
        'token' => ['type' => 'text', 'required' => true, 'unique' => true],
        'create_at' => ['type' => 'int', 'required' => true],
        'lastip' => ['type' => 'text'],
        'lastuse_at' => ['type' => 'int'],
        'expire_token_at' => ['type' => 'int'],
        'status' => ['type' => 'int', 'required' => true],
    ];

    protected $relations = [
        'user' => ['hasOne', User::class, 'user_id'],
        'app' => ['hasOne', App::class, 'app_id'],
    ];

    /**
     * Converts object data to an associative array.
     *
     * @return array Converted data
     */
    public function toArray($recursive = null)
    {
        $result = parent::toArray(null !== $recursive ? $recursive : false);
        unset($result['code'], $result['token']);
        if (null === $recursive) {
            $result['user'] = $this->user->toArray(false);
            $result['app'] = $this->app->toArray(false);
        }

        return $result;
    }
}
