<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

// use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'ms_users';
    protected $primaryKey = 'USER_ID';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    // protected $fillable = [
    //     'name',
    //     'email',
    //     'password',
    // ];
    protected $fillable = [
        'ROLE_ID',
        'UUID',
        'ALIASES',
        'USERNAME',
        'PASSWORD',
        'REMEMBER_TOKEN',
        'EMAIL',
        'EMAIL_VERIFIED_AT',
        'STATUS',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'PASSWORD',
        'REMEMBER_TOKEN',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'EMAIL_VERIFIED_AT' => 'datetime',
    ];

    /**
     * Set Password Column
     */
    public function getAuthPassword()
    {
        return $this->PASSWORD;
    }

    /**
     * Set Username Column
     */
    public function username()
    {
        return 'USERNAME';
    }
}
