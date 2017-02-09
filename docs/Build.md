# How to build

## Cloning

Please clone this repo and also (recursively) clone all submodules, because e.g. the PHP-SDK is a git submodule.

## Creating add-on
Just execute the included build script:

```console
$ build.sh
```

You can pass `-h` to it to see the help.

For the script to work you must have [`rpl`](http://rpl.sourceforge.net/), [uglifyjs](https://github.com/mishoo/UglifyJS2) and 7zip installed.

## Creating PHPDOC
If you want you can create a PHPdoc of this.

Just run this on your command line:

```console
$ php <phpdoc.phar> -t src/libary/ThreemaGateway -d build/phpdoc
```
