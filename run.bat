@echo on

::set VCVARS=C:\Program Files (x86)\Microsoft Visual Studio 12.0\VC\bin\amd64\vcvars64.bat
set PHP_BUILD_DIR=%PHP_SRC%\x64\Release
set QDB_API=%~dp0qdb\win64
set QDB_DAEMON=%~dp0qdb\win64
set TEST_DIR=%~dp0..\test
set PATH=%PATH%;%QDB_DAEMON%\bin;%QDB_API%\bin

:: call "%VCVARS%"

for %%f in (*.php) do "%PHP_BUILD_DIR%\php" -l %%f || exit /b 1

start qdbd.exe -c qdbd.conf
ping 127.0.0.1 -n 6 > nul
"%PHP_BUILD_DIR%\php" "-dextension_dir=%PHP_BUILD_DIR%" "-dextension=php_quasardb.dll" "test.php"
"%PHP_BUILD_DIR%\php" "-dextension_dir=%PHP_BUILD_DIR%" "-dextension=php_quasardb.dll" "test.php"
taskkill /im qdbd.exe /f