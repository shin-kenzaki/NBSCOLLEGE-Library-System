@echo off
echo Starting XAMPP services...

cd \xampp

REM Start Apache
start /B apache_start.bat
echo Apache service started

REM Start MySQL
start /B mysql_start.bat
echo MySQL service started

echo XAMPP services are now running
timeout /t 3 /nobreak > nul
