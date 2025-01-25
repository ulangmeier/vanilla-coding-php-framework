@echo OFF
:: in case DelayedExpansion is on and a path contains !
setlocal DISABLEDELAYEDEXPANSION

:: Codepage UTF8 einstellen (65001)
chcp 65001 >nul

set "curDir=%cd%"

:: Prüfen, ob composer.phar im aktuellen Verzeichnis nicht existiert
if not exist "%~dp0composer.phar" (
    echo Composer.phar existiert nicht. Es wird heruntergeladen und installiert...
	:: Ins aktuelle Verzeichnis wechseln um hier zu installieren:
	cd /d %~dp0
    curl -sS https://getcomposer.org/installer | php
    echo Installation abgeschlossen.
	:: Zurück ins gewählte Verzeichnis wechseln...
	cd /d %curDir%
)

php "%~dp0composer.phar" %*