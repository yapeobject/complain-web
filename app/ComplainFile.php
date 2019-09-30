<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class ComplainFile extends Model
{
    use Notifiable;

    protected $table = 'complain_file';
    protected $primaryKey = 'complain_id';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'complain_id', 'file_location', 'complain_file_type'
    ];

}
