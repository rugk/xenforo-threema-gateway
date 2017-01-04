#!/bin/sh
# LICENSE: MIT license, see LICENSE.md
#
# Creates a new release
#

SOURCE_DIR="./src"
SCRIPT_DIR="./scripts"
DOC_DIR="./doc"
LANG_DIR="./languages"
BUILD_DIR="./build"
VERBOSE=0
ADD_HASHES=1

# functions
show_help() {
    return;
}

# parse parameters
languages=''
copyDoc=0
while getopts "h?vcl:" opt; do
    case "$opt" in
    h|\?)
        show_help
        exit 0
        ;;
    v|verbose)
        VERBOSE=1
        ;;
    c|copydoc)
        copyDoc=1
        ;;
    l|languages) # TODO: long form
        languages=$OPTARG
        ;;
    esac
done

shift $((OPTIND-1))

[ "$1" = "--" ] && shift

# get potential user input
if [ "$XENFORO_DIR" = "" ]; then
    read -p "Please enter the dir to XenForo: " XENFORO_DIR
    if [ "${XENFORO_DIR}" = "" ]; then
        exit 1
    fi
    # make sure to export the variable
    export XENFORO_DIR="${XENFORO_DIR}"
fi

# build
echo "Clean & Create dir…"
if [ -d "$BUILD_DIR" ]; then
    rm -rf "$BUILD_DIR"
fi
mkdir -p "$BUILD_DIR"

echo "Copy add-on XML…"
cp -a "addon-ThreemaGateway.xml" "$BUILD_DIR"

echo "Copy PHP files…"
mkdir -p "$BUILD_DIR/upload"
rsync -a "$SOURCE_DIR/" "$BUILD_DIR/upload/"

for lang in $languages; do
    mkdir -p "$BUILD_DIR/languages"
    # complete langauge attributions
    case $lang in
        en)
            lang="en-US"
            ;;
        de)
            lang="de-DE"
            ;;
    esac

    cp -a "$LANG_DIR/$lang.xml" "$BUILD_DIR/languages"
done

if [ $ADD_HASHES ]; then
    echo "Generating and file hashes…"
    php "$SCRIPT_DIR/AddHealthCheckHashes.php" "$BUILD_DIR/upload"
fi

if [ $copyDoc ]; then
    echo "Copy doc files…"
    mkdir -p "$BUILD_DIR/doc"
    rsync -a "$DOC_DIR/" "$BUILD_DIR/doc/"
fi
