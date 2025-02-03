@echo off
setlocal
:: Codepage UTF8 einstellen (65001)
chcp 65001 >nul

echo.
echo This script uninstalls and installs the root certificate for this machine.
echo Use this, if you want to fix root certificate related issues.
echo.
echo Please accept the warnings that will follow when you proceed...
echo.

:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: Initialisieren und Checks:
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

REM ueberpruefen, ob das Skript mit Administratorrechten ausgefuert wird
NET SESSION >nul 2>&1
IF %ERRORLEVEL% NEQ 0 (
    echo Cancel: Run this script as an Administrator again! Skript execution is cancelled now.
	echo.
    pause
    exit /b
)
pause

:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: Getting the right Document Root:
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

:: Ins Laufwerk des aktuellen Batch-Skripts wechseln:
%~d0

:: Ins Directory des aktuellen Batch-Skripts wechseln:
cd %~dp0

mkcert -uninstall
mkcert -install

pause