<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class UserType extends Model
{
    use Notifiable;

    protected $table = 'user_type';
    protected $primaryKey = 'user_type_id';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_type_id'
    ];

}
