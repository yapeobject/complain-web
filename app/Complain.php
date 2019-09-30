<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Complain extends Model
{
    use Notifiable;

    protected $table = 'user_complain';
    protected $primaryKey = 'complain_id';
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'complain_group','user_id','district_name','mode','category','urgency_level','longitude','latitude','user_remark','date','status'
    ];


    public function complainFile()
    {
        return $this->hasMany('App\ComplainFile', 'complain_id');
    }


}
