<?php

/**
 * Fehlerbehandlung für PHP-Dateien.
 * 
 * - Zeigt Fehlermeldungen an.
 * - Loggt Fehler in eine Datei.
 * - Zeigt den Callstack (Trace) des Fehlers an.
 * - Zeigt den Fehler in einer gut formatierten Weise an.
 * - Erzwingt die Fehlerbehandlung und strikte Codierung durch die Entwickler.
 * - Erzeugt zukunftssichereren Code.
 * 
 * @version 1.0
 * 
 * @package wellErrors
 * @category PHP
 * @author Urs Langmeier <urs_langmeier@hotmail.com>
 * @license MIT
 * 
 * @example require_once("lib/errors.php");                           // Well-Error-Modul einbinden
 * @example installWellErrorHandler();                                // Fehlerbehandlung installieren (aktivieren)
 * @example WellErrorLogFile_Activate("/var/www/error.log");          // Aktiviert das lokale Error-Log in eine Datei.
 * @example trigger_error("Hello World Error", E_USER_ERROR);         // Test 1: Zeigt eine Well-Fehlermeldung an.
 * @example echo 1 / 0;                                               // Test 2: Zeigt eine Well-Fehlermeldung an.
 * @example dig("Hier ist ein Fehler aufgetreten");                   // Zeigt eine Fehlermeldung an.
 * 
 * Experimental:
 * =============
 * @example well_CallStackAlways(false);                         // Den Callstack (Trace) nur von Fehlern anzeigen,
 *                                                                  bei denen PHP den Callstack mitliefert (Standard).
 * 
 * @example well_CallStackAlways(true);                          // - Callstack am Bildschirm: bei allen Fehlern.
 *                                                                  - Callstack Im Log: nur bei FATAL Errors.
 * 
 * @example well_CallStackAlways(true, true);                   // - Callstack am Bildschirm: bei allen Fehlern.
 *                                                                 - Callstack im Log: bei allen Fehlern.
 * Setzen des Error-Levels:
 * =========================
 * 
 * @example installWellErrorHandler(E_ALL & ~E_NOTICE, E_ALL);   // Fehlerbehandlung installieren und am Bildschirm
 *                                                                  alle Fehler anzeigen ausser Notizen,
 *                                                                  im Log alle Fehler anzeigen.
 * 
 * Notiz:
 * =============
 * In cleanErrorText() können unerwünschte Zeilen aus der Fehlermeldung entfernt werden. Funktion
 * kann angepasst werden, um unerwünschte Zeilen zu entfernen.
 * 
 */


/**
 * Installiert den Well-ErrorHandler.
 *
 * @param  int $display_level   Das Fehler-Level für Fehler, die angezeigt werden sollen:
 * 
 *                              0: Keine Fehler anzeigen
 *                              E_ERROR (1): Nur FATAL Errors anzeigen
 *                              E_ALL (32767): Alle Fehler anzeigen
 * 
 *                              Standard: E_ALL
 * 
 * @param  int $log_level      Das Level des Log für die Anzeige:
 * 
 *                              0: Keine Fehler anzeigen
 *                              E_ERROR (1): Nur FATAL Errors anzeigen
 *                              E_ALL (32767): Alle Fehler anzeigen
 * 
 *                              Standard: E_ALL
 * 
 * Die Fehler-Level sind die gleichen wie in PHP und können wie in PHP bit-weise
 * kombiniert werden:
 * 
 * E_ERROR | E_WARNING | E_NOTICE | E_DEPRECATED
 * E_USER_ERROR | E_USER_WARNING | E_USER_NOTICE | E_USER_DEPRECATED
 * 
 * Empfohlene Werte:
 * ==================
 * 
 * Development Value: Display: E_ALL, Log: E_ALL
 * Production Value: Display: 0, Log: E_ALL & ~E_DEPRECATED & ~E_STRICT
 * 
 * @return void
 * @author Urs Langmeier
 */
