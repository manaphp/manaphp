@echo off

@setlocal

set MANA_PATH=%~dp0

if "%PHP_COMMAND%" == "" set PHP_COMMAND=php.exe

"%PHP_COMMAND%" "%MANA_PATH%manacli.php" %*

@endlocal
