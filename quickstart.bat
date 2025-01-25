:: Startet einen schnellen lokalen Webserver zur Ansicht der Site im lokalen Browser...
@echo off
setlocal

:: Codepage UTF8 einstellen (65001)
chcp 65001 >nul

goto startServer

:: PHP-Ordner der Path-Variable hinzufügen:
:: Dazu überprüfen wir zuerst, ob der Pfad bereits in PATH enthalten ist:
echo %PATH% | findstr /I /C:"C:\xampp\php" >nul

:: Wenn Pfad nicht in globaler Systemvariable vorhanden, dann
:: jetzt eibtragen...
IF %ERRORLEVEL% NEQ 0 (
	echo PHP Pfad nicht in Systemvariable PATH eingetragen.
	goto setPath
	exit /b
)

goto startServer

:setPath
:: Fügt PHP in Path Systemvariable ein...
:: check, ob das Skript mit Administratorrechten ausgefuert wird
NET SESSION >nul 2>&1
IF %ERRORLEVEL% NEQ 0 (
	echo Dieses Skript muss als Administrator ausgeführt werden!
	pause
	exit /b
)

echo Der Pfad C:\xampp\php wird zur PATH-Umgebungsvariable hinzugefügt.
setx PATH "%PATH%;C:\xampp\php" /M


:startServer

setlocal

:: Wechselt in das Verzeichnis des Skripts
cd /d "%~dp0"

:: Zufälligen Port zwischen 1024 und 65535 wählen
set /a port=1024 + %random% %% 64512

set "directory=./httpdocs"

if not exist "%directory%" (
    echo Fehler: Das Verzeichnis "%directory%" existiert nicht.
	echo Fehler: Das Verzeichnis "%directory%" existiert nicht. >quickstart.php.log
	pause
    exit /b 1
)

:: Öffnet die Webseite im Standard-Browser nach einer Wartezeit von drei Sekunden
:: um sicherstellen, dass dann der PHP-Webserver bereits hochgefahren ist.
start cmd /c "timeout /t 2 >nul && start http://localhost:%port%

:: Startet den PHP-Entwicklungsserver im Hintergrund auf dem zufälligen Port
cmd /c "c:\xampp\php\php.exe -S localhost:%port% -t httpdocs >quickstart.php.log"

echo.
echo Lokaler Webserver wird gestartet... bitte warten Sie...