function installWellErrorHandler($display_level = E_ALL, $log_level = E_ALL) {
    // Initialisierung:
    global $wellLastErrorTrace;
    global $wellLogLevel_Display;
    global $wellLogLevel_Log;
    $wellLogLevel_Display = $display_level;
    $wellLogLevel_Log = $log_level;
    $wellLastErrorTrace = "";
    // Error-Handling installieren:
    register_shutdown_function('WellError');
}

function WellError ()
{   /*  Zeigt die zuletzt aufgetretenen Fehler an und loggt fatale Fehler
     *  in eine Datei.
     * 
     *  Merke:  Fatale Fehler gehen nicht durch den custom ErrorHandler
     *          WellErrorHandler() durch, sondern müssen hier verarbeitet werden.
     * 
     *******************************************************************************
     * Wichtig: hier immer mit True raus, daimt auch weitere shutdown functions()
     *          noch von PHP ausgeführt werden...
     *******************************************************************************
     *
    */
    global $wellLogLevel_Display;
    global $wellLogLevel_Log;

    if (isset($_GET['wellhideerror'])) {
        return true;
    }
    $err = error_get_last();

    if (! is_null($err)) {
        // Fehler gefunden:
        // ->Fehlermeldung anzeigen:
        $fehlertext = $err['message'];

        $title = "Oops.... hier ist etwas schiefgegangen...";
        $icon = ":(";
                
        $errType = "";
        switch ($err['type'])
        {
            case E_USER_NOTICE:
            case E_NOTICE:
                $errType = "Notice";
                $title = "Code-Überprüfung erforderlich";
                $icon = "<i class='glyphicon glyphicon-exclamation-sign' style='color:gray;vertical-align: top;'></i>";
                break;
            
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                $errType = "Deprecated";
                $title = "Code-Überprüfung erforderlich";
                break;

            case E_STRICT:
                $errType = "Strict Coding";
                $title = "Code-Überprüfung erforderlich";
                break;
     
            case E_WARNING:
            case E_USER_WARNING:
                $errType = "Warning";
                break;
     
            case E_ERROR:
            case E_USER_ERROR:
                $errType = "Error";
                break;
            
            case E_RECOVERABLE_ERROR:
                $errType = "Recoverable Error";
                break;
     
            default:
                $errType = "Other Error";
                break;
        }

        // Im Falle eines Dig()-Aufrufs:
        if (substr($fehlertext, 0, 5) === "dig()") {
            $title = "Hurray! A dig has been found!";
            $icon = "<i class='glyphicon glyphicon-paperclip' style='color:gray;vertical-align: top;'></i>";
        }

        // Fehlermeldung aufräumen:
        $fehlertext = cleanErrorText($fehlertext);

        // Trace des letzten Fehlers hinzufügen:
        global $wellLastErrorTrace;
        if ( $wellLastErrorTrace != "" ) {
            $fehlertext .= "\n\n".$wellLastErrorTrace;
        }

        // Fehlermeldung zusammenstellen:
        $sCopyURL = wellGetRelativePath($err['file']).":".$err['line'];
        $elementCopyURL = "<i class='glyphicon glyphicon-copy' style='color:gray; cursor:pointer;' onclick='navigator.clipboard.writeText(\"".$sCopyURL."\");'></i>";

        if ( function_exists("term") ) {
            // Übersetzungsfunktion vorhanden:
            // ->Notwendige Begriffe übersetzen:
            $title = term($title);
        }

        if ( defined('WELL_ERROR_LOG') ) {
            // Logge den Fehler in eine Datei:
            if ( $err['type'] == E_ERROR ) {
                // Dies ist ein FATAL Error (E_ERROR = Fatal Error, 1)
                // 
                // -> FATAL Errors müssen wir hier nochmal loggen, da diese nicht durch den
                //    custom ErrorHandler WellErrorHandler() abgefangen werden können...
                //
                $sErrorMsg = preg_replace('/\<br(\s*)?\/?\>/i', "\n", strip_tags(html_entity_decode($fehlertext)));

                // Alle absoluten Pfade in der Fehlermeldung durch relative
                // Pfadangaben ersetzen, damit die Fehlermeldung leichter zu lesen ist:
                $pattern = '/(\w?:?[\w\\\\\/]*\.php)(:\d*)/';
                $sErrorMsg = preg_replace_callback($pattern, function ($matches) {
                    $path = $matches[1];
                    $line = $matches[2];
                    return wellGetRelativePath($path) . $line;
                }, $sErrorMsg);
                // Doppelte Zeilenschaltungen entfernen:
                $sErrorMsg = preg_replace('/\n+/', "\n", $sErrorMsg);

                $sErrorLogText = $sCopyURL
                                ."\n->".$errType.";".$sErrorMsg
                                ."\n".str_repeat("-", 50)."\n";

                if ($wellLogLevel_Log & $err['type'] ) {
                    // Fehler ist im Log erwünscht:
                    // ->Fehler ins Log schreiben:
                    wellError_Write2Log($sErrorLogText, wellGetRelativePath($err['file']), $err['line']);
                } // else: Fehler ist nicht im Log erwünscht.
            }
        }

        if ( !($wellLogLevel_Display & $err['type']) ) {
            // Fehler ist am Bildschirm nicht erwünscht:
            // ->Fehler nicht anzeigen, raus hier...
            return true;
        }

        if ( strpos($_SERVER['HTTP_ACCEPT'], "text/html", 0) === false ) {
            // Kein HTML-Request:
            // -->Beispielsweise in API oder in AJAX-Aufrufen.
            // ->Nur Text ausgeben:
            ob_clean();
            echo "ERR=".$errType.";".$sCopyURL.";"
                .$fehlertext.";\n\n"
                ."wellError;DocType: ".$_SERVER['HTTP_ACCEPT'].";";
            return true;
        }

        if ( $icon != "" ) {
            $icon = trim($icon)." ";
        }
        $errormessage = "<h1>".$icon.$title."</h1>"

                        ."<br>\n\n"
                        ."<strong>Details</strong>"
                        ."<br>\n\n"                                
                        ."<pre style='overflow: auto;max-width: 100vw;'>"
                        .$fehlertext
                        ."</pre>"
                        
                        ."<strong>Type</strong>"
                        ."<br>\n\n"
                        .$errType
                        ."<br>\n\n"
                        
                        ."<br>\n\n"                        
                        ."<strong>Module</strong>"                        
                        ."<br>\n\n"

                        ."<span style='cursor:pointer;' onclick='navigator.clipboard.writeText(\"".$sCopyURL."\");'>"
                            .$err['file'].":".$err['line']." "
                        ."</span>"
                        .$elementCopyURL

                        ."<br><br>\n\n"
                        ."<b>Line</b>"
                        ."<br>\n\n"

                        ."#".$err['line'];

        // Fehlermeldung formatieren:
        // (1) -> :1
        $errormessage = preg_replace('/\((\d+)\)/', ':$1 ', $errormessage);

        // Fehlermeldung ausgeben:
        ob_clean();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
            <script>
            document.addEventListener("DOMContentLoaded", function() {
                var wellError = document.getElementById("wellError").outerHTML;
                document.body.innerHTML = wellError;
            });
            </script>            
        </head>
        <body style="background-color: #eeeeee;">
            <?php
            echo '<div id="wellError" class="container">';
            echo '<div class="well well-sm" style="margin-top:65px;">';
            $sCloseURL = parse_url($_SERVER["REQUEST_URI"])["path"]."?wellhideerror=1&".wellURIparams();
            echo("<i class='glyphicon glyphicon-remove pull-right' style='color:gray; cursor:pointer;' onclick='window.location.href=\"".$sCloseURL."\";'></i>");

            echo '<div style="text-align:left;">';
            echo $errormessage;
			echo "</div>";
            echo "</div>";
            echo '</div">';
            ?>
        </body>
        </html>
        <?php
        exit;
    } else {
        // Kein Fehler:
        // ->Raus hier...
        //
        // Merke:   Mit True raus, damit auch weitere shutdown functions() noch von PHP
        //          ausgeführt werden...
        return true;
    }
}

