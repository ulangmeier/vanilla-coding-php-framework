:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: Opens the local Apache Configuration files in Notepad, for manual
:: checks and changes. Must be started with Admin privileges.
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::


@echo off
setlocal
:: Codepage UTF8 einstellen (65001)
chcp 65001 >nul

echo.

:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: Initialisieren und Checks:
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

REM ueberpruefen, ob das Skript mit Administratorrechten ausgefuert wird
NET SESSION >nul 2>&1
IF %ERRORLEVEL% NEQ 0 (
    echo Dieses Skript sollte als Administrator ausgeführt werden!
    pause
    exit /b
)

start notepad C:\Windows\System32\Drivers\etc\hosts
start notepad C:\xampp\apache\conf\extra\httpd-ssl.conf
start notepad C:\xampp\apache\conf\extra\httpd-vhosts.conf
start notepad C:\xampp\apache\conf\httpd.conf

