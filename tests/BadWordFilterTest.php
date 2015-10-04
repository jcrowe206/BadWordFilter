<?php

namespace JCrowe\BadWordFilter\Tests;

use JCrowe\BadWordFilter\BadWordFilter;
use PHPUnit_Framework_TestCase as TestCase;

class BadWordFilterTest extends TestCase {


    /**
     * Test that you can clean an html wrapped string and return html that
     * has not been replaced with '*' as per bug report
     * https://github.com/jcrowe206/BadWordFilter/issues/2
     */
    public function testHtmlWrapper()
    {
        $filter = new BadWordFilter(['also_check' => ['bad word']]);

        static::assertEquals('<h3>b******d</h3>some text', $filter->clean('<h3>bad word</h3>some text'));
    }


    /**
     * Default cleaning works
     */
    public function testBadWordsAreCleaned()
    {
        $filter = new BadWordFilter();

        static::assertEquals('s**t', $filter->clean('shit'));
        static::assertEquals('f**k', $filter->clean('fuck'));
        static::assertEquals('d******d', $filter->clean('dickhead'));
        static::assertEquals('a**', $filter->clean('ass'));
    }


    /**
     * Should prefer the supplied replacement string instead of asterisks
     */
    public function testCustomReplace()
    {
        $filter = new BadWordFilter(['also_check' => ['replace me']]);
        $replaceWith = '#!<>*&';

        static::assertEquals($replaceWith, $filter->clean('replace me', $replaceWith));
    }


    /**
     * Words that have special characters touching them should be treated
     * the same as words with spaces surrounding them
     */
    public function testSpecialCharactersAreIgnored()
    {
        $filter = new BadWordFilter(['also_check' => ['replace me']]);

        static::assertEquals('#r********e', $filter->clean('#replace me'));
        static::assertEquals('^r********e', $filter->clean('^replace me'));
        static::assertEquals('%r********e', $filter->clean('%replace me'));
        static::assertEquals('$r********e', $filter->clean('$replace me'));
        static::assertEquals('@r********e', $filter->clean('@replace me'));
        static::assertEquals('!r********e', $filter->clean('!replace me'));
        static::assertEquals('r********e!', $filter->clean('replace me!'));
        static::assertEquals('(r********e)', $filter->clean('(replace me)'));
        static::assertEquals('<r********e>', $filter->clean('<replace me>'));
    }

    /**
     * Words that contain bad words should not match
     */
    public function testPartialMatchesDontGetCleaned()
    {
        $filter = new BadWordFilter();
        $myString = 'I am an ASSociative professor';

        static::assertEquals($myString, $filter->clean($myString));
    }


    /**
     * Different words should be flagged based on the strictness level
     */
    public function testChangingStrictnessChangesWhichWordsAreCaught()
    {
        // very_strict
        $filter = new BadWordFilter();

        // misspellings
        static::assertEquals('ahole', $filter->clean('ahole'));
        // strictest
        static::assertEquals('anus', $filter->clean('anus'));
        // very_strict
        static::assertEquals('d***o', $filter->clean('dildo'));
        // lenient
        static::assertEquals('b***h', $filter->clean('bitch'));
        // permissive
        static::assertEquals('c**k', $filter->clean('cock'));

        $filter = new BadWordFilter(['strictness' => 'misspellings']);

        static::assertEquals('a***e', $filter->clean('ahole'));
        static::assertEquals('a**s', $filter->clean('anus'));
        static::assertEquals('d***o', $filter->clean('dildo'));
        static::assertEquals('b***h', $filter->clean('bitch'));
        static::assertEquals('c**k', $filter->clean('cock'));

        $filter = new BadWordFilter(['strictness' => 'strictest']);

        static::assertEquals('ahole', $filter->clean('ahole'));
        static::assertEquals('a**s', $filter->clean('anus'));
        static::assertEquals('d***o', $filter->clean('dildo'));
        static::assertEquals('b***h', $filter->clean('bitch'));
        static::assertEquals('c**k', $filter->clean('cock'));

        $filter = new BadWordFilter(['strictness' => 'very_strict']);

        static::assertEquals('ahole', $filter->clean('ahole'));
        static::assertEquals('anus', $filter->clean('anus'));
        static::assertEquals('d***o', $filter->clean('dildo'));
        static::assertEquals('b***h', $filter->clean('bitch'));
        static::assertEquals('c**k', $filter->clean('cock'));


        $filter = new BadWordFilter(['strictness' => 'lenient']);

        static::assertEquals('ahole', $filter->clean('ahole'));
        static::assertEquals('anus', $filter->clean('anus'));
        static::assertEquals('dildo', $filter->clean('dildo'));
        static::assertEquals('b***h', $filter->clean('bitch'));
        static::assertEquals('c**k', $filter->clean('cock'));

        $filter = new BadWordFilter(['strictness' => 'permissive']);

        static::assertEquals('ahole', $filter->clean('ahole'));
        static::assertEquals('anus', $filter->clean('anus'));
        static::assertEquals('dildo', $filter->clean('dildo'));
        static::assertEquals('bitch', $filter->clean('bitch'));
        static::assertEquals('c**k', $filter->clean('cock'));
    }