// Fehlermeldung aufräumen:
// - Entfernt unerwünschte Zeilen aus der Fehlermeldung
function cleanErrorText($errormessage) {
        // Falls Entrypoint im BackTrace vorkommt:
        // ->Weg damit!
        $pattern = '/(#\d+.*?entrypoint\.php.*?\n)/';
        $errormessage = preg_replace($pattern, "", $errormessage);

        /*$pattern = '/(#\d+.*?mysqli_query.*?\n)/';
        $errormessage = preg_replace($pattern, "", $errormessage);

        $pattern = '/(#\d+.*?mysql_query.*?\n)/';
        $errormessage = preg_replace($pattern, "", $errormessage);

        $pattern = '/(#\d+.*?getmixedSQLvalue.*?\n)/';
        $errormessage = preg_replace($pattern, "", $errormessage);*/

        // DB-Connect ausblenden:
        $pattern = '/(#\d+.*?abuconnectdb.*?\n)/';
        $errormessage = preg_replace($pattern, "", $errormessage);
        return $errormessage;
}

/**
 * Gibt den Debug Back Trace im aktuell angefragten Dokument aus
 *
 * @param  bool $blnHtml    True = Als HTML-formatiert ausgeben...
 *                          False = Als Text formatiert ausgeben...
 * @return string 
 * @author Urs Langmeier
 */
