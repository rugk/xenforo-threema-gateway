<?php
/**
 * Handles emoji stuff.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
 */

 class ThreemaGateway_Handler_Emoji
 {
     /**
     * Replaces unicode escape sequence with the correct UNICODE character.
     *
     * You need to pass it as surrogate pairs, e.g. \ud83d\udd11.
     *
     * @param  string $string
     * @return string
     */
    public static function parseUnicode($string)
    {
        // uses json_decode as a hackish way to encode unicode strings
        // https://stackoverflow.com/questions/6058394/unicode-character-in-php-string

        // RegEx: https://regex101.com/r/yS2zX8/1
        return preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return json_decode('"\u' . $match[1] . '"');
        }, $string);
    }

    /**
     * Replaces numbers with their corresponding unicode characters
     * (surrogate pairs).
     *
     * Only replaces one digit numbers. "10" is therefore replaced by two unicode
     * characters.
     *
     * @param  string $string
     * @return string
     */
    public static function replaceNumbers($string)
    {
        // add \u20e3 to every number
        // https://regex101.com/r/aQ3eA3/1
        return preg_replace('/(\d)/', '\1\\u20e3', $string);

        //DEPRECIATED - TODO: remove code below

        // To prevent str_replace from replacing inserted values again we first
        // replace all numbers with unique identifiers, which contain no numbers.
        $string = str_replace([
            '0',
            '1',
            '2',
            '3',
            '4',
            '5',
            '6',
            '7',
            '8',
            '9'
        ], [
            '[zero]',
            '[one]',
            '[two]',
            '[three]',
            '[four]',
            '[five]',
            '[six]',
            '[seven]',
            '[eight]',
            '[nine]'
        ], $string);

        $string = str_replace([
            '[zero]',
            '[one]',
            '[two]',
            '[three]',
            '[four]',
            '[five]',
            '[six]',
            '[seven]',
            '[eight]',
            '[nine]'
        ], [
            '\u0030\u20e3',
            '\u0031\u20e3',
            '\u0032\u20e3',
            '\u0033\u20e3',
            '\u0034\u20e3',
            '\u0035\u20e3',
            '\u0036\u20e3',
            '\u0037\u20e3',
            '\u0038\u20e3',
            '\u0039\u20e3'
        ], $string);
        return $string;
    }
 }
