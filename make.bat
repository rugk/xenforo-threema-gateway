@echo off
rem Creates build out of source.
rem
rem Copyright (c) rugk, 2015
rem This file is licensed under the MIT/Expat license. See LICENSE.md for more information.

rem THIS FILE IS DEPRECIATED! It shjould be replaced with a platform-independ solution. (python)

rem ERROR CODES:
rem    0    Everything worked as expected.
rem    1-99 Warnings (Build can still be valid...)
rem 100-199 User generated error (stopped execution)
rem 200-299 Bad error causing build to fail. (missing rights, ...)
rem 300-399 Bad error while 'building'.

rem ==========================
rem PREPARATION
rem ==========================
setlocal EnableExtensions EnableDelayedExpansion
if errorlevel 1 (
  echo ERROR: Your batch interpreter does not work with this script.
  set errorcode=201
  goto end
)
rem
rem ** Set static variables **
rem
set buildDir=%cd%\build
set sourceDir=%~DP0
rem % rem (workaround for https://github.com/footballhead/language-batch/issues/16)
rem Correct trailing slash at end of path
set sourceDir=%sourceDir:~0,-1%
set tempDir=%sourceDir%\temp

set ownfilename=%~N0

set baseExcludeFile=%sourceDir%\.make_excludebase
set docExcludeFile=%sourceDir%\.make_excludedoc
set tempExcludefile=%tempDir%\make_excludetemp.tmp

rem
rem ** Set default variables **
rem
set includeAddonSource=1
set includeAddonXML=1
set includeAddonLanguages=1
set includeSdkSource=1
REM set includeAddonAssets=0

set excludeDoc=1
set SdkSourceMode=0

set addonLanguageFilter=*.xml

rem Following variables are fixed defaults. To overrride them you have to pass
rem a parameter.
set verbose=0
set doNotAsk=0
set ignoreErrors=0
set keepSymblinks=0

rem
rem ** Set internal variables **
rem
set copyparams=
set xcopyparams=/E /I

set errorcode=0
REM set copyhider=

rem ==========================
rem PARSING SETTINGS
rem ==========================

rem
rem ** Parsing parameters **
rem
set parpos=0
:paramParse
rem Check whether all params were (already) parsed
set par=%~1
if "%par%"=="" goto paramParseEnd
rem Remove leading character / or - as it does not matter whether you use
rem "-" or "/".
set par=%par:~1%
set /a parpos=%parpos%+1

rem Parameter OR-helper
set isHelpPar=0
if "%par%"=="?" set isHelpPar=1
if "%par%"=="h" set isHelpPar=1
if "%par%"=="help" set isHelpPar=1

set isVerbosePar=0
rem Normal verbose mode:
if "%par%"=="v" set isVerbosePar=1
if "%par%"=="verbose" set isVerbosePar=1
rem Extended verbose mode:
if "%par%"=="vv" set isVerbosePar=2
if "%par%"=="v2" set isVerbosePar=2
if "%par%"=="verbose2" set isVerbosePar=2

rem Check whetherthe next value is a boolean
set isBooleanValue=0
if "%~2"=="0" set isBooleanValue=1
if "%~2"=="1" set isBooleanValue=1

rem Checking for parameters
if %isHelpPar% EQU 1 (
  rem Check for pther parameter besides /?
  rem Comment this out if you do not want to ignore such unimportant errors.
  REM if %parpos% NEQ 1 goto err_badParam
  REM if not "%~2"=="" goto err_badParam

  rem Show help
  echo Copies all files in a separate dir for publishing.
  echo.
  rem Note that 'echo.' is just used because otherwise /? would not be escaped.
  rem See https://stackoverflow.com/questions/6514746/escaping-a-batch-file-echo-that-starts-with-a-forward-slash-and-question-mark
  echo.%ownfilename% [/? ^| /h ^| /help]
  echo %ownfilename% [/includeAddonSource 0/1] [/includeAddonXML 0/1]
  echo     [/includeAddonLanguages 0/1 [/addonLanguageFilter str]]
  echo     [/includeSdkSource 0/1] [/SdkSourceMode 0/1]] [/excludeDoc 0/1]
  echo     [/doNotAsk] [/v ^| /vv]
  echo.
  if %parpos% NEQ 1 (
    rem (warnings method)
    echo WARNING: As you already passed other parameters the displayed default values
    echo may not display the correct ones. Please use /? as the first parameter to
    echo display the correct values.
    echo.
    set errorcode=10
  )
  echo Parameters:
  echo.   /? /h /help                  Displays this help.
  echo    /includeAddonSource     0/1  Specifies whether to include add-on source
  echo                                 code. Default: %includeAddonSource%
  echo    /includeAddonXML        0/1  Specifies whether to include the installation
  echo                                 XML. Default: %includeAddonXML%
  echo    /includeAddonLanguages  0/1  Specifies whether to include the installation
  echo                                 XML. Default: %includeAddonLanguages%
  echo    /addonLanguageFilter    str  The filter for xcopy for the languages. You can
  echo                                 use some wildcards. Default: %addonLanguageFilter%
  echo    /includeSdkSource       0/1  Specifies whether to include the Threema
  echo                                 Gateway SDK. Default: %includeSdkSource%
  echo    /SdkSourceMode          0/1  Specifies whether to include the Threema
  echo                                 SDK source ^(1^) or .phar ^(0^). Default: %SdkSourceMode%
  echo    /excludeDoc             0/1  Specifies whether to include the documentation
  echo                                 files. ^(Readme, Examples, ...^) Default: %excludeDoc%
  echo    /doNotAsk                    Do not ask the user before critical actions.
  echo    /ignoreErrors                Ignore all errors.
  echo    /keepSymblinks               Keep all symbolic links. You have to run the
  echo                                 script with elevated privileges if you use this
  echo                                 option.
  echo    /v                           Verbose mode on. ^(Displays copied files^)
  echo    /vv                          Extended verbose mode on.

  goto end
) else if %isVerbosePar% EQU 1 (
    set verbose=1
) else if %isVerbosePar% EQU 2 (
    set verbose=2
) else if "%par%"=="includeAddonSource" (
  if %isBooleanValue% NEQ 1 goto err_badParam_Boolean
  set includeAddonSource=%~2
  shift
) else if "%par%"=="includeAddonXML" (
  if %isBooleanValue% NEQ 1 goto err_badParam_Boolean
  set includeAddonXML=%~2
  shift
) else if "%par%"=="includeAddonLanguages" (
  if %isBooleanValue% NEQ 1 goto err_badParam_Boolean
  set includeAddonLanguages=%~2
  shift
) else if "%par%"=="addonLanguageFilter" (
  set addonLanguageFilter=%~2
  shift
) else if "%par%"=="includeSdkSource" (
  if %isBooleanValue% NEQ 1 goto err_badParam_Boolean
  set includeSdkSource=%~2
  shift
) else if "%par%"=="SdkSourceMode" (
  if %isBooleanValue% NEQ 1 goto err_badParam_Boolean
  set SdkSourceMode=%~2
  shift
) else if "%par%"=="excludeDoc" (
  if %isBooleanValue% NEQ 1 goto err_badParam_Boolean
  set excludeDoc=%~2
  shift
) else if "%par%"=="doNotAsk" (
  set doNotAsk=1
) else if "%par%"=="ignoreErrors" (
  set ignoreErrors=1
) else if "%par%"=="keepSymblinks" (
  set keepSymblinks=1
) else (
  rem Unknown parameter
  echo ERROR: Unknown parameter /%par% at position %parpos%.
  set errorcode=202
  goto end
)