function wellTracePrint($blnHtml = true) {
    if ( $blnHtml ) {
        echo ("<pre>".wellTrace()."</pre>");
    } else {
        echo strip_tags(wellTrace());
    }
}

/**
 * Gibt einen gut formatierten Trace aus.
 * 
 * @param  bool $blnCleanUp     True (Standard): Bereinigt den Trace von unerwünschten Zeilen.
 * @return string
 * @author Urs Langmeier
 */
function wellTrace($blnCleanUp = true) {
    $arTrace = debug_backtrace();
    array_shift($arTrace); // Remove this function from the trace
   
    foreach ( $arTrace as $i => $arTraceItem ) {
        // File:
        $sFile = $arTraceItem['file']??"";
        $sFile = wellGetRelativePath($sFile);
        $sFunction = $arTraceItem['function']??"";
        
        if ( $blnCleanUp ) {
            // Bereinige den Trace von unerwünschten Zeilen:
            
            switch ( $sFile ) {
                case "entrypoint.php":
                    // Unerwünschte Zeile im BackTrace:
                    // ->Weg damit!
                    unset($arTrace[$i]);
                    continue 2;
                    break;
            }
            if (strpos($sFunction, "WellErrorHandler") === 0) {
                // Am Anfang der Funktion steht WellErrorHandler(
                // ->Weg damit, denn das ist unser eigener ErrorHandler.
                // ->Der stört nur im Trace.
                unset($arTrace[$i]);
                continue;
            }
        }
    }

    // Calculate the length of the longest file path:
    $iMaxFileLength = 0;
    $iMaxLineLength = 0;
    foreach ( $arTrace as $i => $arTraceItem ) {
        $sFile = $arTraceItem['file']??"";
        $sFile = wellGetRelativePath($sFile);
        $sLine = $arTraceItem['line']??"";
        if ( strlen($sFile) > $iMaxFileLength
                and strlen($sFile) <= 80
             ) {
            // Nur Dateien bis 80 Zeichen Länge berücksichtigen:
            // ->Längere Dateinamen verzerren die Ausgabe zu stark.
            $iMaxFileLength = strlen($sFile);
        }
        if ( strlen($sLine) > $iMaxLineLength ) {
            $iMaxLineLength = strlen($sLine);
        }
    }

    // Output the trace in a Well-Formatted way:
    $wellTrace = array();
    foreach ( $arTrace as $i => $arTraceItem ) {
        // File:
        $sFile = $arTraceItem['file']??"";
        $sFile = wellGetRelativePath($sFile);

        // Fülle den Dateinamen mit Blanks auf:
        $sFile = str_pad($sFile, $iMaxFileLength, " ", STR_PAD_LEFT);
        
        $sLine = $arTraceItem['line']??"";
        $sLine = str_pad($sLine, $iMaxLineLength, " ", STR_PAD_RIGHT);

        $sFunction = $arTraceItem['function']??"";

        $sClass = $arTraceItem['class']??"";
        $sType = $arTraceItem['type']??""; // Beispielsweise :: oder ->
        
        // Argumente:
        $sArgs = "";
        foreach ( $arTraceItem['args'] as $i => $arg ) {
            if ( $i > 0 ) {
                $sArgs .= ", ";
            }
            if ( is_array($arg) ) {
                $arg = "arr{".json_encode($arg)."}";
                // Beschränke die Länge des Arrays auf 80 Zeichen:
                if ( strlen($arg) > 80 ) {
                    if ( fctHasInformativeInfo(substr($arg, -40)) ) {
                        $arg = substr($arg, 0, 40) . "..." . substr($arg, -40);
                    } else {
                        $arg = substr($arg, 0, 80);
                    }
                }
                $sArgs .= wellCleanArgumentString($arg);
            } else if ( is_object($arg) ) {
                $sArgs .= "Object";
            } else if ( is_null($arg) ) {
                $sArgs .= "null";
            } else {
                if ( is_string($arg) ) {
                    // Beschränke die Länge des Strings auf 80 Zeichen:
                    if ( strlen($arg) > 80 ) {
                        if ( fctHasInformativeInfo(substr($arg, -40)) ) {
                            $arg = substr($arg, 0, 40) . "..." . substr($arg, -40);
                        } else {
                            $arg = substr($arg, 0, 80);
                        }
                    }
                    $arg = '"'.wellCleanArgumentString($arg).'"';
                }
                $sArgs .= $arg;
            }
        }
        
        // Ausgabe mit und ohne Klasse:
        $wellTrace[] = $sFile.":".$sLine." ".$sClass.$sType.$sFunction."(".$sArgs.");";
        
    }
    $strWellTrace = implode("\n", $wellTrace);

    // Platziere vor den Dateinamen ein Icon zum Kopieren der Datei-URL:
    $strWellTrace = preg_replace_callback('/(\S.*\w*\.php:\d*)/', function ($matches) {
        return "<i class='glyphicon glyphicon-copy' style='color:gray; cursor:pointer;' onclick='navigator.clipboard.writeText(\"".$matches[1]."\");'></i> <strong>".$matches[1]."</strong>";
    }, $strWellTrace);

    return $strWellTrace;
}

