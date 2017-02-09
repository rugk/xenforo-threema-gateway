#!/bin/sh
# LICENSE: MIT license, see LICENSE.md
#
# Creates a new release of xenforo-threema-gateway.
#

CURR_DIR="$( pwd )"
SOURCE_DIR="$CURR_DIR/src"
PHP_SDK_SOURCE_DIR="$CURR_DIR/src/library/ThreemaGateway/threema-msgapi-sdk-php"
SCRIPT_DIR="$CURR_DIR/scripts"
DOC_DIR="$CURR_DIR/docs"
LANG_DIR="$CURR_DIR/languages"
BUILD_DIR="$CURR_DIR/build"
RELEASE_DIR="$CURR_DIR/release"

JS_DIR="upload/js/ThreemaGateway"

DEBUG_MARKER=" /* BUILD ADJUST */"
DEBUG_MARKED=" /* ADJUSTED AT BUILD TIME */"

# functions
show_help() {
    echo "This is the built script for xenforo-threema-gateway.

    $0 [-h|-?|--help]
    $0 [[-p|--sdkPhar] 0/1] [[-m|--copydoc] 0/1] [[-d|--debug] 0/1]
       [[-l|--languages] language list] [[-g|--genArchives] 0/1]
       [[-o|--addHashes] 0/1] [[-t|--date] date]

    -h|-?|--help     Show this help.
    -p|--sdkPhar     Build the PHP-SDK phar.
    -m|--copydoc     Additionally copy the doc files.
    -d|--debug       Build a debug version instead of a productive one.
    -l|--languages   After this parmeter specify the languages, which should be
                     included, separated by spaces. So you need to quote the
                     string. (e.g. 'en de' for English and German)
    -g|--genArchives Generate zip and tar.gz files (default: 1)
    -j|--minimizeJs  Minimize JS files using UglifyJs (default: 1)
                     If a debug version is created, the generated files are not
                     used by default.
    -o|--addHashes   Add file hashes to built, so health checker of XenForo
                     can be used. (default: 1, requires XenForo to be installed)
    -t|--date        Pass the current date, which should be replaced by in the
                     files. Useful for reproducible builts. (default: current date)
    "
    return;
}

# default parameters
languages=''
genPhpSdkPhar=1
copyDoc=0
debugMode=0
addHashes=1
genArchives=1
minimizeJs=1
varDate="$( date +%F )"

# parse parameters
while true; do
    param="$1"
    paramValue='1' # by default: assume true

    # if next param is not a new param it is a param value
    if [ "$2" != "" ] && [ "$( echo $2 | sed 's/^-//g' )" = "$2" ]; then
        paramValue="$2"
        shift
    fi

    # whitelist of available parameters
    case "${param}" in
        1)
            # always ignore true parameters
            ;;
        -h|-\?|--help)
            show_help
            exit 0
            ;;
        -p|--sdkPhar)
            genPhpSdkPhar="${paramValue}"
            ;;
        -m|--copydoc)
            copyDoc="${paramValue}"
            ;;
        -d|--debug)
            debugMode="${paramValue}"
            ;;
        -l|--languages)
            languages="${paramValue}"
            ;;
        -a|--genArchives)
            genArchives="${paramValue}"
            ;;
        -o|--addHashes)
            addHashes="${paramValue}"
            ;;
        -j|--minimizeJs)
            minimizeJs="${paramValue}"
            ;;
        -t|--date)
            varDate="${paramValue}"
            ;;
        --|'') # end processing
            break
            ;;
        *) # unknown command
            # show warning (currently even if file path was passed as we do not
            # accept anyone)
            echo "Warning: The parameter '${param}' is unknown and will be ignored."
            echo "Warning: Any further parameters will be ignored."
            break;
            ;;
    esac

    # shift to test next param
    shift
done

# check params
if [ "${languages}" = "1" ]; then
    echo "Invalid langauge parameter value. This cannot be true."
    exit 1
fi
if [ "${varDate}" = "1" ]; then
    echo "Invalid date parameter value. This cannot be true."
    exit 1
fi

