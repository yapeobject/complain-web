<?php
/**
 * Created by PhpStorm.
 * User: Infelicitas
 * Date: 6/26/2018
 * Time: 2:58 PM
 */
namespace App\Repository\Transformers;
class authTransformer extends Transformer
{
    public function transform($user){
        $getUserPermissions = $user->groups()->first()->permissions()->get()->pluck('name');
        $getUserServices = $user->groups()->first()->services()->where('enable', 1)->where('android', 1)->orderBy('serial')->get();
        $point = $user->point;

        $token = uniqid('', true).'.'.$user->id.'.'.uniqid('', true);
        if ($user->securityToken()->first()) {
            $firstTime = false;
            $user->securityToken()->update(['token' => $token]);
        } else {
            $firstTime = true;
            $user->securityToken()->create(['token' => $token]);
        }
        $userInfo = [
            'id'            =>  $user['id'],
            'name'          =>  $user['name'],
            'mobile_number' =>  $user['mobile_number'],
            'is_verified'   =>  $user['is_verified'],
            'email'         =>  $user['profile']['email']
        ];
        return [
            'user'                  =>  $userInfo,
            //'permissions'           =>  $getUserPermissions,
            'total_points'          =>  $point ? number_format((double) $point->total, 2) : null,
            'available_points'      =>  $point ? number_format((double) $point->available, 2) : null,
            'api_token'             =>  $user->securityToken->token,
            'first_time'            =>  $firstTime,
            'services'              =>  $getUserServices
        ];
    }
}