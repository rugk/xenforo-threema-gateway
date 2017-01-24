# Contributing
It's great you want to contribute to this project. You can do this in many ways, so keep on reading. :smiley: :tada:

## Bug reporting (Issues)
If you experience any issues you can use the [GitHubs Issue Tracker](issues) to report these. Please  include the following details:
- Operation system
- PHP version
- XenForo version
- version of this add-on
- whether you installed libsodium and if so:
  - which version of libsodium you installed
- cURL version if relevant (if you experience connection/sending errors)
- any settings, which may influence the issue you have

An issue template will be shown when you create a new issue in this repo.

If you are not the server admin and your error happened on a publicy available server you can also just share the link to the forum. However,
please make sure to contact the server admin before, to make sure the issue cannot be solved by him/her.

## Translating
The language files can be found in _xenforo-threema-gateway\languages_. If there is a language missing which you like to be supported, feel free to translate it. You can use the [English example](languages/en-US.xml) as a starting point. It is recommend to use XenForo for this as you can use the internal XenForo translation system, but as these are just XML files so you can also manually translate them.

Special notes for some languages:

- German: Place the verb last, so say "Modus aktivieren" instead of "Aktiviere Modus".

## Coding
Here are some rules you should follow when you want to contribute code to this repo.

### Commit messages
- Use the present tense ("Add feature" not "Added feature")
- Use the imperative mood ("Move cursor to..." not "Moves cursor to...")

More information: [A Note About Git Commit Messages](http://tbaggery.com/2008/04/19/a-note-about-git-commit-messages.html)

### PHP Style
Use the **[PHP Coding Style Fixer](http://cs.sensiolabs.org/)** to clean up your code. There is a config file for this tool included in the repo (_[.php_cs](.php_cs)_), where many styling rules will be applied automatically. If you do not want or can use this, please follow these rules:
- Follow all rules, which are marked as PSR-0, PSR-1 and PSR-2 on the [php cs fixer site](http://cs.sensiolabs.org/). This are basic rules and should not be too difficult.
- Additionally follow these rules:
  - `concat_with_spaces` Around an `.` should be one space at each side.
  - `short_array_syntax` Use the new [short array syntax](https://secure.php.net/manual/language.types.array.php) of PHP 5.4
  - `standardize_not_equal` Use `!=` instead of `<>`
  - `operators_spaces` All operators should have one space at each side.
  - `duplicate_semicolon` Do not duplicate semicolons.
  - `single_array_no_trailing_comma` Single-line arrays should have no comma.
  - `print_to_echo` Do not use `print`. Use `echo` instead.
  - `single_quote` Prefer single quotes over double quotes.

Additionally there some other rules:
- Use **PHPdoc**
- Also use PHPDOC for all new variables you introduce:
   `/** @var string $someString This string is an example. */`
- Do **not require** a **higher PHP version** as described in the current [system requirements](./README.md#Requirements) without prior discussing this. Actually I think the current required PHP version is already quite high, so you should be able to use most of the nice features of recent PHP versions.
- **Test your changes!** (except when you only edit the documentation)
- use **camelCase** for variable and function names
- start any new file like this:

  ```php
  <?php
  /**
   * Short description of the purpose of this file.
   *
   * More information if required.
   *
   * @package ThreemaGateway
   * @author rugk
   * @copyright Copyright (c) rugk, 2017
   * @license MIT/Expat license, see LICENSE.md for details
   */
  ```

- When adding a regular expression add a link to a sample/test set on https://regex101.com/ in the comments. When modifying the RegExp edit this test set and update the link.

### MySQL
* Always use prepared statements.
* Use Zend_Db - that's how XenForo does it.

### JavaScript/jQuery

* We use the [extensible module](http://www.adequatelygood.com/JavaScript-Module-Pattern-In-Depth.html) pattern, which is a variation of the popular Revealing Module Pattern. Please follow the style shown in the already existing JS files.

### XenForo
* Intend templates correctly with tabs.
* Avoid direct MySQL queries. Use Models/DataWriters instead.
