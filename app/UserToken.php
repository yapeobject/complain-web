<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class UserToken extends Model
{
    use Notifiable;

    protected $table = 'token';
    protected $primaryKey = 'user_id';
    public $timestamps = false;
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'token', 'expiry', 'status'
    ];

}
