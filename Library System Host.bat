@echo off
echo Starting XAMPP services...

cd \xampp

REM Start Apache
start /B apache_start.bat
echo Apache service started

REM Start MySQL
start /B mysql_start.bat
echo MySQL service started

echo Waiting for services to initialize...
timeout /t 5 /nobreak > nul

cd \xampp\htdocs\Library-System

echo Starting Library System...
echo This will start both Admin (port 8080) and User (port 8081) servers.

echo Set WShell = CreateObject("WScript.Shell") > "%temp%\invisible.vbs"
echo WShell.Run """" ^& WScript.Arguments(0) ^& """", 0, False >> "%temp%\invisible.vbs"
echo WScript.Sleep 2000 >> "%temp%\invisible.vbs"
echo WShell.Run "http://localhost:8080", 1, False >> "%temp%\invisible.vbs"
echo WShell.Run "http://localhost:8081", 1, False >> "%temp%\invisible.vbs"
echo WScript.Sleep 1000 >> "%temp%\invisible.vbs"
echo WShell.Run "taskkill /F /IM cmd.exe", 0, True >> "%temp%\invisible.vbs"

wscript.exe "%temp%\invisible.vbs" "Library Admin.bat"
wscript.exe "%temp%\invisible.vbs" "Library User.bat"

del "%temp%\invisible.vbs"

echo Library System is ready!
timeout /t 3 /nobreak > nul
