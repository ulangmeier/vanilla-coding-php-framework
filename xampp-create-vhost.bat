@echo off
setlocal enabledelayedexpansion

:: Codepage UTF8 einstellen (65001)
chcp 65001 >nul


echo.

REM ueberpruefen, ob das Skript mit Administratorrechten ausgefuert wird
NET SESSION >nul 2>&1
IF %ERRORLEVEL% NEQ 0 (
    echo Dieses Skript muss als Administrator ausgeführt werden!
    pause
    exit /b
)

:: Keep the Script root for later use:
set scriptRoot=%~dp0

:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: Getting the right Domain Name
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

:: Get the current Folder Name (this will be our domain!):
for %%i in ("%~dp0.") do set "currentFolderName=%%~nxi"

:: Define the subdomain that you want to add to the local server and it's local domain root folder:
set "defaultDomainName=dev.%currentFolderName%"
set documentRoot=%~dp0

:: Überprüfen, ob das letzte Zeichen im Pfad ein \ ist
if "%documentRoot:~-1%"=="\" (
	:: Ja, das letzte Zeichen ist ein \, es muss für später entfernt werden:
    set "documentRoot=%documentRoot:~0,-1%"
)

set "documentRoot=%documentRoot%\httpdocs"


:askForDomainName
:: Ask user for the domain Name
set "subDomainName="
set /p "subDomainName=Welcher Domainname soll verwendet werden? [q = Abbruch | Eingabetaste = Standard: %defaultDomainName%]: "

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


:startScript
echo.
echo Der verwendete Domainname ist: %subDomainName%
echo.



:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: 2. vhosts-Datei bearbeiten:
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

:: Define the path to the httpd-vhosts.conf file
set vhostFile=C:\xampp\apache\conf\extra\httpd-vhosts.conf

set "searchString=ServerName %subDomainName%"
findstr /C:"%searchString%" "%vhostFile%" >nul
if %errorlevel% equ 0 (
	:: Die Site ist bereits in Apache konfiguriert...
	echo %vhostFile% is already configured for %subDomainName%...
	echo.

) else (
	:: Append the virtual host configuration to the file
	echo Adding VirtualHost configuration to %vhostFile%
	echo.


	:: Define the content to add
	echo. >> "%vhostFile%"
	echo ^<VirtualHost ^*:80^> >> "%vhostFile%"
	echo     ServerAdmin webmaster@%subDomainName% >> "%vhostFile%"
	echo     DocumentRoot "%documentRoot%" >> "%vhostFile%"
	echo     ServerName %subDomainName% >> "%vhostFile%"
	echo     ErrorLog "logs/%subDomainName%.error.log" >> "%vhostFile%"
	echo     CustomLog "logs/%subDomainName%.access.log" common >> "%vhostFile%"
	echo ^</VirtualHost^> >> "%vhostFile%"

)

:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: 3. System Hosts-Datei bearbeiten:
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

:: Define the path to the System Hosts file
set systemHostsFile=%SystemRoot%\System32\drivers\etc\hosts

set "searchString=127.0.0.1 %subDomainName%"
findstr /C:"%searchString%" "%systemHostsFile%" >nul
if %errorlevel% equ 0 (
	:: Die Site ist bereits konfiguriert...
	echo %systemHostsFile% is already configured for %subDomainName%...
	echo.

) else (
	:: Update hosts file to redirect hostname to local server:
	echo.>> %SystemRoot%\System32\drivers\etc\hosts & echo 127.0.0.1 %subDomainName% >> %SystemRoot%\System32\drivers\etc\hosts

	echo Local host successfully added to %SystemRoot%\System32\drivers\etc\hosts for %subDomainName%.
	echo.

)

:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: 4. Directory in httpd.conf als valides Web Directory freigeben:
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

set "apacheConf=C:\xampp\apache\conf\httpd.conf"

:: Wir brauchen das aktuelle Verzeichnis mit / statt mit \, beispielsweise d:/git/my-site.ch:
set "currentDir=%documentRoot%"
:: Jetzt Backslashes (\) in Forward-Slashes (/) umkehren:
set "currentDir=%currentDir:\=/%"

