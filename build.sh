#!/bin/sh
# LICENSE: MIT license, see LICENSE.md
#
# Creates a new release
#

CURR_DIR="$( pwd )"
SOURCE_DIR="$CURR_DIR/src"
SCRIPT_DIR="$CURR_DIR/scripts"
DOC_DIR="$CURR_DIR/docs"
LANG_DIR="$CURR_DIR/languages"
BUILD_DIR="$CURR_DIR/build"
RELEASE_DIR="$CURR_DIR/release"
ADD_HASHES=1

# functions
show_help() {
    # TODO
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
    export XENFORO_DIR
fi

# build
echo "Clean & create dir…"
if [ -d "$BUILD_DIR" ]; then
    rm -rf "$BUILD_DIR"
fi
mkdir -p "$BUILD_DIR"

echo "Copy add-on XML…"
cp -a "addon-ThreemaGateway.xml" "$BUILD_DIR"

echo "Copy README.txt…"
cp -a "$DOC_DIR/templates/ArchiveReadme.md" "$BUILD_DIR/README.txt"

echo "Copy LICENSE.md…"
cp -a "LICENSE.md" "$BUILD_DIR"

echo "Copy source files…"
mkdir -p "$BUILD_DIR/upload"
rsync -a "$SOURCE_DIR/" "$BUILD_DIR/upload/"

if [ "$languages" ]; then
    mkdir -p "$BUILD_DIR/languages"
fi

langFiles=''
for lang in $languages; do
    # complete language attributions
    case $lang in
        en)
            lang="en_US"
            ;;
        de)
            lang="de_DE_du de_DE_Sie"
            ;;
    esac

    for langVariant in $lang; do
        # convert to full file names
        case $langVariant in
            en_US) langFiles="${langFiles} language-English-(US)";;
            de_DE_du) langFiles="${langFiles} language-Deutsch-[Du]";;
            de_DE_Sie) langFiles="${langFiles} language-Deutsch-[Sie]";;
            *) langFiles="${langFiles} ${langVariant}";;
        esac
    done
done

for langFile in $langFiles; do
    cp -a "$LANG_DIR/$langFile.xml" "$BUILD_DIR/languages"
done

if [ $ADD_HASHES = 1 ]; then
    echo "Generating file hashes…"
    php "$SCRIPT_DIR/GenFileHashes.php" "$BUILD_DIR/upload"
fi

if [ $copyDoc = 1 ]; then
    echo "Copy doc files…"
    mkdir -p "$BUILD_DIR/docs"
    rsync -a "$DOC_DIR/" "$BUILD_DIR/docs/"
fi

# get version number
versionDefault=$( git describe --abbrev=0 --tags )
read -p "Version number [${versionDefault}]: " version
version=${version:-$versionDefault}

# replace variables
echo "Replace variables…"
rpl -q -R -d '{{CURR_VERSION}}' "${version}" "$BUILD_DIR"
rpl -q -R -d '{{CURR_GIT_HASH}}' "$( git rev-parse HEAD )" "$BUILD_DIR"
rpl -q -R -d '{{CURR_DATE}}' "$( date +%F )" "$BUILD_DIR"

# ZIP files
mkdir -p "$RELEASE_DIR"

echo "Generating archives…"
cd "$BUILD_DIR"
7z a -mx=9 "$RELEASE_DIR/xenforo-threema-gateway_v${version}.zip" "./*" > /dev/null
tar -caz --owner=rugk -f "$RELEASE_DIR/xenforo-threema-gateway_v${version}.tar.gz" -- *

cd ..
