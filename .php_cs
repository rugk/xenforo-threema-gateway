<?php
/**
 * Configuration file for PHP Coding Standards Fixer (php-cs-fixer).
 *
 * On GitHub: {@link https://github.com/FriendsOfPhp/php-cs-fixer}
 * More information: {@link http://cs.sensiolabs.org/}
 *
 * @author rugk
 * @copyright Copyright (c) 2015-2016 rugk
 * @license MIT
 */

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->exclude('threema-msgapi-sdk-php')
    ->in(__DIR__)
;

return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::PSR2_LEVEL)
    ->fixers(['concat_with_spaces', 'short_array_syntax', 'standardize_not_equal',
              'phpdoc_params', 'operators_spaces', 'duplicate_semicolon',
              'remove_leading_slash_use', 'align_equals', 'phpdoc_params',
              'single_array_no_trailing_comma', 'phpdoc_indent', 'phpdoc_scalar',
              'phpdoc_short_description', 'phpdoc_trim', 'phpdoc_order',
              'phpdoc_types', 'print_to_echo', 'self_accessor', 'single_quote',
              'spaces_cast', 'ternary_spaces', 'phpdoc_to_comment'])
    ->finder($finder)
;