function wellGetRelativePath($sFile) {
    $sFile = str_replace("\\", "/", $sFile);
    $sFile = str_replace($_SERVER['DOCUMENT_ROOT'], "", $sFile);
    
    if ( substr($sFile, 0, 1) == "/" ) {
        $sFile = substr($sFile, 1);
    }
    return $sFile;
}

/* Gibt einen Debug-Text aus um im Code nach einem Bug zu diggen.
*/
function dig($text = "")
{
    // throw a dig() msg error
    $errText = "dig() found";
    if ( $text != "" ) {
        $errText .= ":\n**".$text."**\n in:";
    } else {
        $errText.= " in:";
    }

    $errText.= "\n".wellTrace();

    trigger_error($errText, E_USER_NOTICE);
    
    if ( function_exists("abuLogEvent") ) {
        abuLogEvent("dig", wellGetRelativePath(__FILE__)
                            ." ".$errText);
    }
    //echo $errText;
}

/**
 * Aktiviert das lokale Error-Log in eine Datei.
 *
 * @param string $errorLogFilePath      Pfad zur Log-Datei
 * 
 * @param bool $blnAppend               True: Fehler anhängen, False: Mit leerer Log-Datei beginnen.
 * 
 *                                      Default: True
 * 
 *                                      Wichtig: wenn Sie AJAX-Aufrufe testen, dann setzen Sie $blnAppend auf True,
 *                                               um die Fehlermeldungen in AJAX-Scripts zu speichern.
 * 
 * @param bool $blnUniqueErrors         True: Nur Fehlermeldungen an Fehlerpositionen speichern, die noch nicht
 *                                            bereits im Log drin sind.
 * 
 *                                      False (Default): Alle Fehlermeldungen im Log speichern.
 * 
 * @return str 
 * @author Urs Langmeier
 */
