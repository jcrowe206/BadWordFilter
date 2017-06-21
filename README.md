BadWordFilter
=============

[![Build Status](https://travis-ci.org/jcrowe206/BadWordFilter.svg?branch=master)](https://travis-ci.org/jcrowe206/BadWordFilter) [![Coverage Status](https://coveralls.io/repos/jcrowe206/BadWordFilter/badge.svg?branch=v2.1.2&service=github)](https://coveralls.io/github/jcrowe206/BadWordFilter?branch=v2.1.2)

A bad word filter for php. Pass in a string or multidimensional array to check for the existence of a predefined list of bad words.
Use the list that ships with the application or define your own custom blacklist. BadWordFilter only matches whole words (excluding symbols)
and not partial words. This will match:

```php
$myString = "Don't be a #FOOBAR!";
$clean = BadWordFilter::clean($myString);
var_dump($clean);
// output: "Don't be a #F****R!"
```

but this will not:

```php
$myString = "I am an ASSociative professor";
$clean = BadWordFilter::clean($myString);
var_dump($clean);
// output: "I am an ASSociative professor"
```



<h3>QuickStart Guide</h3>

1) add the following to your composer.json file:

```javascript
"jcrowe/bad-word-filter": "2.2.*"
```

2) Run composer install

```bash
composer install
```

3) Add BadWordFilter to your providers array and create an alias to the facade in app.php


```php
$providers = array(
   ...
   ...
   'JCrowe\BadWordFilter\Providers\BadWordFilterServiceProvider',
),

$aliases = array(
    ...
    ...
    'BadWordFilter'	  => 'JCrowe\BadWordFilter\Facades\BadWordFilter',
),
```


4) start cleaning your inputs~

```php
$cleanString = BadWordFilter::clean("my cheesy string");
var_dump($cleanString);
// output: "my c****y string"
```

<h5>INPORTANT NOTE<h5>
<strong>BadWordFilter does not and never will prevent XSS or SQL Injection. Take the proper steps in your code to sanitize all user input before
storing to a database or displaying to the client.</strong>


<h3>Settings options</h3>

BadWordFilter takes 4 options:

```php
$options = array(
    'source' => 'file',
    'source_file' => __DIR__ . '/bad_words.php',
    'strictness' => 'very_strict',
    'also_check' => array(),
);
```

<h6>Source Types</h6>

<strong>File</strong>

If you specify a source type of "file" you must also specify a source_file or use the default source file included with this package.
The Source File must return an array of words to check for. If you wish to specify strictness level in your custom bad words list simply
split your array into sub keys of 'permissive', 'lenient', 'strict', 'very_strict', 'strictest', 'misspellings'

<strong>Array</strong>

If you specify a source type of "array" you must also specify a "bad_words_array" key that contains a list of words to check for.

<h6>Strictness</h6>

Available options are:
"permissive",
"lenient",
"strict",
"very_strict",
"strictest",
"misspellings"

Where permissive will allow all but the worst of words through and strictest will attempt to flag even the most G rated words.
Mispellings will also check for common misspellings and/or leet-speak. A full list of words can be seen in the src/config/bad_words.php file in this repo.


<h6>Also Check</h6>

In addition to the default list specified in the config file or array you can also pass in an "also_check" key that contains an array of words
to flag.



<h3>Overriding Defaults</h3>

You can override the default settings in the constructor if using the class as an instance, or as an optional parameter in the static method call

```php
$myOptions = array('strictness' => 'permissive', 'also_check' => array('foobar'));
$filter = new \JCrowe\BadWordFilter\BadWordFilter($myOptions);

$cleanString = $filter->clean('Why did you FooBar my application?');
var_dump($cleanString);
// output: "Why did you F****r my application?"
```


<h3>How to handle bad words</h3>

By default bad words will be replaced with the first letter followed by the requisite number of asterisks and then the last letter. Ie:
"Cheese" would become "C****e"

This can be changed to be replaced with a set string by passing the new string as an argument to the "clean" method

```php
$myOptions = array('also_check' => array('cheesy'));
$cleanString = BadWordFilter::clean("my cheesy string", '#!%^", $myOptions);
var_dump($cleanString);
// output: "my #!%^ string"
```

or

```php
$myOptions = array('also_check' => array('cheesy'));
$filter = new \JCrowe\BadWordFilter\BadWordFilter($myOptions);
$cleanString = $filter->clean("my cheesy string", "#!$%");
var_dump($cleanString);
// output: "my #!$% string"
```

In case you want to keep bad word and surround it by anything (ex. html tag):

```php
$myOptions = array('also_check' => array('cheesy'));
$filter = new \JCrowe\BadWordFilter\BadWordFilter($myOptions);
$cleanString = $filter->clean("my cheesy string", '<span style="color: red;">$0</span>');
var_dump($cleanString);
// output: "my <span style="color: red;">cheesy</span> string"
```

<h3>Full method list</h3>

<h6>isDirty</h6>
<strong>Check if a string or an array contains a bad word</strong>

Params:
  $input - required - array|string

Return:
  Boolean


Usage:

```php
$filter = new \JCrowe\BadWordFilter\BadWordFilter();

if ($filter->isDirty(array('this is a dirty string')) {
    /// do something
}
```


<h6>clean</h6>
<strong>
    Clean bad words from a string or an array. By default bad words are replaced with asterisks with the exception of the first and last letter.
    Optionally you can specify a string to replace the words with
</strong>

Params:
    $input - required - array|string
    $replaceWith - optional - string

Return:
    Cleaned array or string

Usage:
```php
$filter = new \JCrowe\BadWordFilter\BadWordFilter();
$string = "this really bad string";
$cleanString = $filter->clean($string);
```


<h6>STATIC clean</h6>
<strong>
    Static wrapper around the "clean" method.
</strong>

Params:
    $input - required - array|string
    $replaceWith - optional - string
    $options - optional - array

Return:
    Cleaned array or string

Usage:
```php
$string = "this really bad string";
$cleanString = BadWordFilter::clean($string);
```


<h6>getDirtyWordsFromString</h6>
<strong>Return the matched dirty words</strong>

Params:
    $input - required - string

Return:
    Boolean


Usage:

```php
$filter = new \JCrowe\BadWordFilter\BadWordFilter();
if ($badWords = $filter->getDirtyWordsFromString("this really bad string")) {
    echo "You said these bad words: " . implode("<br />", $badWords);
}
```


<h6>getDirtyKeysFromArray</h6>
<strong>After checking an array using the isDirty method you can access the bad keys by using this method</strong>

Params : none

Return:
    String - dot notation of array keys

Usage:

```php
$arrayToCheck = array(
    'first' => array(
        'bad' => array(
            'a' => 'This is a bad string!',
            'b' => 'This is a good string!',
        ),
    ),
    'second' => 'bad bad bad string!',
);

$filter = new \JCrowe\BadWordFilter\BadWordFilter();

if ($badKeys = $filter->getDirtyKeysFromArray($arrayToCheck)) {

    var_dump($badKeys);
    /* output:

        array(
            0 => 'first.bad.a',
            1 => 'second'
        );
    */
}
```

