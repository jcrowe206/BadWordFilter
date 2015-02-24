<?php namespace JCrowe\BadWordFilter\Facades;

use Illuminate\Support\Facades\Facade;

class BadWordFilter extends Facade
{

    /**
     * @return string
     */
    protected static function getFacadeAccessor() { return 'bad-word-filter'; }
}