# get user input
if [ "$XENFORO_DIR" = "" ]; then
    read -p "Please enter the dir to XenForo: " XENFORO_DIR
    if [ "${XENFORO_DIR}" = "" ]; then
        exit 1
    fi

    # make sure to export the variable
    export XENFORO_DIR
fi

# get version number
versionDefault=$( git describe --abbrev=0 --tags )
read -p "Version number [${versionDefault}]: " version
version=${version:-$versionDefault}

# copy files
echo "Clean & create dir…"
if [ -d "$BUILD_DIR" ]; then
    rm -rf "$BUILD_DIR"
fi
mkdir -p "$BUILD_DIR"

echo "Copy add-on XML…"
cp -a "addon-ThreemaGateway.xml" "$BUILD_DIR"

echo "Copy README.txt…"
cp -a "$DOC_DIR/templates/ArchiveReadme.txt" "$BUILD_DIR/README.txt"

echo "Copy LICENSE.md…"
cp -a "LICENSE.md" "$BUILD_DIR"

if [ ${genPhpSdkPhar} = 1 ]; then
    echo "Generate PHP-SDK phar…"
    cd "$PHP_SDK_SOURCE_DIR" || exit

    scripts/buildPhar.php

    cd "$CURR_DIR"
fi

echo "Copy source files…"
mkdir -p "$BUILD_DIR/upload"
rsync -a "$SOURCE_DIR/" "$BUILD_DIR/upload/"

if [ "$languages" ]; then
    mkdir -p "$BUILD_DIR/languages"
fi

if [ $copyDoc = 1 ]; then
    echo "Copy doc files…"
    mkdir -p "$BUILD_DIR/docs"
    rsync -a "$DOC_DIR/" "$BUILD_DIR/docs/"
fi

# complete language attributions
langFiles=''
for lang in $languages; do
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


# replace variables
echo "Replace variables…"
# for Readme or so…
rpl -q -R -d '{{CURR_VERSION}}' "${version}" "$BUILD_DIR"
rpl -q -R -d '{{CURR_GIT_HASH}}' "$( git rev-parse HEAD )" "$BUILD_DIR"
rpl -q -R -d '{{CURR_DATE}}' "${varDate}" "$BUILD_DIR"
# for source code debug
if [ $debugMode = 1 ]; then
    rpl -q -R -d "DEBUG = false$DEBUG_MARKER" "DEBUG = true$DEBUG_MARKED" "$BUILD_DIR"
else
    rpl -q -R -d "DEBUG = true$DEBUG_MARKER" "DEBUG = false$DEBUG_MARKED" "$BUILD_DIR"
fi

# minify JS files
if [ $minimizeJs = 1 ]; then
    for jsFile in "$BUILD_DIR"/"$JS_DIR"/*; do
        jsFilename="${jsFile%%.*}"

        # RegExp for comments: https://regex101.com/r/sP0bU3/2
        uglifyjs \
        --compress \
        --mangle \
        --screw-ie8 \
        --output "${jsFilename}.min.js" \
        --comments "/MIT license|Copyright|@preserve|@license|@source/i" \
        -- "${jsFile}"
        # Note: "define" does not work for some reason (so we could do define DEBUG=true/false)...

        # rename files to make the minimized version the default one if debug mode is disabled
        if [ $debugMode = 0 ]; then
            mv "${jsFile}" "${jsFilename}.full.js"
            mv "${jsFilename}.min.js" "${jsFile}"
        fi
    done
fi

# finalize files
if [ $addHashes = 1 ]; then
    echo "Generating file hashes…"
    php "$SCRIPT_DIR/GenFileHashes.php" "$BUILD_DIR/upload"
fi

#generate archives
if [ ${genArchives} = 1 ]; then
    mkdir -p "$RELEASE_DIR"

    echo "Generating archives…"
    cd "$BUILD_DIR" || exit
    7z a -mx=9 "$RELEASE_DIR/xenforo-threema-gateway_v${version}.zip" "./*" > /dev/null
    tar -caz --owner=rugk -f "$RELEASE_DIR/xenforo-threema-gateway_v${version}.tar.gz" -- *

    cd ..
fi