set "searchString=<Directory \"%currentDir%\""
findstr /C:"%searchString%" "%apacheConf%" >nul
if %errorlevel% equ 0 (
	:: Das Directory ist bereits in httpdconf freigegeben...
	echo %apacheConf% is already configured for delivering %currentDir%
	echo.

) else (
	(
		:: Das Directory D:\git ist noch nicht freigegeben wir holen das nach...
		echo.
		echo ^<Directory "%currentDir%"^>
		echo    # Possible values for the Options directive are "None", "All",
		echo    # or any combination of:
		echo    #   Indexes Includes FollowSymLinks SymLinksifOwnerMatch ExecCGI MultiViews
		echo    #
		echo    # Note that "MultiViews" must be named *explicitly* --- "Options All"
		echo    # doesn^'t give it to you.
		echo    #
		echo    # The Options directive is both complicated and important.  Please see
		echo    # http://httpd.apache.org/docs/2.4/mod/core.html#options
		echo    # for more information.
		echo    #
		echo    Options Indexes FollowSymLinks Includes ExecCGI
		echo.
		echo    # AllowOverride controls what directives may be placed in .htaccess files.
		echo    # It can be "All", "None", or any combination of the keywords:
		echo    #   AllowOverride FileInfo AuthConfig Limit
		echo    #
		echo    AllowOverride All
		echo.
		echo    # Controls who can get stuff from this server.
		echo    #
		echo    Require all granted
		echo ^</Directory^>	
		echo.

	) >> "%apacheConf%"
	
	echo %currentDir% is added as a valid directory to %apacheConf%
	echo.

)

echo.
echo Your local site is accessible at %subDomainName%
echo.
echo.
echo.


:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: 5. HTTPS für die lokale Domain/Subdomain aktivieren:
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

echo Activating HTTPS for %subDomainName%...

:: Ins Laufwerk des aktuellen Batch-Skripts wechseln:
%~d0

:: Ins Directory des aktuellen Batch-Skripts wechseln:
cd "%scriptRoot%"

:: In den Tools-Ordner wechseln:
cd tools


echo.
echo Run: install-cert.bat %subDomainName%
call install-cert.bat %subDomainName%


:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: 6. Site Specific Checks & Updating of Apache Configuration.
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: Note: You might need some site specific apache settings that are
:: are necessary for your site to run. For example, if your site
:: needs the expires_module, then add the line:
::
:: 		LoadModule expires_module modules/mod_expires.so
::
:: to your site specific file .site/httpd.conf
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

echo Check ^& Update site specific Apache Configuration...

echo Run: update-apache-configuration.vbs %subDomainName%
cscript //nologo "update-apache-configuration.vbs"



:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: 7. Site Specific Checks & Updating of PHP Configuration.
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: Note: You might need some site specific PHP settings that are
:: are necessary for your site to run. For example, if your site
:: needs the gd module, then add the line:
::
:: 		extension=gd
::
:: to your site specific file .site/php.ini
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

echo Check ^& Update site specific PHP Configuration...

echo Run: update-php-configuration.vbs %subDomainName%
cscript //nologo "update-php-configuration.vbs"



:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: 8. Install the dependencies with Composer
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

:: Ins Directory des aktuellen Batch-Skripts wechseln:
cd "%scriptRoot%"

echo Composer: Installing the dependencies...
cmd /c composer



:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: 9. Additional Steps
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: Note: You can start additional scripts here from the tools folder...
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

:: Change to the tools folder:
cd "%scriptRoot%\tools"
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: 10. Finally Restart the apache server
:::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
echo Run: apache-restart.vbs
cscript //nologo "runasuser.vbs" xampp-restart.vbs


goto :successMessage

:successMessage

	:: Mitteilung ausgeben
	:: Hole das temporäre Verzeichnis des Benutzers und erstelle eine temporäre VBS-Datei mit der Mitteilung an den Benutzer...
	set tempDir=%TEMP%
	set tempFile=%tempDir%\4e6b2fbb-8078-4eb5-8b94-fbd3ee0c3425.vbs

	:: Temporäre VBScript-Datei löschen, wenn diese bereits existiert...
	if exist "%tempFile%" (
		del "%tempFile%"
	)

	:: Temporäre VBScript-Datei im Temp-Ordner erstellen
	(
		echo WScript.Echo "Please confirm the message on screen."
		echo Msgbox ^"Your local site is accessible at %subDomainName%. Don^'t forget to restart the Xampp Apache Server.^", vbInformation, ^"Success.^"
	) > "%tempFile%"

	:: VBScript-Datei mit cscript ausführen
	cscript //nologo "%tempFile%"

	:: Temporäre VBScript-Datei löschen
	if exist "%tempFile%" (
		del "%tempFile%"
	)

:startBrowser
	:: Start the site in the default browser...
	set "filePath=%documentRoot%"

	:: Check if the root folder exists...
	if exist "%filePath%" (
		:: Yes, open the browser after a short waiting period that lets XAMPP reconfigure apache...
		echo Browser will be started in a short time... please wait for starting up Apache Web Service...
		timeout /t 15
		start https://%subDomainName%
	) else (
		echo The folder "%filePath%" does not exist. Cannot open the site in the Webbrowser. Quit.
	)


:end
pause