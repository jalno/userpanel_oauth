<?php

namespace packages\userpanel_oauth;

use packages\base\DB\DBObject;
use packages\base\Packages;
use packages\userpanel\User;

class App extends DBObject
{
    public const ACTIVE = 1;
    public const DEACTIVE = 2;

    protected $dbTable = 'userpanel_oauth_apps';
    protected $primaryKey = 'id';
    protected $dbFields = [
        'name' => ['type' => 'text', 'required' => true],
        'user_id' => ['type' => 'int', 'required' => true],
        'token' => ['type' => 'text', 'required' => true, 'unique' => true],
        'secret' => ['type' => 'text', 'required' => true],
        'logo' => ['type' => 'text'],
        'ip' => ['type' => 'text'],
        'status' => ['type' => 'int', 'required' => true],
    ];
    protected $relations = [
        'user' => ['hasOne', User::class, 'user_id'],
    ];

    public function getLogoURL(): string
    {
        return Packages::package('userpanel_oauth')->url($this->logo);
    }

    /**
     * Converts object data to an associative array.
     *
     * @return array Converted data
     */
    public function toArray($recursive = null)
    {
        $result = parent::toArray(null !== $recursive ? $recursive : false);
        if ($this->logo) {
            $result['logo'] = $this->getLogoURL();
        }
        unset($result['secret']);
        if (null === $recursive) {
            $result['user'] = $this->user->toArray(false);
        }

        return $result;
    }
}
