Option Explicit

'::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
':: 1. Updating of Apache Configuration for site specific needs.
'::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
':: You might need some site specific apache settings that are
':: are necessary for your site to run. For example, if your site
':: needs the expires_module, then add the line:
'::
':: 		LoadModule expires_module modules/mod_expires.so
'::
':: to your site specific file .site/httpd.conf
'::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

Dim sourceFile, targetFile, fso, sourceStream, targetStream, line, found, targetStreamForCheck
dim scriptFolder, objScript, blnFirst
Dim sScriptPath, sParentFolder, sSubDomainName

' FileSystemObject erstellen
Set fso = CreateObject("Scripting.FileSystemObject")

Set objScript = fso.GetFile(WScript.ScriptFullName)

WScript.Echo "Skriptordner: " & fso.GetParentFolderName(objScript)

scriptFolder = fso.GetParentFolderName(objScript)
sourceFile = scriptFolder & "\..\.site\httpd.conf"
targetFile = "C:\xampp\apache\conf\httpd.conf"


' Überprüfen, ob die Quelldatei existiert
If Not fso.FileExists(sourceFile) Then
    WScript.Echo "Die Datei " & sourceFile & " wurde nicht gefunden."
    WScript.Quit 1
End If

' Überprüfen, ob die Zieldatei existiert
If Not fso.FileExists(targetFile) Then
    WScript.Echo "Die Datei " & targetFile & " wurde nicht gefunden."
    WScript.Quit 1
End If


'Subdomain-Name holen:
if WScript.Arguments.Count >= 1 then
	sSubDomainName = WScript.Arguments(0)
else
	sSubDomainName = ""
end if
if sSubDomainName = "" then
	'Es wurden keine Parameter übergeben...
	'->Ordnername des Parent-Ordners als Subdomain-Name verwenden:
	' Ermittelt den vollständigen Pfad des aktuell ausgeführten Skripts
	sScriptPath = fso.GetParentFolderName(WScript.ScriptFullName)

	' Extrahiere den Parent-Ordnernamen aus dem Pfad:
	sParentFolder = Split(sScriptPath, "\")(UBound(Split(sScriptPath, "\")) - 1)
	sSubDomainName = sParentFolder
end if


' Quelldatei öffnen
Set sourceStream = fso.OpenTextFile(sourceFile, 1)

' Zieldatei öffnen
Set targetStream = fso.OpenTextFile(targetFile, 8, True)

blnFirst = true

' Durch jede Zeile der Quelldatei gehen
Do While Not sourceStream.AtEndOfStream
    line = Trim(sourceStream.ReadLine)
	
	if instr(line, "{{YOUR_WEBSITE_NAME}}") then
		'Variable {{YOUR_WEBSITE_NAME}} durch den Domain-Namen ersetzen,
		'der übergeben wurde...
		if sSubDomainName <> "" then
			line = replace(line, "{{YOUR_WEBSITE_NAME}}", sSubDomainName)
		end if
	end if
    
    ' Prüfen, ob die gesamte Zeile bereits in der Zieldatei existiert
    found = False
    Set targetStreamForCheck = fso.OpenTextFile(targetFile, 1)
    Do While Not targetStreamForCheck.AtEndOfStream
        If left(targetStreamForCheck.ReadLine, len(line)) = line Then
            found = True
            Exit Do
        End If
    Loop
    targetStreamForCheck.Close
    
    ' Zeile hinzufügen, wenn sie nicht gefunden wurde
    If Not found Then
		
		if blnFirst Then
			'Blank line einfügen zum Trennen unserer Einstellungen:
			targetStream.WriteLine ""
			'Nur in der ersten eingefügten Zeile eine Leerzeile einfügen:
			blnFirst = False
		end if
		
		'Einstellungs-Zeile einfügen:
		targetStream.WriteLine line
		
		if left(line, 1) <> "#" then
			WScript.Echo "Added setting """ & line & """ to httpd.conf."
		'else: Kommentare nicht melden
		end if
    Else
		if left(line, 1) <> "#" then
			WScript.Echo "Setting """ & line & """ exists already in httpd.conf."
		'else: Kommentare nicht melden
		end if
    End If
Loop

' Dateien schließen
sourceStream.Close
targetStream.Close

WScript.Echo "Apache Configuration checked / updated successfully."
