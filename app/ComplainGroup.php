<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class ComplainGroup extends Model
{
    use Notifiable;

    protected $table = 'complain_group';
    protected $primaryKey = 'complain_group';
    public $timestamps = false;
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'complain_group'
    ];

}
