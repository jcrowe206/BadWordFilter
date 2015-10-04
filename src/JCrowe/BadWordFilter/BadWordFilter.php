<?php

namespace JCrowe\BadWordFilter;

class BadWordFilter
{


    /**
     * The default configurations for this package
     *
     * @var array|mixed
     */
    private $defaults = [];


    /**
     * All the configurations for this package. Created by merging user provided configurations
     * with the default configurations
     *
     * @var array
     */
    private $config = [];

    /**
     * Manages state of the object, if we are using a custom
     * word list this will be set to true
     *
     * @var bool
     */
    private $isUsingCustomDefinedWordList = false;


    /**
     * A list of bad words to check for
     *
     * @var array
     */
    private $badWords = [];


    /**
     * The start of the regex we will build to check for bad word matches
     *
     * @var string
     */
    private $regexStart = '/\b([-!$%^&*()_+|~=`{}\[\]:";\'?,.\/])?';


    /**
     * The end of the regex we ill build to check for bad word matches
     *
     * @var string
     */
    private $regexEnd = '([-!$%^&*()_+|~=`{}\[\]:\";\'?,.\/])?\b/i';


    /**
     * Create the object and set up the bad words list and
     *
     * @param array $options
     *
     * @throws \Exception
     */
    public function __construct(array $options = [])
    {
        $this->defaults = include __DIR__ . '/../../config/config.php';

        if ($this->hasAlternateSource($options) || $this->hasAlternateSourceFile($options)) {

            $this->isUsingCustomDefinedWordList = true;
        }

        $this->config = array_merge($this->defaults, $options);

        $this->getBadWords();
    }


    /**
     * Check if the provided $input contains any bad words
     *
     * @param string|array $input
     *
     * @return bool
     */
    public function isDirty($input)
    {
        return is_array($input) ? $this->isADirtyArray($input) : $this->isADirtyString($input);
    }


    /**
     * Clean the provided $input and return the cleaned array or string
     *
     * @param string|array $input
     * @param string $replaceWith
     *
     * @return array|string
     */
    public function scrub($input, $replaceWith = '*')
    {
        return is_array($input) ? $this->cleanArray($input, $replaceWith) : $this->cleanString($input, $replaceWith);
    }


    /**
     * Clean the $input (array or string) and replace bad words with $replaceWith
     *
     * @param $input
     * @param string $replaceWith
     * @param array $options
     *
     * @return array|string
     */
    public function clean($input, $replaceWith = '*')
    {
        return $this->scrub($input, $replaceWith);
    }


    /**
     * Get dirty words from the provided $string as an array of bad words
     *
     * @param $string
     *
     * @return array
     */
    public function getDirtyWordsFromString($string)
    {
        $badWords = [];
        $wordsToTest = $this->flattenArray($this->badWords);

        foreach ($wordsToTest as $word) {

            $word = preg_quote($word);

            if (preg_match($this->buildRegex($word), $string, $matchedString)) {

                $badWords[] = $matchedString[0];
            }
        }

        return $badWords;
    }


    /**
     * Get an array of key/value pairs of dirty keys in the $input array
     *
     * @param array $input
     *
     * @return array
     */
    public function getDirtyKeysFromArray(array $input = [])
    {
        return $this->findBadWordsInArray($input);
    }

    /**
     * Create the regular expression for the provided $word
     *
     * @param $word
     *
     * @return string
     */
    private function buildRegex($word)
    {
        return $this->regexStart . '(' . $word . ')' . $this->regexEnd;
    }


    /**
     * Check if the current model is set up to use a custom defined word list
     *
     * @return bool
     */
    private function isUsingCustomDefinedWordList()
    {
        return $this->isUsingCustomDefinedWordList;
    }


    /**
     * Check if the $input array is dirty or not
     *
     * @param array $input
     * @param bool $previousKey
     *
     * @return bool
     */
    private function isADirtyArray(array $input)
    {
        return $this->findBadWordsInArray($input) ? true : false;
    }


    /**
     * Return an array of bad words that were found in the $input array along with their keys
     *
     * @param array $input
     * @param bool $previousKey
     *
     * @return array
     */
    private function findBadWordsInArray(array $input = [], $previousKey = false)
    {
        $dirtyKeys = [];

        foreach ($input as $key => $value) {

            // create the "dot" notation keys
            if ($previousKey !== false) {
                $key = $previousKey . '.' . $key;
            }

            if (is_array($value)) {

                // call recursively to handle multidimensional array,
                $dirtyKeys[] = $this->findBadWordsInArray($value, $key);

            } else {
                if (is_string($value)) {

                    if ($this->isADirtyString($value)) {

                        // bad word found, add the current key to the dirtyKeys array
                        $dirtyKeys[] = (string) $key;

                    }

                } else {
                    continue;
                }
            }
        }

        return $this->flattenArray($dirtyKeys);
    }


    /**
     * Clean all the bad words from the input $array
     *
     * @param $array
     * @param $replaceWith
     *
     * @return mixed
     */
    private function cleanArray(array $array = [], $replaceWith)
    {
        $dirtyKeys = $this->findBadWordsInArray($array);

        foreach ($dirtyKeys as $key) {

            $this->cleanArrayKey($key, $array, $replaceWith);
        }

        return $array;
    }


    /**
     * Clean the string stored at $key in the $array
     *
     * @param $key
     * @param $array
     * @param $replaceWith
     *
     * @return mixed
     */
    private function cleanArrayKey($key, &$array, $replaceWith)
    {
        $keys = explode('.', $key);

        foreach ($keys as $k) {

            $array = &$array[$k];
        }

        return $array = $this->cleanString($array, $replaceWith);
    }