function WellErrorLogFile_Activate($errorLogFilePath, $blnAppend = true, $blnUniqueErrors = false) {
    define("WELL_ERROR_LOG", $errorLogFilePath);
    if ( !$blnAppend ) {
        // Log-Datei leeren:
        file_put_contents(WELL_ERROR_LOG, "");
    }
    set_error_handler('WellErrorHandler');
    if ( $blnUniqueErrors ) {
        // Nur einzigartige Fehlermeldungen im Log speichern:
        define("WELL_ERROR_LOG_UNIQUE", true);        
    } else {
        define("WELL_ERROR_LOG_UNIQUE", false);
    }
}

/**
 * Gibt die URI-Parameter als String zurück.
 *
 * @return str 
 * @author Urs Langmeier
 */
function wellURIparams() {
    $sParams = "";
    foreach ( $_GET as $key => $value ) {
        if ( $key == "wellhideerror" ) {
            continue;
        }
        if ( $sParams != "" ) {
            $sParams .= "&";
        }
        $sParams .= $key."=".$value;
    }
    return $sParams;
}

/**
 * Experimental: Den Callstack (Trace) aller Fehler anzeigen. Ist in den meisten Fällen unnötig, da von Haus aus
 *               bereits der Callstack des Fehlers angezeigt wird, wo notwendig. Das reicht in den allermeisten
 *               Fällen aus.
 *
 * @param  string $blnScreenAlwaysLogFatal              True: Immer den Callstack (Trace) des Fehlers am Bildschirm anzeigen
 *                                                            und im Log nur bei FATAL Errors (E_ERROR) anzeigen.
 * 
 *                                                      False: Nur anzeigen, wenn PHP den Callstack mitliefert (Standard).  
 * 
 * @param  string $blnCallStackToLogOnlyOnFatalErrors   True: Im Log auch immer den Callstack (Trace) anzeigen.
 *                                                      False: Im Log nur bei FATAL Errors (E_ERROR) anzeigen.
 * 
 *                                                      Default: False (nur bei FATAL Errors).
 * 
 * Notiz: Der zweite Parameter kann nur aktiviert werden, wenn der erste auch aktiviert wird.
 *
 * @example well_CallStackAlways(true);    // Den Callstack (Trace) aller Fehler anzeigen.
 * @example well_CallStackAlways(false);   // Den Callstack (Trace) nur anzeigen, wenn PHP ihn mitliefert (Standard).
 * @example well_CallStackAlways(true, true); // Den Callstack (Trace) aller Fehler anzeigen und im Log speichern.
 * @example 
 * @return str 
 * @author Urs Langmeier
 */
function well_CallStackAlways($blnScreenAlwaysAndLogFatal, $blnLogAlways = false) {
    // Callstack auf dem Screen:
    if ( $blnScreenAlwaysAndLogFatal ) {
        define("CALL_STACK_ALWAYS", true);
    } else {
        define("CALL_STACK_ALWAYS", false);
    }
    // Callstack im Log always (nicht nur Fatal):
    if ( $blnLogAlways ) {
        define("CALL_STACK_LOG_ALWAYS", true);
    } else {
        define("CALL_STACK_LOG_ALWAYS", false);
    }
}

