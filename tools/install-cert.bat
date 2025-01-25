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
    echo Dieses Skript muss als Administrator ausgeführt werden!
    pause
    exit /b
)


:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: Getting the right Document Root:
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

:: Ins Laufwerk des aktuellen Batch-Skripts wechseln:
%~d0

:: Ins Directory des aktuellen Batch-Skripts wechseln:
cd %~dp0

:: Aus dem Tools-Ordner, indem das Skript liegt, in den darüberliegenden
:: GitRoot-Ordner wechseln:
cd %~dp0\..

:: DocumentRoot ist das aktuelle Verzeichnis:
:: set documentRoot=%~dp0
set documentRoot=%cd%

:: Überprüfen, ob das letzte Zeichen im Pfad ein \ ist
if "%documentRoot:~-1%"=="\" (
	:: Ja, das letzte Zeichen ist ein \, es muss für später entfernt werden:
    set "documentRoot=%documentRoot:~0,-1%"
)

set gitRoot=%documentRoot%
set "documentRoot=%documentRoot%\httpdocs"


:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: Getting the right Domain Name:
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::


:: Überprüfen, ob der Parameter gesetzt ist
if "%~1"=="" (
	:: Kein Subdomaine oder Domaine als Parameter angegeben...
	goto :noParam
) else (
	goto :hasParam
)


:noParam

	:: Kein Subdomain-Parameter angegeben...
	
	:: Get the current Folder Name (with it, we will build our domain that we propose to use):
	for %%i in ("%gitRoot%\.") do set "currentFolderName=%%~nxi"

	:: Define the subdomain that you want to add to the local server and it's local domain root folder:
	set "defaultDomainName=dev.%currentFolderName%"

	:askForDomainName
	echo.
	echo Welcher Domainname soll verwendet werden?
	:: Ask user for the domain Name
	set "subDomainName="
	set /p "subDomainName=[q = Abbruch | Eingabetaste = Standard: %defaultDomainName%]: "

	REM Wenn der Benutzer nichts eingibt, den Standardwert verwenden
	if "%subDomainName%"=="" set "subDomainName=%defaultDomainName%"

	:: Wenn der Benutzer Ctrl+C eingibt, das Skript beenden
	if errorlevel 0 (
		if "%subDomainName%"=="q" (
			echo.
			echo Skript wird abgebrochen.
			exit /b
		)
	)

	:: Prüfen, ob der Domainname einen Punkt enthält. Wenn nicht, dann muss
	echo %subDomainName% | findstr "\." >nul
	if not %errorlevel% equ 0 (
		echo.
		echo Invalid domain or subdomain name. Please enter a name in one of the following formats:
		echo.
		echo domainname.tld
		echo subdomain.domainname.tld
		echo.
		goto :askForDomainName
	)
	
	goto :startScript
    
:hasParam

	:: Parameter ist angegeben...
	set param=%1

	:: Anführungszeichen entfernen, falls vorhanden
	set param=%param:"=%
	
	:: Und als unsere Subdomain verwenden:
	set subDomainName=%param%

	goto :startScript



:startScript
echo.
echo Der verwendete Domainname ist: %subDomainName%
echo.



:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: 1. Stammquellenzertifikat und Zertifikat für Subdomain installieren:
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

:: Zurück in den Tools-Ordner wechseln:
cd .\tools

:: Aktuelle Maschine als vertrauenswürdige Stamm-Zertifizierungsquelle installieren:
mkcert -install

:: Zertifikate für die Domain UND für localhost erstellen (deaktiviert)
:: mkcert %subDomainName% localhost 127.0.0.1 ::1

:: Zertifikat nur für die lokale Domain / Subdomain erstellen:
mkcert %subDomainName%

:: Verschieben an die richtigen Orte (Reihenfolge zwingend so einhalten!):
move /Y %subDomainName%*key.pem C:\xampp\apache\conf\ssl.key\%subDomainName%.key
move /Y %subDomainName%*.pem C:\xampp\apache\conf\ssl.crt\%subDomainName%.crt

echo.
echo The certificate files you find now in:
echo.
echo C:\xampp\apache\conf\ssl.key\%subDomainName%.key
echo.
echo  and in:
echo.
echo C:\xampp\apache\conf\ssl.crt\%subDomainName%.crt
echo.


:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: 2. vhosts-Datei bearbeiten:
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

:: Define the path to the httpd-vhosts.conf file
set vhostFile=C:\xampp\apache\conf\extra\httpd-ssl.conf

set "searchString=ServerName %subDomainName%"
findstr /C:"%searchString%" "%vhostFile%" >nul
if %errorlevel% equ 0 (
	:: Die Site ist bereits in Apache konfiguriert...
	echo %vhostFile% is already configured for %subDomainName%...
	echo.

) else (
	:: Append the virtual host configuration to the file
	echo Adding VirtualHost SSL configuration to %vhostFile%
	echo.


	:: Define the content to add
	echo. >> "%vhostFile%"
	echo ^<VirtualHost ^*:443^> >> "%vhostFile%"
	echo     DocumentRoot "%documentRoot%" >> "%vhostFile%"
	echo     ServerName %subDomainName%:443 >> "%vhostFile%"
	echo     SSLEngine on >> "%vhostFile%"
	echo     SSLCertificateFile "conf/ssl.crt/%subDomainName%.crt" >> "%vhostFile%"
	echo     SSLCertificateKeyFile "conf/ssl.key/%subDomainName%.key" >> "%vhostFile%"
	echo ^</VirtualHost^> >> "%vhostFile%"

)

