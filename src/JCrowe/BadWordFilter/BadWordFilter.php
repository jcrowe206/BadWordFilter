<?php

namespace JCrowe\BadWordFilter;

class BadWordFilter {

    private $defaults = array();
    private $config = array();
    private $isUsingCustomDefinedWordList = false;

    private $dirtyKeys = array();
    private $badWords = array();

    private $regexStart = '/\b([-!$%^&*()_+|~=`{}\[\]:";\'<>?,.\/])?';
    private $regexEnd = '([-!$%^&*()_+|~=`{}\[\]:\";\'<>?,.\/])?\b/i';

    public function __construct($options = array()) {
        $this->defaults = include __DIR__ . '/../../config/config.php';
        if (
            !empty($options['source']) && $options['source'] !== $this->defaults['source']
            || !empty($options['source_file']) && $options['source_file'] !== $this->defaults['source_file']) {
            $this->isUsingCustomDefinedWordList = true;
        }
        $this->config = array_merge($this->defaults, $options);

        $this->getBadWords();
    }


    public function isDirty($input) {
        if (is_array($input)) {
            $this->dirtyKeys = array();
            return $this->isADirtyArray($input);
        } else {
            return $this->isADirtyString($input);
        }
    }

    public function scrub($input, $replaceWith = '*') {
        if (is_array($input)) {
            $this->dirtyKeys = array();
            return $this->cleanArray($input, $replaceWith);
        } else if (is_string($input)) {
            return $this->cleanString($input, $replaceWith);
        } else {
            throw new \Exception('Can only clean arrays and strings at this time.');
        }
    }

    public static function clean($input, $replaceWith = '*', $options = array()) {
        $instance = new self($options);
        return $instance->clean($input);
    }

    public function getDirtyKeys() {
        return $this->dirtyKeys;
    }

    public function getDirtyWordsFromString($string) {
        $badWords = array();
        $wordsToTest = $this->flattenArray($this->badWords);

        foreach ($wordsToTest as $word) {
            $word = preg_quote($word);
            if (preg_match($this->regexStart . "(" . $word . ")" . $this->regexEnd, $string, $matchedString)) {
                $badWords[] = $matchedString[0];
            }
        }

        return !empty($badWords) ? $badWords : false;
    }

    private function isUsingCustomDefinedWordList() {
        return $this->isUsingCustomDefinedWordList;
    }

    private function isADirtyArray(array $input, $previousKey = false) {
        foreach ($input as $key => $value) {
            if ($previousKey !== false) {
                $key = $previousKey . '.' . $key;
            }
            if (is_array($value)) {
                $this->isADirtyArray($value, $key);
            } else if (is_string($value)) {
                if ($this->isADirtyString($value)) {
                    $this->dirtyKeys[] = array('key' => (string) $key, 'value' => $value);
                }
            } else {
                continue;
            }
        }

        return !empty($this->dirtyKeys);

    }

    private function cleanArray($array, $replaceWith) {
        $this->dirtyKeys = array();

        if ($this->isADirtyArray($array)) {
            foreach ($this->getDirtyKeys() as $key) {
                $this->cleanArrayKey($key, $array, $replaceWith);
            }
        }

        return $array;
    }

    private function cleanArrayKey($key, &$array, $replaceWith) {
        $keys = explode('.', $key['key']);
        foreach ($keys as $k) {
            $array = &$array[$k];
        }
        return $array = $this->cleanString($array, $replaceWith);
    }

    private function cleanString($string, $replaceWith) {
        $words = $this->getDirtyWordsFromString($string);

        if ($words) {
            foreach ($words as $word) {
                if (!strlen($word)) {
                    continue;
                }
                if ($replaceWith == '*') {

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

    private function isADirtyString($input) {
        $passes = !$this->strContainsBadWords($input);

        return !$passes;
    }

    private function strContainsBadWords($string) {
        if ($this->getDirtyWordsFromString($string)) {
            return true;
        }

        return false;
    }

    private function getBadWords() {
        if (empty($this->badWords)) {
            switch ($this->config['source']) {
                case "config":
                    $this->badWords = $this->getBadWordsFromConfigFile();
                    break;
                case "array":
                    $this->badWords = $this->getBadWordsFromArray();
                    break;
                case "database":
                    $this->badWords = $this->getBadWordsFromDB();
                    break;
                default:
                    throw new \Exception('Config source was not a valid type. Valid types are: file, database, cache');
                    break;
            }

            if (!$this->isUsingCustomDefinedWordList()) {
                switch ($this->config['strictness']) {
                    case "permissive":
                        $this->badWords = $this->getBadWordsByKey(array('permissive'));
                        break;
                    case "lenient":
                        $this->badWords = $this->getBadWordsByKey(array('permissive', 'lenient'));
                        break;
                    case "strict":
                        $this->badWords = $this->getBadWordsByKey(array('permissive', 'lenient', 'strict'));
                        break;
                    case "very_strict":
                        $this->badWords = $this->getBadWordsByKey(array('permissive', 'lenient', 'strict', 'very_strict'));
                        break;
                    case "strictest":
                        $this->badWords = $this->getBadWordsByKey(array('permissive', 'lenient', 'strict', 'very_strict', 'strictest'));
                        break;
                    case "misspellings":
                    case "all":
                        $this->badWords = $this->getBadWordsByKey(array('permissive', 'lenient', 'strict', 'very_strict', 'strictest', 'misspellings'));
                        break;
                    default:
                        throw new \Exception('You must specify a strictness level if you are not using custom bad word lists.');
                        break;
                }
            }

            if (!empty($this->config['also_check'])) {
                if (!is_array($this->config['also_check'])) {
                    throw new \Exception('also_check must be an array.');
                } else {
                    $this->badWords = array_merge($this->badWords, $this->config['also_check']);
                }
            }
        }

        return $this->badWords;
    }

    private function getBadWordsByKey(array $keys) {
        $bw = array();
        foreach ($keys as $key) {
            if (!empty($this->badWords[$key])) {
                $bw[] = $this->badWords[$key];
            }
        }
        return $bw;
    }

    private function getBadWordsFromConfigFile() {
        if (file_exists($this->config['source_file'])) {
            return include $this->config['source_file'];
        }

        throw new \Exception('Source was config but the config file was not set or contained an invalid path. Tried looking for it at: ' . $this->config['source_file']);
    }

    private function getBadWordsFromArray() {
        if (!empty($this->config['bad_words_array']) && is_array($this->config['bad_words_array'])) {
            return $this->config['bad_words_array'];
        }

        throw new \Exception('Source is set to "array" but bad_words_array is either empty or not an array.');
    }

    private function getBadWordsFromDB() {
        throw new \Exception('Bad words from db is not yet supported. If you would like to see this feature please consider submitting a pull request.');
    }

    private function flattenArray($array) {
        $objTmp = (object) array('aFlat' => array());

        array_walk_recursive($array, create_function('&$v, $k, &$t', '$t->aFlat[] = $v;'), $objTmp);

        return $objTmp->aFlat;
    }


}