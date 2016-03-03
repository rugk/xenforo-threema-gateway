<?php
/**
 * Provides an interface to the Libsodium library.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
 */

class ThreemaGateway_Handler_Libsodium
{
    /**
     * Tests whetheer libsodium is correctly set up. This only works with
     * libsodium >= 0.2.0.
     *
     * @param int $length The length of the string
     */
    public function __construct()
    {
        if (!extension_loaded('libsodium') || method_exists('Sodium', 'sodium_version_string')) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_libsodium_error'));
        }
    }

    /**
     * Tests whetheer this libsodium library can be used.
     *
     * In comparision to {@link __construct()} this does not throw an exception
     * when you cannot use libsodium.
     *
     * @return bool
     */
    public static function canUse()
    {
        return extension_loaded('libsodium') && !method_exists('Sodium', 'sodium_version_string');
    }

    /**
     * Generates a random numeric string.
     *
     * @param  int    $length The length of the string
     * @return string
     */
    public function getRandomNumeric($length)
    {
        return $this->getRandomString($length, '0123456789');
    }

    /**
     * Generates a random alphabetic string. (only lower letters).
     *
     * @param  int    $length The length of the string
     * @return string
     */
    public function getRandomAlpha($length)
    {
        return $this->getRandomString($length, 'abcdefghijklmnopqrstuvwxyz');
    }

    /**
     * Generates a random alphanumeric string. (only lower letters).
     *
     * This excludes letters which are commonly confused such as O and 0.
     *
     * @param  int    $length The length of the string
     * @return string
     */
    public function getRandomAlphaNum($length)
    {
        return $this->getRandomString($length, 'abcdefghjkmnpqrstuvwxyz23456789');
    }

    /**
     * Generates a random string.
     *
     * @link https://paragonie.com/blog/2015/07/how-safely-generate-random-strings-and-integers-in-php
     * @param  int    $length   The length of the string
     * @param  string $keyspace The characters to choose from
     * @return string
     */
    public function getRandomString($length, $keyspace = 'abcdefghijklmnopqrstuvwxyz234567')
    {
        $str     = '';
        $keysize = strlen($keyspace);
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keyspace[$this->getRandomInteger($keysize)];
        }
        return $str;
    }

    /**
     * Generates some random bytes.
     *
     * @link https://paragonie.com/book/pecl-libsodium/read/02-random-data.md
     * @param  int    $bytes Number of bytes to return
     * @return string
     */
    public function getRandomBytes($number)
    {
        return \Sodium\randombytes_buf($number);
    }

    /**
     * Generates some random integers.
     *
     * @link https://paragonie.com/book/pecl-libsodium/read/02-random-data.md
     * @param  int $range Upper bound
     * @return int
     */
    public function getRandomInteger($range)
    {
        return \Sodium\randombytes_uniform($range);
    }

    /**
     * Converts a binary string to an hexdecimal string.
     *
     * This is the same as PHP's bin2hex() implementation, but it is resistant to
     * timing attacks.
     *
     * @link https://paragonie.com/book/pecl-libsodium/read/03-utilities-helpers.md#bin2hex
     * @param  string $binaryString The binary string to convert
     * @return string
     */
    public function bin2hex($binaryString)
    {
        return \Sodium\bin2hex($binaryString);
    }

    /**
     * Converts an hexdecimal string to a binary string.
     *
     * This is the same as PHP's hex2bin() implementation, but it is resistant to
     * timing attacks.
     *
     * @link https://paragonie.com/book/pecl-libsodium/read/03-utilities-helpers.md#hex2bin
     * @param  string $hexString The hex string to convert
     * @param  string $ignore    (optional) Characters to ignore
     * @return string
     */
    public function hex2bin($hexString, $ignore = '')
    {
        return \Sodium\hex2bin($hexString, $ignore);
    }

    /**
     * Erases the content of a variable with sensitive data.
     *
     * @link https://paragonie.com/book/pecl-libsodium/read/03-utilities-helpers.md#memzero
     * @param string $variable The variable you want to wipe
     */
    public function memzero(&$variable)
    {
        return \Sodium\memzero($variable);
    }

    /**
     * Increments a value (e.g a nounce).
     *
     * @link https://paragonie.com/book/pecl-libsodium/read/03-utilities-helpers.md#increment
     * @param  string $binaryString The variable you want to increment
     * @return string
     */
    public function increment(&$binaryString)
    {
        return \Sodium\increment($binaryString);
    }

    /**
     * Compares two strings.
     *
     * This is the same as PHP's strcmp() implementation, but it is resistant to
     * timing attacks.
     *
     * @link https://paragonie.com/book/pecl-libsodium/read/03-utilities-helpers.md#compare
     * @param  string $str1 The first string
     * @param  string $str2 The second string
     * @return int
     */
    public function compare($str1, $str2)
    {
        return \Sodium\compare($str1, $str2);
    }

    /**
     * Compares two strings in constant tine (like hash_equals()).
     *
     * @link https://paragonie.com/book/pecl-libsodium/read/03-utilities-helpers.md#memcmp
     * @param  string $str1 The first string
     * @param  string $str2 The second string
     * @return int 0 = successful; -1 = failure
     */
    public function memcmp($str1, $str2)
    {
        return \Sodium\memcmp($str1, $str2);
    }
}
