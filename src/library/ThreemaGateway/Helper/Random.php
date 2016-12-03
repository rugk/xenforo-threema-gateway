<?php
/**
 * Creates random data, strings, numbers etc.
 *
 * It makes use of libsodium >= 0.2.0 if possible.
 *
 * @package ThreemaGateway
 * @author rugk
 * @copyright Copyright (c) 2016 rugk
 * @license MIT
 */

class ThreemaGateway_Helper_Random
{
    /**
     * Generates a random numeric string.
     *
     * @param  int    $length The length of the string
     * @return string
     */
    public static function getRandomNumeric($length)
    {
        return self::getRandomString($length, '0123456789');
    }

    /**
     * Generates a random alphabetic string. (only lower letters).
     *
     * @param  int    $length The length of the string
     * @return string
     */
    public static function getRandomAlpha($length)
    {
        return self::getRandomString($length, 'abcdefghijklmnopqrstuvwxyz');
    }

    /**
     * Generates a random alphanumeric string. (only lower letters).
     *
     * This excludes letters which are commonly confused such as O and 0.
     *
     * @param  int    $length The length of the string
     * @return string
     */
    public static function getRandomAlphaNum($length)
    {
        return self::getRandomString($length, 'abcdefghjkmnpqrstuvwxyz0123456789');
    }

    /**
     * Generates a random string.
     *
     * Note that they $keyspace parameter may be ignored if libsodium is not
     * available as XenForo's native method has a hardcoded namespace.
     *
     * @link https://paragonie.com/blog/2015/07/how-safely-generate-random-strings-and-integers-in-php
     * @param  int    $length   The length of the string
     * @param  string $keyspace The characters to choose from
     * @return string
     */
    public static function getRandomString($length, $keyspace = 'abcdefghijklmnopqrstuvwxyz01234567')
    {
        /** @var string $output */
        $output  = '';
        if (self::canUseLibsodium()) {
            /** @var int $keysize */
            $keysize = strlen($keyspace);

            try {
                for ($i = 0; $i < $length; ++$i) {
                    $output .= $keyspace[self::getRandomInteger($keysize)];
                }
            } catch (Exception $e) {
                $output = '';
            }
        }

        if (!$output) {
            $output = XenForo_Application::generateRandomString($length, false);
        }

        return $output;
    }

    /**
     * Generates some random bytes.
     *
     * @link https://paragonie.com/book/pecl-libsodium/read/02-random-data.md
     * @param  int    $bytes Number of bytes to return
     * @return string
     */
    public static function getRandomBytes($number)
    {
        /** @var string $output */
        $output = '';

        if (self::canUseLibsodium()) {
            try {
                $output = \Sodium\randombytes_buf($number);
            } catch (Exception $e) {
                $output = '';
            }
        }

        if (!$output) {
            // recent XenForo versions (>= 1.5.11) do it better than Salt,
            // so only use Salt as a fallback if the XenForo version is older
            if (XenForo_Application::$versionId < 1051100) {
                // try PHP SDK, fails if not yet available
                try {
                    $output = ThreemaGateway_Handler_PhpSdk::getInstance()->getCryptTool()->createRandom($number);
                } catch (Exception $e) {
                    $output = '';
                }
            }
        }

        // use XenForos native method as the last fallback
        if (!$output) {
            $output = XenForo_Application::generateRandomString($number, true);
        }

        return $output;
    }

    /**
     * Generates some random integers.
     *
     * IMPORTANT: This required Libsodium >= 0.2.0. If libsodium is not
     * installed
     *
     * @link https://paragonie.com/book/pecl-libsodium/read/02-random-data.md
     * @param  int               $range Upper bound
     * @throws XenForo_Exception
     * @return int
     */
    public static function getRandomInteger($range)
    {
        self::assertLibsodiumAvailable();

        return \Sodium\randombytes_uniform($range);
    }

    /**
     * Tests whether the libsodium library can be used.
     *
     * @throws XenForo_Exception
     */
    protected static function assertLibsodiumAvailable()
    {
        if (!self::canUseLibsodium()) {
            throw new XenForo_Exception(new XenForo_Phrase('threemagw_libsodium_error'));
        }
    }

    /**
     * Tests whether the libsodium library can be used.
     *
     * @return bool
     */
    protected static function canUseLibsodium()
    {
        return extension_loaded('libsodium') && !method_exists('Sodium', 'sodium_version_string');
    }
}
