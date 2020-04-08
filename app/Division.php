<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Division extends Model
{
    use Notifiable;

    protected $table = 'division';
    protected $primaryKey = 'division_name';
    public $timestamps = false;
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'division_name'
    ];

}
