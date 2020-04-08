<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class District extends Model
{
    use Notifiable;

    protected $table = 'district';
    protected $primaryKey = 'district_name';
    public $timestamps = false;
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'district_name', 'division_name', 'complain_notify_email'
    ];

}
