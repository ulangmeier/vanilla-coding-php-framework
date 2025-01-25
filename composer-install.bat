:: Startet einen schnellen lokalen Webserver zur Ansicht der Site im lokalen Browser...
@echo off
setlocal

:: Codepage UTF8 einstellen (65001)
chcp 65001 >nul

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

set "directory=./tools"

if not exist "%directory%" (
    echo Fehler: Das Verzeichnis "%directory%" existiert nicht.
	echo Fehler: Das Verzeichnis "%directory%" existiert nicht. >composer-install.log
	pause
    exit /b 1
)

:: Composer soll sich aktualisieren, um mit dem neuesten Composer zu arbeiten:
echo.
echo Composer: Check for updates of Composer
cmd /c tools\composer self-update

:: Tools aus composer.lock installieren:
echo.
echo Composer: Check for new dependencies...
cmd /c tools\composer install

:: Ein Update der externen Abhängigkeiten auf aktuellste Versionen ausführen
:: falls Parameter "update" angegeben ist.
if "%1" == "update" (
	echo.
	echo Composer: Updating dependencies...
	tools\composer update
)

pause