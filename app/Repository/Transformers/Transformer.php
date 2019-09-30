<?php
/**
 * Created by PhpStorm.
 * User: Infelicitas
 * Date: 6/24/2018
 * Time: 2:36 PM
 */

namespace App\Repository\Transformers;


abstract class Transformer
{
    /*
     * Transforms a collection of lessons
     * @param $lessons
     * @return array
     */
    public function transformCollection(array $items){

        return array_map([$this, 'transform'], $items);

    }

    public abstract function transform($item);
}