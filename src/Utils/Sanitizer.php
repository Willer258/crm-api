<?php

namespace App\Utils;

use Symfony\Component\String\Slugger\AsciiSlugger;
use function _PHPStan_0f7d3d695\RingCentral\Psr7\str;

class Sanitizer
{


    /** @var AsciiSlugger */
    private $slugger;

    public function __construct()
    {
        $this->slugger = new AsciiSlugger();
    }

    public function string(string $string = null)
    {
        return (string)strip_tags(trim($string));
    }

    public function phoneNumber(string $string)
    {
        $string = preg_replace('/\s/', '', $string);
        return preg_replace('/\D/', '', $string);
    }

    public function slugify(string $text = '', string $replaceBy = '_', bool $lowerCase = false)
    {
        return Sanitizer::slug($text, $replaceBy, $lowerCase);
    }

    public static function slug(string $text = '', string $replaceBy = '_', bool $lowerCase = false)
    {
        $slugger = new AsciiSlugger();
        $text = $slugger->slug($text, $replaceBy);
//        $text = preg_replace('~[^\pL\d]+~u', $replaceBy, $text);
//        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
//        $text = preg_replace('~[^-\w]+~', '', $text);
//        $text = trim($text, '-');
//        $text = preg_replace('~-+~', $replaceBy, $text);
//        $text = preg_replace('/\./', $replaceBy, $text);
//        $text = preg_replace('/\s+/', $replaceBy, $text);
        if ($lowerCase) {
            $text = strtolower($text);
        }
//        if (empty($text)) {
//            return uniqid('', false);
//        }
        return (string)$text;
    }


    public function camelToSnake()
    {

    }

    public static function abbreviation($text, $length, $space = 2, $uppercase = false)
    {
        $chars = str_split($text);
        $abv = '';
        foreach ($chars as $index => $char) {
            if ($char !== '-' && $char !== '_' && $char !== ' ' && strlen($abv) < $length) {
                if ($index % $space === 0) {
                    $abv .= $char;
                }
            }
        }
        if ($uppercase) {
            $abv = strtoupper($abv);
        }
        return $abv;
    }

    public static function initial($text, $uppercase = false, $count = 1)
    {
        $words = explode(' ', $text);
        $initial = '';
        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) > 0) {
                if (strlen($word) > $count) {
                    $initial .= mb_substr($word, 0, $count);
                } else {
                    $initial .= mb_substr($word, 0, 1);
                }
            }
        }
        if ($uppercase) {
            $initial = strtoupper($initial);
        }
        return $initial;
    }

    public static function snakeToCamel(string $text)
    {
        $text = str_replace('-', ' ', $text);
        $text = ucwords($text);
        return str_replace(' ', '', $text);
    }

    public static function textDif($text1, $text2) {
        $distance = levenshtein($text1, $text2);
        $maxLength = max(strlen($text1), strlen($text2));
        $similarity = (1 - $distance / $maxLength) * 100;
        return $similarity;
    }

    public static function getReadableString($length = 6)
    {
        $string = '';
        $vowels = array("a", "e", "i", "o", "u");
        $consonants = array(
            'b', 'c', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm',
            'n', 'p', 'r', 's', 't', 'v', 'w', 'x', 'y', 'z'
        );

        $max = $length / 2;
        for ($i = 1; $i <= $max; $i++) {
            $string .= $consonants[rand(0, 19)];
            $string .= $vowels[rand(0, 4)];
        }

        return $string;
    }

    public  function generateString($length = 6)
    {
        return self::getReadableString($length);
    }

    public static function getPartnerCodeFromUserCode($userCode)
    {
        $partnerCode = null;
        if (str_contains($userCode, '-')) {
            $array = explode('-', $userCode);
            if (count($array) > 1) {
                array_pop($array);
                $partnerCode = implode('-', $array);
            }
        }
        return $partnerCode;
    }

}