    /**
     * Clean the input $string and replace the bad word with the $replaceWith value
     *
     * @param $string
     * @param $replaceWith
     *
     * @return mixed
     */
    private function cleanString($string, $replaceWith)
    {
        $words = $this->getDirtyWordsFromString($string);

        if ($words) {

            foreach ($words as $word) {

                if (!strlen($word)) {

                    continue;
                }

                if ($replaceWith === '*') {

                    $fc = $word[0];
                    $lc = $word[strlen($word) - 1];
                    $len = strlen($word);

                    $newWord = $len > 3 ? $fc . str_repeat('*', $len - 2) . $lc : $fc . '**';

                } else {

                    $newWord = $replaceWith;
                }

                $string = preg_replace("/$word/", $newWord, $string);
            }
        }

        return $string;
    }


    /**
     * Check if the $input parameter is a dirty string
     *
     * @param $input
     *
     * @return bool
     */
    private function isADirtyString($input)
    {
        return $this->strContainsBadWords($input);
    }


    /**
     * Check if the input $string contains bad words
     *
     * @param $string
     *
     * @return bool
     */
    private function strContainsBadWords($string)
    {
        return $this->getDirtyWordsFromString($string) ? true : false;
    }


    /**
     * Set the bad words array to the model if not already set and return it
     *
     * @return array|void
     * @throws \Exception
     */
    private function getBadWords()
    {
        if (!$this->badWords) {

            switch ($this->config['source']) {

                case 'file':
                    $this->badWords = $this->getBadWordsFromConfigFile();
                    break;

                case 'array':
                    $this->badWords = $this->getBadWordsFromArray();
                    break;

                case 'database':
                    $this->badWords = $this->getBadWordsFromDB();
                    break;

                default:
                    throw new \Exception('Config source was not a valid type. Valid types are: file, database, cache');
                    break;
            }

            if (!$this->isUsingCustomDefinedWordList()) {

                switch ($this->config['strictness']) {

                    case 'permissive':
                        $this->badWords = $this->getBadWordsByKey(['permissive']);
                        break;

                    case 'lenient':
                        $this->badWords = $this->getBadWordsByKey(['permissive', 'lenient']);
                        break;

                    case 'strict':
                        $this->badWords = $this->getBadWordsByKey(['permissive', 'lenient', 'strict']);
                        break;

                    case 'very_strict':
                        $this->badWords = $this->getBadWordsByKey(['permissive', 'lenient', 'strict', 'very_strict']);
                        break;

                    case 'strictest':
                        $this->badWords = $this->getBadWordsByKey([
                            'permissive',
                            'lenient',
                            'strict',
                            'very_strict',
                            'strictest'
                        ]);
                        break;

                    case 'misspellings':
                    case 'all':
                        $this->badWords = $this->getBadWordsByKey([
                            'permissive',
                            'lenient',
                            'strict',
                            'very_strict',
                            'strictest',
                            'misspellings'
                        ]);
                        break;

                    default:
                        $this->badWords = $this->getBadWordsByKey(['permissive', 'lenient', 'strict', 'very_strict']);
                        break;

                }
            }

            if (!empty($this->config['also_check'])) {

                if (!is_array($this->config['also_check'])) {

                    $this->config['also_check'] = [$this->config['also_check']];
                }

                $this->badWords = array_merge($this->badWords, $this->config['also_check']);
            }
        }

        return $this->badWords;
    }


    /**
     * Get subset of the bad words by an array of $keys
     *
     * @param array $keys
     *
     * @return array
     */
    private function getBadWordsByKey(array $keys)
    {
        $bw = [];
        foreach ($keys as $key) {
            if (!empty($this->badWords[$key])) {
                $bw[] = $this->badWords[$key];
            }
        }

        return $bw;
    }


    /**
     * Get the bad words list from a config file
     *
     * @return array
     * @throws \Exception
     */
    private function getBadWordsFromConfigFile()
    {
        if (file_exists($this->config['source_file'])) {
            return include $this->config['source_file'];
        }

        throw new \Exception('Source was config but the config file was not set or contained an invalid path. Tried looking for it at: ' . $this->config['source_file']);
    }


    /**
     * Get the bad words from the array in the config
     *
     * @return array
     * @throws \Exception
     */
    private function getBadWordsFromArray()
    {
        if (!empty($this->config['bad_words_array']) && is_array($this->config['bad_words_array'])) {
            return $this->config['bad_words_array'];
        }

        throw new \Exception('Source is set to "array" but bad_words_array is either empty or not an array.');
    }


    /**
     * Get bad words from the database - not yet supported
     *
     * @throws \Exception
     */
    private function getBadWordsFromDB()
    {
        throw new \Exception('Bad words from db is not yet supported. If you would like to see this feature please consider submitting a pull request.');
    }


    /**
     * Flatten the input $array
     *
     * @param $array
     *
     * @return mixed
     */
    private function flattenArray($array)
    {
        $objTmp = (object)['aFlat' => []];

        array_walk_recursive($array, create_function('&$v, $k, &$t', '$t->aFlat[] = $v;'), $objTmp);

        return $objTmp->aFlat;
    }


    /**
     * @param array $options
     *
     * @return bool
     */
    private function hasAlternateSource(array $options)
    {
        return !empty($options['source']) && $options['source'] !== $this->defaults['source'];
    }


    /**
     * @param array $options
     *
     * @return bool
     */
    private function hasAlternateSourceFile(array $options)
    {
        return !empty($options['source_file']) && $options['source_file'] !== $this->defaults['source_file'];
    }

}