<?php namespace JCrowe\BadWordFilter\Facades;

use Illuminate\Support\Facades\Facade;


/**
 * Class BadWordFilter
 *
 * @package JCrowe\BadWordFilter\Facades
 *
 * @see \JCrowe\BadWordFilter\BadWordFilter
 *
 * @method static bool isDirty($input)
 * @method static array|string scrub($input, $replaceWith = '*')
 * @method static array|string clean($input, $replaceWith = '*')
 * @method static array getDirtyWordsFromString($string)
 * @method static array getDirtyKeysFromArray(array $input = [])
 */
class BadWordFilter extends Facade
{

    /**
     * @return string
     */
    protected static function getFacadeAccessor() { return 'bad-word-filter'; }
}