@echo off

set VCVARS=C:\Program Files (x86)\Microsoft Visual Studio 12.0\VC\bin\amd64\vcvars64.bat
set PHP_BUILD_DIR=%PHP_SRC%\x64\Release_TS
set QDB_API=%~dp0qdb\win64
set QDB_DAEMON=%~dp0qdb\win64
set PHP_SRC=C:\Sources\php-5.5.20-src
set TEST_DIR=%~dp0..\test
set PATH=%PATH%;%QDB_DAEMON%\bin;%QDB_API%\bin

call "%VCVARS%"

start qdbd.exe -o --transient
sleep 6
"%PHP_BUILD_DIR%\php" "-dextension_dir=%PHP_BUILD_DIR%" "-dextension=php_quasardb.dll" "test.php"
taskkill /im qdbd.exe /f