rem Try next character...
shift
goto paramParse

:paramParseEnd
rem
rem ** Adjusting settings **
rem

rem Copy symblink instead of files
rem NOTE: If you do so this script must be executed with elevated privileges.
if %keepSymblinks% EQU 1 (
  set xcopyparams=%xcopyparams% /B
  set copyparams=%copyparams% /L
)

rem Set basic verbose mode
if %verbose% GEQ 1 (
  rem Show file names
  set xcopyparams=%xcopyparams% /F
) else (
  rem Do not show files
  set xcopyparams=%xcopyparams% /Q
)
rem Additionally set extended verbose mode
if %verbose% GEQ 2 (
  prompt %ownfilename%$G
  echo on
)

rem ==========================
rem CREATE TEMP FILES
rem ==========================

rem
rem ** Temp Dir **
rem
if not exist "%tempDir%" md "%tempDir%"
if not exist "%tempDir%" (
  echo ERROR: New temp dir could not be created.
  set errorcode=202
  if %ignoreErrors% NEQ 1 goto end
) else (
  echo Empty temp dir created.
)

rem ==========================
rem CREATE DIR
rem ==========================

rem
rem ** Build Dir **
rem
rem Remove old dir
if exist "%buildDir%" (
  if %doNotAsk% NEQ 1 (
    echo All files in "%buildDir%" will be removed. Continue?
    set userinput=y
    set /p userinput=[Y/n]=
    If not "!userinput!"=="y" If not "!userinput!"=="yes" (
      set errorcode=101
      goto end
    )
  )

  rd /S /Q "%buildDir%"
  if exist "%buildDir%" (
    echo ERROR: Old build dir could not be removed.
    set errorcode=210
    if %ignoreErrors% NEQ 1 goto end
  ) else (
    echo Old build dir removed.
  )
)

rem Create directory structure
set temperror=0
md "%buildDir%"
if not exist "%buildDir%" set /a temperror=%temperror%+1
if not exist "%buildDir%\upload" md "%buildDir%\upload"
if not exist "%buildDir%\upload" set /a temperror=%temperror%+1

if %includeAddonLanguages% EQU 1 (
  md "%buildDir%\languages"
  if not exist "%buildDir%\languages" set /a temperror=%temperror%+1
)

