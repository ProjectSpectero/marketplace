<?php

namespace App;

use App\Constants\UserStatus;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Laravel\Passport\HasApiTokens;
use Silber\Bouncer\Database\HasRolesAndAbilities;

class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use HasApiTokens, Authenticatable, Authorizable, HasRolesAndAbilities, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    public $searchAble = [
        'name', 'email', 'node_key'
    ];

    public function userMeta ()
    {
        return $this->hasMany(UserMeta::class);
    }

    public function backupCodes ()
    {
        return $this->hasMany(BackupCode::class);
    }

    public function nodes ()
    {
        return $this->hasMany(Node::class);
    }

    public function orders ()
    {
        return $this->hasMany(Order::class);
    }

    public function passwordResetToken ()
    {
        return $this->hasMany(PasswordResetToken::class);
    }

    public function findForPassport (String $identifier)
    {
        /** @var Builder $this */
        return $this->where('email', $identifier)
            ->where('status', '!=', UserStatus::DISABLED)
            ->first();
    }

    public function findByNodeKey (String $nodeKey, bool $throwsException = false)
    {
        /** @var Builder $predicate */
        $predicate = $this->where('node_key', $nodeKey);
        return $throwsException ? $predicate->firstOrFail() : $predicate->first();
    }
}