    /**
     * Should be able to determine if a string has filth in it
     */
    public function testIsDirtyFindsDirtyString()
    {
        $filter = new BadWordFilter();

        static::assertFalse($filter->isDirty('my very clean string'));
        static::assertTrue($filter->isDirty('my very fucking dirty string'));
    }


    /**
     * able to get a list of dirty words that are in a string
     */
    public function testCanGetListOfDirtyWordsFromString()
    {
        $filter = new BadWordFilter();

        static::assertEquals([
            'fucking',
        ], $filter->getDirtyWordsFromString('my very fucking dirty string'));

        static::assertEquals([
            'fucking',
            'shitty'
        ], $filter->getDirtyWordsFromString('my very fucking shitty dirty string'));
    }


    /**
     * Can parse an array and get list of dirty strings and their array key
     */
    public function testCanGetListOfDirtyWordsFromArray()
    {
        $filter = new BadWordFilter();

        static::assertEquals([
                '1',
                '2',
                'filth',
        ], $filter->getDirtyKeysFromArray(['this is a clean string', 'this shit is dirty', 'fuck yo couch', 'actually that is a nice couch!', 'filth' => 'another shitty string']));
    }


    /**
     * Should be able to access bad keys in a multidimensional array
     */
    public function testCanGetListOfDirtyWordsFromMultidimensionalArray()
    {
        $filter = new BadWordFilter();

        static::assertEquals([
            'filth.dirty',
            'filth.clean.1',
        ], $filter->getDirtyKeysFromArray([
            'filth' => [
                'dirty' => 'this shit is dirty',
                'clean' => [
                    'this one is clean',
                    'fuck it I lied, this one is dirty'
                ]
            ]
        ]));
    }


    /**
     * Should receive a cleaned array from the filter
     */
    public function testCanCleanADirtyArray()
    {
        $filter = new BadWordFilter();

        $cleanedString = $filter->clean([
            'filth' => [
                'dirty' => 'this shit is dirty',
                'clean' => [
                    'this one is clean',
                    'fuck it I lied, this one is dirty'
                ]
            ]
        ]);

        static::assertEquals([
            'filth' => [
                'dirty' => 'this s**t is dirty',
                'clean' => [
                    'this one is clean',
                    'f**k it I lied, this one is dirty'
                ]
            ]
        ], $cleanedString);
    }


    /**
     * Using a custom bad words array should ignore the default
     * bad words list and strictly look for those words that
     * have been included in the 'bad_words_array' option
     */
    public function testUsingCustomArrayOfFilth()
    {
        $options = [
            'source' => 'array',
            'bad_words_array' => [
                'bad',
                'ugly',
                'mean'
            ]

        ];

        $filter = new BadWordFilter($options);

        static::assertEquals('this is a b** string that has u**y and m**n words in it. fuck.', $filter->clean('this is a bad string that has ugly and mean words in it. fuck.'));
    }
}