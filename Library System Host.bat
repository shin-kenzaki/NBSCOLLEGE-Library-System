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

echo Starting Admin Server...
start "Admin Server" cmd /c "Library Admin.bat"
timeout /t 3 /nobreak > nul

echo Starting User Server...
start "User Server" cmd /c "Library User.bat"
timeout /t 3 /nobreak > nul

echo Opening web browsers...
start http://localhost:8080
start http://localhost:8081

echo Library System is ready!
timeout /t 3 /nobreak > nul
