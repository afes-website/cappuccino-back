<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class User extends Model implements AuthenticatableContract, AuthorizableContract {

    use Authenticatable, Authorizable, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'password',
        'session_key',
        'perm_admin',
        'perm_reservation',
        'perm_executive',
        'perm_exhibition',
        'perm_teacher',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'session_key',
    ];

    protected $primaryKey = 'id';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    const VALID_PERMISSION_NAMES = [
        "admin",
        "reservation",
        "executive",
        "exhibition",
        "teacher",
    ];

    public static function findOrFail(string $id, $http_code = 404) {
        $user = self::find($id);
        if (!$user) abort($http_code, 'USER_NOT_FOUND');
        return $user;
    }

    public function hasPermission($perm_name) {
        if (!in_array($perm_name, self::VALID_PERMISSION_NAMES))
            throw new \Exception('invalid permission name');

        return ($this->{'perm_' . $perm_name} == 1); // weak comparison because of string
    }

    public function exhibition() {
        return $this->hasOne(Exhibition::class, 'id');
    }
}
