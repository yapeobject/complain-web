<?php
/**
 * Created by PhpStorm.
 * User: Infelicitas
 * Date: 6/26/2018
 * Time: 2:58 PM
 */

namespace App\Repository\Transformers;


class rateTransformer extends Transformer
{
    public function transform($user){

        $getUserServices = $user->groups()->first()->services()->where('enable', 1)->where('android', 1)->orderBy('serial')->get();

        return $getUserServices;

    }
}