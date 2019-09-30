<?php
/**
 * Created by PhpStorm.
 * User: Infelicitas
 * Date: 6/26/2018
 * Time: 2:58 PM
 */

namespace App\Repository\Transformers;


class userTransformer extends Transformer
{
    public function transform($user){
        $user->profile;
        $point = $user->point;
        return [
            'user'                  =>  $user
        ];

    }
}