/**
 * Speichert den Callstack (Trace) des aktuellen Fehlers in einer globalen Variable ab,
 * damit dieser in der WellError-Funktion später ausgegeben werden kann.
 *
 * @param  string $errno
 * @param  string $errstr
 * @param  string $errfile
 * @param  string $errline
 * @return string
 * @author Urs Langmeier
 */
function WellErrorHandler($errno, $errstr, $errfile, $errline) 
{
    // Initialisierung:
    global $wellLogLevel_Display;
    global $wellLogLevel_Log;
    $strTrace = "";

    if ( defined('CALL_STACK_ALWAYS') && CALL_STACK_ALWAYS ) {
        // Experimental:
        // ->Immer den Callstack (Trace) des Fehlers anzeigen.
        global $wellLastErrorTrace;
        if (substr($errstr, 0, 5) === "dig()") {
            // Dig() macht eigenen Callstack rein!
            $wellLastErrorTrace = "";
            $strTrace = "";
        } else {
            // Bei einem normalen Fehler:
            // ->Wir liefern den CallStack wellFormatiert mit:
            $wellLastErrorTrace = wellTrace();
            $strTrace = $wellLastErrorTrace;
        }
    }

    if ( defined('WELL_ERROR_LOG') ) {
        // Logge den Fehler in eine Datei:
        // <br> in \n umwandeln, und HTML-Tags entfernen:
        $sErrMsg = preg_replace('/\<br(\s*)?\/?\>/i', "\n", strip_tags(html_entity_decode($errstr)));
        
        $errType = "";
        switch ($errno)
        {
            case E_USER_NOTICE:
            case E_NOTICE:
                $errType = "Notice";
                break;
            
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                $errType = "Deprecated";
                break;

            case E_STRICT:
                $errType = "Strict Coding";
                break;
        
            case E_WARNING:
            case E_USER_WARNING:
                $errType = "Warning";
                break;
        
            case E_ERROR:
            case E_USER_ERROR:
                $errType = "Error";
                break;
            
            case E_RECOVERABLE_ERROR:
                $errType = "Recoverable Error";
                break;
        
            default:
                $errType = "Other Error";
                break;
        }

        // Alle absoluten Pfade in der Fehlermeldung durch relative
        // Pfadangaben ersetzen, damit die Fehlermeldung leichter zu lesen ist:
        $pattern = '/(\w?:?[\w\\\\\/]*\.php)(:\d*)/';
        $sErrMsg = preg_replace_callback($pattern, function ($matches) {
            $path = $matches[1];
            $line = $matches[2];
            return wellGetRelativePath($path) . $line;
        }, $sErrMsg);
        // Doppelte Zeilenschaltungen entfernen:
        $sErrMsg = preg_replace('/\n+/', "\n", $sErrMsg);

        // Trace hinzufügen:
        if ( defined('CALL_STACK_LOG_ALWAYS') && CALL_STACK_LOG_ALWAYS ) {
            // Trace ist bei allen Fehlern im Log erwünscht:
            // ->Hinzufügen
            // ->Bei FATAL Errors wird der Trace sowieso immer hinzugefügt von ->WellError().
            // Vorher entfernen wir aber noch das HTML für unseren Log-Eitnrag...
            $strTrace = preg_replace('/\<br(\s*)?\/?\>/i', "\n", strip_tags(html_entity_decode($strTrace)));
            $sErrMsg .= "\n".$strTrace;
        }
        
        // Datei-Pfad simplifizieren (relativ machen):
        $sFileSimplified = wellGetRelativePath($errfile);

        // Logeintrag wie folgt zusammenstellen:
        // test.php:2
        // ->Warning;Undefined array key "name"
        $sErrorLogText = $sFileSimplified.":".$errline
                          ."\n->".$errType.";".$sErrMsg
                          ."\n".str_repeat("-", 50)."\n";

        if ($wellLogLevel_Log & $errno) {
            // Fehler ist im Log erwünscht:
            // ->Fehler ins Log schreiben:
            wellError_Write2Log($sErrorLogText, $sFileSimplified, $errline);
        }
    }

    if ($wellLogLevel_Display & $errno) {
        // Fehler ist am Bildschirm erwünscht:
        // Der normale ErrorHandler von PHP muss dann ebenfalls verarbeitet werden,
        // damit die Fehlermeldung auch in der WellError-Funktion angezeigt wird.
        // ->Deshalb wird der Fehler hier einfach ignoriert und False zurückgegeben.
        return false;
    } else {
        // Der Fehler wird nicht im Well angezeigt:
        return true;
    }
}

