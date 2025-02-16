Set WShell = CreateObject("WScript.Shell")

' Start XAMPP MySQL silently
WShell.Run """C:\xampp\mysql_start.bat""", 0, False

' Wait 2 seconds for MySQL to start
WScript.Sleep 2000

' Start XAMPP Apache silently
WShell.Run """C:\xampp\apache_start.bat""", 0, False

' Wait 2 seconds for Apache to start
WScript.Sleep 2000

' Change directory and start PHP server silently
WShell.CurrentDirectory = "C:\xampp\htdocs\Library-System\Admin"
WShell.Run "php -S 0.0.0.0:8080", 0, False

' Wait 1 second for PHP server to start
WScript.Sleep 1000

' Open the browser
WShell.Run "http://localhost:8080", 1, False
