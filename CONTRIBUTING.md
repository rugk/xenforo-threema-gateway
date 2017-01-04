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

## Translating
The language files can be found in _xenforo-threema-gateway\languages_. If there is a language missing which you like to be supported, feel free to translate it. You can use the [English example](languages/en-US.xml) as a starting point. It is recommend to use XenForo for this as you can use the internal XenForo translation system for this, but as these are just XML files you can also manually translate them.

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
- Do **not require** a **higher PHP version** as described in the current [system requirements](./README.md#Requirements) without prior discussing this. Actually I think the current required PHP version is already quite high, so you should be able to use most of the nice features of recent PHP versions.
- **Test your changes!** (except when you only edit the documentation)
- use **camelCase** for variable and function names
- start any new file like this:

  ```php
  <?php
  /**
   * Short description of the purpose of this file
   *
   * More information if required
   *
   * @package ThreemaGateway
   * @author rugk
   * @copyright Copyright (c) rugk, 2017
   * @license MIT/Expat license, see LICENSE.md for details
   */
  ```

- add a short description to any class you create:

  ```php
  /**
   * An example class which does nothing
   */
  class ThreemaGateway_ExampleClass
  ```
- When adding an regular expression add a link to a sample/test set on https://regex101.com/ in the comments. When modifying the RegExp edit this test set and update the link.

### MySQL
* Always use prepared statements if possible.

### JavaScript/jQuery

* We use the [extensible module](http://www.adequatelygood.com/JavaScript-Module-Pattern-In-Depth.html) pattern, which is a variation of the popular Revealing Module Pattern. Please follow the style shown in the already existing JS files.

### XenForo
* Intend templates correctly with tabs.
* Avoid direct MySQL queries. Use Models/DataWriters instead.

### Markdown Style
These markdown rules of course only apply to markdown files in this repo. When creating a description for issues or pull requests with markdown I do not care how you do this.

Please follow [this styleguide](https://github.com/slang800/markdown-styleguide) when writing Markdown. The most important things are:
- avoid HTML in markdown files - use markdown
- use one space after a the # of the headline
- use correct headlines: start with `#h1` and add one # for every subheadline
- use `-` to start an enumeration
- after each paragraph (before a new headline) use _one_ empty line. Do not included unnecessary extra empty lines.
- use 2 spaces indentation if you e.g. indent an enumeration

You can use [tidy-markdown](https://github.com/slang800/tidy-markdown) which does all this for you.
