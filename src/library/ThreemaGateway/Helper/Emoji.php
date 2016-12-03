<?php
/**
 * Handles emoji stuff.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
 */

 class ThreemaGateway_Helper_Emoji
 {
     /**
     * Replaces unicode escape sequence with the correct UNICODE character.
     *
     * XenForo template helper: emojiparseunicode.
     * You need to pass it as surrogate pairs, e.g. \ud83d\udd11.
     *
     * @param  string $string
     * @return string
     */
    public static function parseUnicode($string)
    {
        // uses json_decode as a hackish way to encode unicode strings
        // https://stackoverflow.com/questions/6058394/unicode-character-in-php-string

        // RegEx: https://regex101.com/r/yS2zX8/3
        return preg_replace_callback('/(\\\\u([0-9a-fA-F]{4}))+/', function ($match) {
            return json_decode('"' . $match[0] . '"');
        }, $string);
    }

    /**
     * Replaces digits with their corresponding unicode characters
     * (surrogate pairs).
     *
     * XenForo template helper: emojireplacedigits.
     * Only replaces one digit numbers. "10" is therefore replaced by two unicode
     * characters.
     *
     * @param  string $string
     * @return string
     */
    public static function replaceDigits($string)
    {
        // add \u20e3 to every number
        // https://regex101.com/r/aQ3eA3/1
        return preg_replace('/(\d)/', '\1\\u20e3', $string);
    }
 }