/**
 * Schreibt einen Eintrag ins Log. Falls nur einzigartige Fehler gewünscht sind,
 * wird der Eintrag nur geschrieben, wenn er noch nicht im Log vorhanden ist.
 *
 * @param  string $sErrorLogText
 * @return str 
 * @author Urs Langmeier
 */
function wellError_Write2Log($sErrorLogText, $file, $line) {
    if ( defined('WELL_ERROR_LOG_UNIQUE') && WELL_ERROR_LOG_UNIQUE ) {
        // Nur einzigartige Fehlermeldungen im Log speichern:
        // ->Prüfen, ob der Eintrag bereits im Log vorhanden ist:
        $strErrorLogFileContent = file_get_contents(WELL_ERROR_LOG);
        $strErrorLine2Check = $file.":".$line;
        if ( strpos($strErrorLogFileContent, $strErrorLine2Check) !== false ) {
            // Eintrag bereits im Log vorhanden:
            return;
        }
    }
    // Eintrag ins Log schreiben:
    if (file_put_contents(WELL_ERROR_LOG, $sErrorLogText, FILE_APPEND) === false) {
        chmod(WELL_ERROR_LOG, 0777);
        file_put_contents(WELL_ERROR_LOG, $sErrorLogText, FILE_APPEND);
    }
}

/**
 * Prüft, ob ein String eine informative Information enthält.
 *
 * @param  string $string   Der zu prüfende String.
 * @return boolean          True = Ja, der String enthält eine informative Information.
 * @author Urs Langmeier
 */
function fctHasInformativeInfo($string) {
    $string = strtolower($string);
    // Durch den String loopen, und schauen, ob etwas anderes da ist als nur
    // Tabulatoren, Zeilenschaltungen, Leerzeichen und Sonderzeichen:
    for ( $i = 0; $i < strlen($string); $i++ ) {
        $char = substr($string, $i, 1);
        if ( $char == " " or $char == "\t" or $char == "\n" ) {
            // Leerzeichen, Tabulatoren und Zeilenschaltungen ignorieren:
            continue;
        }
        if ( ctype_alnum($char) ) {
            // Alphanumerisches Zeichen gefunden:
            return true;
        }
    }
}

/**
 * Bereinigt einen String von unerwünschten Zeichen.
 * Für die Anzeige von Funktions-Argumenten in Fehlermeldungen.
 * 
 * Zu bereiningende Zeichen:
 * - Zeilenschaltungen
 * - Tabulatoren
 * - HTML-Tags
 * - Doppelte Leerzeichen
 *
 * @return string
 * @author Urs Langmeier
 */
function wellCleanArgumentString($p_ArgumentAsString) {
    // Argumente bereinigen:
    $p_ArgumentAsString = str_replace("\n", '\n', $p_ArgumentAsString);
    $p_ArgumentAsString = str_replace("\r", '\r', $p_ArgumentAsString);
    $p_ArgumentAsString = str_replace("\t", '\t', $p_ArgumentAsString);
    $p_ArgumentAsString = strip_tags($p_ArgumentAsString);
    $p_ArgumentAsString = preg_replace('/\s+/', ' ', $p_ArgumentAsString);
    return $p_ArgumentAsString;
}