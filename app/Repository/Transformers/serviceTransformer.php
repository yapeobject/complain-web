<?php
/**
 * Created by PhpStorm.
 * User: Infelicitas
 * Date: 6/26/2018
 * Time: 2:58 PM
 */

namespace App\Repository\Transformers;


class serviceTransformer extends Transformer
{
    public function transform($user){

        $getUserServices = $user->groups()->first()->services()->where('enable', 1)->where('android', 1)->orderBy('serial')->get();

        return $getUserServices;

    }
    public function dashboard($user){
        $point = $user->point;
        $firstTime = false;

        $getUserServices = $user->groups()->first()->services()->where('enable', 1)->where('android', 1)->orderBy('serial')->get();

        return [
            'user'                  =>  $user->only('id','name','mobile_number','is_verified','is_verified'),
            //'permissions'           =>  $getUserPermissions,
            'total_points'          =>  $point ? number_format((double) $point->total, 2) : null,
            'available_points'      =>  $point ? number_format((double) $point->available, 2) : null,
            'api_token'             =>  $user->securityToken->token,
            'first_time'            =>  $firstTime,
            'services'              =>  $getUserServices
        ];
    }
}