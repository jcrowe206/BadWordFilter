<?php namespace JCrowe\BadWordFilter\Facades;

use JCrowe\BadWordFilter\BadWordFilter as Filter;

class BadWordFilter extends \Facades
{

    /**
     * @return string
     */
    protected static function getFacadeAccessor() { return 'BadWordFilter'; }


    /**
     * Static accessor
     *
     * @param $input
     * @param string $replaceWith
     * @param array $options
     * @return mixed
     */
    public static function clean($input, $replaceWith = "*", array $options = []) {
        return (new Filter($options))->clean($input, $replaceWith);
    }
}