if %temperror% GEQ 1 (
  echo ERROR: Directory structure could not be created.
  set errorcode=220+%temperror%
  if %ignoreErrors% NEQ 1 goto end
) else (
  echo Directory structure created.
)

rem
rem ** Copy files **
rem

rem Copy source files
if %includeAddonSource% EQU 1 (
  echo threema-msgapi-sdk-php > %tempExcludefile%

  set xcopyexcludeparam=%baseExcludeFile%+%tempExcludefile%
  If %excludeDoc% EQU 1 (
    rem Do not include Readme
    set xcopyexcludeparam=!xcopyexcludeparam!+%docExcludeFile%
  ) else (
    rem Include Readme
    rem Copy README
    copy "%sourceDir%\README.md" "%buildDir%"
    if errorlevel 1 (
      echo ERROR: Error while copying add-on readme file.
      set errorcode=312
      if %ignoreErrors% NEQ 1 goto end
    ) else (
      echo Add-on Readme file was copied.
    )
  )

  xcopy /EXCLUDE:!xcopyexcludeparam! %xcopyparams% "%sourceDir%\src\*.*" "%buildDir%\upload"
  if errorlevel 1 (
    echo ERROR: Error while copying source files.
    set errorcode=310
    if %ignoreErrors% NEQ 1 goto end
  ) else (
    echo Source files copied.
  )
)

rem Copy SDK files
if %includeSdkSource% EQU 1 (
  rem Copy library source
  if %SdkSourceMode% EQU 1 (
    echo threema_msgapi.phar> %tempExcludefile%
    echo threema-msgapi-tool.php>> %tempExcludefile%
  ) else (
    echo \source\> %tempExcludefile%
  )

  set xcopyexcludeparam=%baseExcludeFile%+%tempExcludefile%
  If %excludeDoc% EQU 1 (
    set xcopyexcludeparam=!xcopyexcludeparam!+%docExcludeFile%
  )

  if not exist "%buildDir%\upload" md "%buildDir%\upload"

  xcopy /EXCLUDE:!xcopyexcludeparam! %xcopyparams% "%sourceDir%\src\library\ThreemaGateway\threema-msgapi-sdk-php" "%buildDir%\upload\library\ThreemaGateway\threema-msgapi-sdk-php"
  if errorlevel 1 (
    echo ERROR: Error while copying SDK files.
    set errorcode=310
    if %ignoreErrors% NEQ 1 goto end
  ) else (
    echo SDK files copied.
  )

  rem Copy LICENSE
  copy "%sourceDir%\LICENSE.md" "%buildDir%\upload\library\ThreemaGateway"
  if errorlevel 1 (
    echo ERROR: Error while copying license file to upload\library dir.
    set errorcode=322
    if %ignoreErrors% NEQ 1 goto end
  ) else (
    echo License file copied to upload\library dir.
  )
)

rem Copy XML file
if %includeAddonXML% EQU 1 (
  rem xcopy would not work
  copy "%sourceDir%\addon-ThreemaGateway.xml" "%buildDir%"
  if errorlevel 1 (
    echo ERROR: Error while copying XML file.
    set errorcode=320
    if %ignoreErrors% NEQ 1 goto end
  ) else (
    echo XML file copied.
  )
)

rem Copy license file
copy "%sourceDir%\LICENSE.md" "%buildDir%"
if errorlevel 1 (
  echo ERROR: Error while copying license file to build dir.
  set errorcode=331
  if %ignoreErrors% NEQ 1 goto end
) else (
  echo License file copied to build dir.
)

rem Copy language files
if %includeAddonLanguages% EQU 1 (
  set xcopyexcludeparam=%baseExcludeFile%
  If %excludeDoc% EQU 1 (
    set xcopyexcludeparam=!xcopyexcludeparam!+%docExcludeFile%
  )

  xcopy /EXCLUDE:!xcopyexcludeparam! %xcopyparams% "%sourceDir%\languages\%addonLanguageFilter%" "%buildDir%\languages"
  if errorlevel 1 (
    echo ERROR: Error while copying language files.
    set errorcode=330
    if %ignoreErrors% NEQ 1 goto end
  ) else (
    echo Language files copied.
  )
)

echo Building finished.
goto end

rem ==========================
rem GOTO
rem ==========================

rem
rem ** Errors **
rem
:err_badParam
echo ERROR: Wrong parameters. Use %ownfilename% /? to display a list of
echo parameters you can use.
set errorcode=203
goto end

:err_badParam_Boolean
echo ERROR: Wrong parameter at position %parpos%: %~2
echo Expected a boolean.
set errorcode=204
goto end

rem
rem ** Exit **
rem
:end
if %errorcode% NEQ 0 (
  echo Exiting with exit code %errorcode%.
)

rem Remove temp dir
if exist "%tempDir%" rd /S /Q "%tempDir%" >nul
exit /b %errorcode%
