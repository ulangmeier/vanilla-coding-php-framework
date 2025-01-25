<?php

    register_shutdown_function('documentEndChecks');

    /**
     * Im HTML-Header platziert, fügt diese Funktion alle benötigten Libraries in das HTML-Dokument
     * ein.
     * 
     * @param string $strAdditionalModules Zusätzliche Libraries, die geladen werden sollen
     *  (durch Kommas getrennt)
     * 
     *  Beispiel: `"animate,font-awesome"`
     * 
     * Gut zu wissen:
     * --------------
     * 
     * - Die Libraries müssen in der Datei "lib/libraries.json" definiert sein.
     * 
     * - Die Libraries werden in der Reihenfolge geladen, wie sie da definiert sind.
     * 
     * - Die Libraries, die "loadOnDemand": true eingestellt haben, werden nur geladen,
     *   wenn sie explizit im Parameter $strAdditionalModules angegeben sind. So können
     *   Sie selten benötigte Libraries nur in einzelnen HTML-Dokumenten laden.
     * 
     * - Alle anderen Libraries werden immer geladen, sofern sie nicht bereits geladen wurden.
     * 
     *
     * Folgende Dinge werden von libraries() zusätzlich noch mit geladen:
     * -------------------------------------------------------------------
     * 
     * - `index.css` oder `main.css`, falls vorhanden
     * 
     * - Seitenspezifisches Stylesheet: eine `.css-Datei` mit dem aktuellen Skriptnamen,
     *   falls vorhanden.
     * 
     * Wichtig / jsLateLoad:
     * --------------------
     * 
     * Mit einem jsLateLoad kann die Webseite für den Benutzer schneller geladen werden:
     * 
     * -> Nicht alle Libraries werden mit libraries() sofort geladen! Ein Teil der nicht
     *    am Anfang des HTML-Dokuments benötigten Libraries wird erst beim Aufruf von shutdown()
     *    geladen.
     * 
     *    Wenn jsLateLoad aktiviert ist, dann wird das JavaScript der Library
     *    erst beim Aufruf von shutdown() in das HTML-Dokument geladen.
     * 
     * -> Vergessen Sie deshalb nicht, die Funktion shutdown() vor dem Ende des Body-Tags
     *    aufzurufen.
     * 
     * @see shutdown()
     * 
     * ----------------------------------------------------------------------------------------------
     * 
     * Beispiel-Aufruf der beiden Funktionen libraries() und shutdown():
     * ------------------------------------------------------------------
     * ```php
     * <!DOCTYPE html>
     * <html>
     *      <head>
     *          ...
     *          <?php libraries("chartjs,animate"); ?>
     *      </head>
     *      <body>
     *          ...
     *          <?php shutdown(); ?>
     *      </body>
     *  </html>
     * ```
     * 
     * @author Urs Langmeier
     * 
     */
    function libraries($strAdditionalModules = "") {

        // Festhalten, dass die Libraries bereits geladen wurden.
        // -> Dies wird beim Beenden des Dokuments geprüft.
        global $globalLibrariesprocessed;
        $globalLibrariesprocessed = true;

        // Input harmonisieren:
        if ( $strAdditionalModules != "" ) {
            $strAdditionalModules = "," . $strAdditionalModules . ",";
            $strAdditionalModules = lcase($strAdditionalModules);
            $strAdditionalModules = replace($strAdditionalModules, ".", "");
            $strAdditionalModules = trim($strAdditionalModules);
        }

        $libraries = file_get_contents("lib/libraries.json");
        $libraries = json_decode($libraries, true);
        $libraries = $libraries['libraries'];
    
        foreach ($libraries as $library) {
            if ( $library['loadOnDemand'] ) {
                // Die Bibliothek wird nur bei Gebrauch geladen...
                if ( $strAdditionalModules == "" ) {
                    // Es wurden keine zusätzlichen Module angegeben...
                    // -> Lade die Bibliothek nicht:
                    continue;
                } else {
                    // Es wurden zusätzliche Module angegeben...
                    // -> Lade die Bibliothek, wenn sie benötigt wird:

                    // Aus Library-Namen mögliche Punkte (.) entfernen
                    // -> Es soll "chartjs" als Library hinzugefügt werden können, nicht "chart.js".
                    //    Beides soll möglich sein.
                    $libName = lcase($library['name']);
                    $libName = str_replace(".", "", $libName);
                    $libName = trim($libName);

                    if ( instr($strAdditionalModules, ",".$libName."," ) ) {
                        // Die Bibliothek wird jetzt benötigt...
                        // -> Lade die Bibliothek:
                        require_library($library['name']);
                    } else {
                        // Die Bibliothek wird nicht benötigt...
                        // -> Lade die Bibliothek nicht:
                        continue;
                    }
                }
            } else {
                // Die Bibliothek wird immer geladen...
                // -> Lade die Bibliothek:
                require_library($library['name']);
            }
        }

        // Aktuelles Stylesheet (falls eine .css-Datei mit dem aktuellen PHP-Skriptnamen
        // existiert):
        $currentStylesheet = basename($_SERVER['SCRIPT_FILENAME'], '.php') . '.css';
        if (file_exists($currentStylesheet)) {
            echo '<link rel="stylesheet" href="'.$currentStylesheet.'">';
        } /* else {
            consoleLog("Seitenspezifisches Stylesheet ".$currentStylesheet." existiert nicht!");
        }*/

        // index.css oder main.css (falls vorhanden):
        if (file_exists("index.css")) {
            echo '<link rel="stylesheet" href="index.css">';
        }
        if (file_exists("main.css")) {
            echo '<link rel="stylesheet" href="main.css">';
        }
        if (file_exists("css/main.css")) {
            echo '<link rel="stylesheet" href="/css/main.css">';
        }        
        
    }

    /**
     * Inkludiert eine einzelne CSS / JavaScript Library in das aktuelle HTML-Dokument.
     * 
     * Die Library muss in der Datei "lib/libraries.json" definiert sein.
     *
     * @param  string $libName Der Name der CSS / JavaScript Library
     * @return void
     * 
     * Wichtig:
     * ========
     * 
     * Es wird empfohlen, die Funktion libraries() zu verwenden, um alle benötigten Libraries
     * in das HTML-Dokument einzufügen.
     * 
     * @see libraries()
     * 
     * jsLateLoad:
     * ===========
     * 
     * Nicht alle Arten von Libraries werden mit diesem Befehl sofort geladen:
     * 
     * Wenn jsLateLoad aktiviert ist, dann wird das JavaScript der Library
     * erst später beim Aufruf von libraries_LateLoad() geladen.
     *
     * -> So kann die Webseite für den Benutzer schneller geladen werden.
     * 
     * @see libraries_LateLoad()
     * 
     * @example require_library("chartjs");
     * @example require_library("bootstrap", "3.4.1");
     * 
     */
    function require_library($libraryName, $libraryVersion = "locked") {

        // Library-Name in Kleinbuchstaben umwandeln:
        $libName = strtolower($libraryName);

        // Aus Library-Namen mögliche Punkte (.) entfernen
        // -> Es soll "chartjs" als Library hinzugefügt werden können, nicht "chart.js".
        //    Beides soll möglich sein.
        $libName = str_replace(".", "", $libName);
        $libName = trim($libName);
        $libVer = $libraryVersion;
        if ( $libVer == "" ) {
            $libVer = "locked";
        }

        global $globalRequiredLibraries;
        if (!isset($globalRequiredLibraries)) {
            $globalRequiredLibraries = array();
        }

        global $globalRequiredLibrariesToLoadLater_JS;
        if (!isset($globalRequiredLibrariesToLoadLater_JS)) {
            $globalRequiredLibrariesToLoadLater_JS = array();
        }
        
        // Wurde die Library bereits hinzugefügt?
        if (in_array($libName, $globalRequiredLibraries)) {
            // Ja!
            // -> Nichts tun. Nur einmal hinzufügen.
            consoleLog("Library wurde bereits hinzugefügt: ".$libName);
            return;
        }

        // Anfügen dieser Library zu den bereits hinzugefügten Libraries:
        $globalRequiredLibraries[] = $libName;

        // Initialisierung:
        $cssLink = "";
        $jsLink = "";
        $jsLateLoad = false;

        // Welches Modul soll geladen werden?
        // ->Dazu die libraries.json einlesen:
        $libraries = file_get_contents("lib/libraries.json");
        $libraries = json_decode($libraries, true);
        $libraries = $libraries['libraries'];

        // Durchsuchen der Libraries:
        $blnFound = false;
        foreach ($libraries as $library) {
            if ( str_replace(".", "", strtolower($library['name'])) == $libName ) {
                if ( $libVer != "locked" ) {
                    // Version ist angegeben die gewünscht ist:
                    if ( $library['locked']['version'] != $libVer ) {
                        // Version der locked version stimmt nicht überein!
                        // -> Hol explizit diese Version:
                        if ( array_key_exists($libVer, $library) ) {
                            $blnFound = true;
                        } else {
                            // Version nicht gefunden!
                            // ->Weiter suchen, denn vielleicht wurde die Library
                            //   mit demselben Namen, aber einer anderen Version in einem
                            //   weiteren Library-Item später noch definiert...
                            continue;
                        }
                    } else {
                        // Version stimmt mit der locked-Version überein!
                        $libVer = "locked";
                        $blnFound = true;
                    }
                }
                // Library gefunden!
                $blnFound = true;
                break;                
            }
        }

        if (!$blnFound) {
            consoleLog("Library nicht gefunden: ".$libraryName." ".$libVer);
            return;
        } else {
            // Library gefunden!
            // consoleLog("Library wird geladen: ".$libraryName);

            $cssLink = $library[$libVer]["css"];
            $jsLink = $library[$libVer]["js"];
            $jsLateLoad = $library["jsLateLoad"];

            if ( $cssLink != "" ) {
                // CSS-Link hinzufügen:
                echo "<!-- " . $library['name'] . " -->";
                echo '<link rel="stylesheet" href="'.$cssLink.'">'."\n";
            }
            if ( $jsLink != "" ) {
                if ($jsLateLoad) {
                    // JS-Link später hinzufügen:
                    // consoleLog("Late Load (JS): ".$libraryName);
                    $javaScript = "<!-- " . $library['name'] . " -->"
                                 .'<script src="'.$jsLink.'"></script>'."\n";
                    $globalRequiredLibrariesToLoadLater_JS[] = $javaScript;
                
                } else {
                    // JS-Link hinzufügen:
                    echo "<!-- " . $library['name'] . " -->";
                    echo '<script src="'.$jsLink.'"></script>'."\n";
                }
            }
        }
    }

    /**
     * Inkludiert die Libriaries für die Webseite die spät geladen werden sollen.
     *
     * @return str 
     * @author Urs Langmeier
     * 
     * Diese Funktion muss am Ende des Body-Tags im HTML-Dokument aufgerufen werden.
     * 
     */
    function libraries_LateLoad() {

        // Festhalten, dass die spät geladenen Libraries bereits verarbeitet wurden:
        // -> Dies wird beim Beenden des Dokuments geprüft.
        global $globalLateLoadedLibrariesProcessed;
        $globalLateLoadedLibrariesProcessed = true;

        // Interne Hauptfunktionen (Mainfunctions):
        $libURL = "lib/mainfunctions.js";
        echo "\n".'<script src="'.$libURL.'"></script>'."\n";

        // Aktuelles Skript:
        // -> Falls eine .js-Datei mit dem aktuellen PHP-Skriptnamen
        //    existiert...
        $currentScript = basename($_SERVER['SCRIPT_FILENAME'], '.php') . '.js';

        if (file_exists($currentScript)) {
            echo '<script src="'.$currentScript.'"></script>';
        } /* else {
            consoleLog($currentScript." existiert nicht!");
        }*/

        // Module, die zum später laden hinzugefügt wurden
        // werden hier geladen:
        global $globalRequiredLibrariesToLoadLater_JS;
        if (!empty($globalRequiredLibrariesToLoadLater_JS)) {
            foreach ($globalRequiredLibrariesToLoadLater_JS as $lateLoadJavaScript) {
                echo $lateLoadJavaScript;
            }
        }
    
    }

    /**
     * Log to the Browser Console
     *
     * @param  string $message
     * @param  boolean $blnError (optional) Default: false (no error) true = error
     * @return void
     * @author Urs Langmeier
     */
    function consoleLog($message, $blnError = false) {
        $message = str_replace("\n", '\n', $message);
        $message = str_replace("\r", '\r', $message);
        $message = str_replace('"', '\"', $message);
        $message = str_replace("'", "\'", $message);

        if ( $blnError ) {
            // Fehler anzeigen:
            echo "<script>console.error('" . $message . "');</script>";
        } else {
            // Normale Meldung:
            echo "<script>console.log('". $message . "');</script>";
        }
    }

    /**
    * Diese Funktion wandelt einen HEX-Wert in einen RGBA-Wert um
    * @param string $hex Der HEX-Wert, der umgewandelt werden soll
    * @param float $alpha Der Alpha-Wert (Transparenz) des RGBA-Werts
    * @return string|null Der RGBA-Wert oder null, falls der HEX-Wert ungültig ist
    */
    function hex2Rgba($hex, $alpha = 0.4) {
        // Entferne das Hash (#) Symbol, falls vorhanden
        $hex = ltrim($hex, '#');

        // Überprüfe, ob der HEX-Wert korrekt ist (6 oder 3 Zeichen)
        if (strlen($hex) === 6) {
            list($r, $g, $b) = [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
        } elseif (strlen($hex) === 3) {
            list($r, $g, $b) = [hexdec(str_repeat(substr($hex, 0, 1), 2)), hexdec(str_repeat(substr($hex, 1, 1), 2)), hexdec(str_repeat(substr($hex, 2, 1), 2))];
        } else {
            return null; // Ungültiger HEX-Wert
        }

        // Erstelle den rgba-Wert
        return "rgba($r, $g, $b, $alpha)";
    }

    /** 
     *  Ersetzt alle <br> und <p> Tags durch Zeilenumbrüche
     *  @param string $string Der String, in dem die Tags ersetzt werden sollen
     *  @return string Der String mit den ersetzten Tags.
     */
    function br2nl($string){
        $string = preg_replace('/\<p(\s*)?\/?\>/i', "\n", $string);
        $string = replace($string, "</p>", "\n");
        return preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
    }

    // ula, 02.10.2024
    /**
     * Funktion zum Umwandeln eines Arrays in einen String
     * 
     * @param array $arr        Das Array, das umgewandelt werden soll
     * @param boolean $blnJSON  Soll das Array als JSON umgewandelt werden?
     * @return string
     */
    function arr2String($arrayOrString, $blnJSON = false) {
        if ( is_array($arrayOrString) ) {
            // Das ist ein Array
            if ( $blnJSON ) {
                return json_encode($arrayOrString);
            } else {
                return print_r($arrayOrString, true);
            }
        
        } else {
            // Das ist kein Array
            // ->Variablenwert umwandeln in einen String
            return (string)$arrayOrString;
        }
    }

    /** Gibt den Wert eines Arrays zurück, wenn er existiert, sonst null.
     * 
     *  Parameter:
     *    $array      Das Array, in dem der Wert geprüft wird.
     *    $key        Der Schlüssel, dessen Wert geprüft wird.
     *   
     * Return:
     *    Der Wert des Schlüssels, wenn er existiert, sonst null.
     * 
    */
    function arrVal(&$array = null, $key) {
        if (array_key_exists($key, $array)) {
            return $array[$key];
        } else {
            return null;
        }
    }

    /**
     * Gibt den Wert aus der $_POST-Variable zurück, wenn diese Variable existiert, sonst null.
     * 
     * Parameter:
     *   $key        Der Schlüssel, dessen Wert geprüft wird.
     * 
     * Return:
     *  Der Wert des Schlüssels, wenn er existiert, sonst null.
     */
    function _POST($key) {
        return arrVal($_POST, $key);
    }

    /**
     * Gibt den Wert aus der $_GET-Variable zurück, wenn diese Variable existiert, sonst null.
     * 
     * Parameter:
     *   $key        Der Schlüssel, dessen Wert geprüft wird.
     * 
     * Return:
     *  Der Wert des Schlüssels, wenn er existiert, sonst null.
     */
    function _GET($key) {
        return arrVal($_GET, $key);
    }

    /**
     * Gibt den Wert aus der $_SESSION-Variable zurück, wenn diese Variable existiert, sonst null.
     * 
     * Parameter:
     *   $key        Der Schlüssel, dessen Wert geprüft wird.
     * 
     * Return:
     *  Der Wert des Schlüssels, wenn er existiert, sonst null.
     */
    function _SESSION($key) {
        return arrVal($_SESSION, $key);
    }

    /**
     * Gibt den Wert aus der $_COOKIE-Variable zurück, wenn diese Variable existiert, sonst null.
     * 
     * Parameter:
     *   $key        Der Schlüssel, dessen Wert geprüft wird.
     * 
     * Return:
     *  Der Wert des Schlüssels, wenn er existiert, sonst null.
     */
    function _COOKIE($key) {
        return arrVal($_COOKIE, $key);
    }

    /**
     * Gibt den Wert aus der $_REQUEST-Variable zurück, wenn diese Variable existiert, sonst null.
     * 
     * Parameter:
     *   $key        Der Schlüssel, dessen Wert geprüft wird.
     * 
     * Return:
     *  Der Wert des Schlüssels, wenn er existiert, sonst null.
     */
    function _REQUEST($key) {
        return arrVal($_REQUEST, $key);
    }

    /**
     * Gibt den Wert aus der $_SERVER-Variable zurück, wenn diese Variable existiert, sonst null.
     * 
     * Parameter:
     *   $key        Der Schlüssel, dessen Wert geprüft wird.
     * 
     * Return:
     *  Der Wert des Schlüssels, wenn er existiert, sonst null.
     */
    function _SERVER($key) {
        return arrVal($_SERVER, $key);
    }

    /**
     * Gibt den Wert aus der $_FILES-Variable zurück, wenn diese Variable existiert, sonst null.
     * 
     * Parameter:
     *   $key        Der Schlüssel, dessen Wert geprüft wird.
     * 
     * Return:
     *  Der Wert des Schlüssels, wenn er existiert, sonst null.
     */
    function _FILES($key) {
        return arrVal($_FILES, $key);
    }
    
    /**
    * Gibt die Position zurück, in der $needle in $haystack ab der Position $lPos vorkommt.
    * Gibt 0 zurück, falls $needle nicht in $haystack vorkommt.
    * WICHTIG: 1-basierend!
    *
    * @param string $haystack Der String, in dem gesucht wird.
    * @param string $needle Der String, der gesucht wird.
    * @param int $lPos Die Position, ab der gesucht wird.
    * @return int
    */
    function instr($haystack, $needle, $lPos = 1)
    {	
        if ( $lPos > strlen($haystack) ) return 0;
        if ( $lPos < 1 ) $lPos = 1;
        $strpos = strpos($haystack, $needle, $lPos - 1);
        if ( $strpos !== false )
        {	// Kommt vor!
            return $strpos+1;
        } else
        {	// Kommt nicht vor!
            return 0;
        }
    }

    /**
     * Gibt die Position zurück, in der $needle in $haystack vorkommt.
     * Gibt 0 zurück, falls $needle nicht in $haystack vorkommt.
     * Ist im Gegensatz zu ->instr() rechts-basierend, das heisst es
     * es wird von rechts nach links gesucht.
     * WICHTIG: 1-basierend!
     * 
     * @param string $haystack Der String, in dem gesucht wird.
     * @param string $needle Der String, der gesucht wird.
     * @param int $lPos Die Position, ab der gesucht wird.
     * @return int
     */
    function instrRev($haystack, $needle, $lPos = 0)
    {	
        if ( $lPos == 0 ) $lPos = strlen($haystack);
        $strpos = strrpos(left($haystack, $lPos), $needle);
        if ( $strpos !== false )
        {	// Kommt vor!
            return $strpos+1;
        } else
        {	// Kommt nicht vor!
            return 0;
        }
    }

    /** Returns a string in which a specified substring has been replaced with another substring a specified number of times.
     *  Parameter:
     *      $exp            Die volle Zeichenkette
     *      $find           Die zu suchende Zeichenkette
     *      $replaceWith    Die zu ersetzende Zeichenkette
     *      $limit          Anzahl zu ersetzen (optional), Standard ist alles ersetzen.
     */
    function replace($exp, $find, $replacewith, $limit=null ) {
        if($limit) {
            return preg_replace('/'.preg_quote($find, '/').'/', $replacewith, $exp, $limit);
            
        } else {
            return str_replace($find, $replacewith, $exp);
        }
    }

    /** Gibt True zurück, wenn ein String $needle in einem anderen
     *  String $haystack vorkommt.
     * 
     *  Parameter:
     *    $haystack   Der String, in dem gesucht wird.
     *    $needle     Der String, der gesucht wird.
     * 
     *  Return:
     *  Gibt false zurück, falls $needle nicht in $haystack vorkommt.
     *  Gibt true zurück, falls $needle in $haystack vorkommt.
     */
    function contains($haystack, $needle)
    {	
        $strpos = strpos($haystack, $needle, 0);
        if ( $strpos !== false )
        {	// Kommt vor!
            return true;
        } else
        {	// Kommt nicht vor!
            return false;
        }
    }
    
    /**
     * Gibt eine Anzahl Zeichen $length im String $str zurück.
     *
     * @param  string $str
     * @param  int $length
     * @author Urs Langmeier
     */
    function left(string $str, int $length): string {
        return substr($str, 0, $length);
    }

    function right($str, $length) {
        // Gibt eine Anzahl Zeichen $length im String $str zurück, von rechts aus.
        return substr($str, -$length);
    }
    
    //VB6 Equivalent of strtolower
    //Returns a string or character converted to lowercase.
    function lcase($str) {
        return strtolower($str);
    }
    
    //VB6 Equivalent of strtoupper
    //Returns a string or character converted to uppercase.
    function ucase($str) {
        return strtoupper($str);
    }
    
    //Returns an Integer representing the ASCII character code corresponding to the first letter in a string.
    function asc($string) {
        $char = substr($string,0,1);
        return ord($char);
    }

    function mid($str, $pos, $len = 0)
    {   // Gibt aus dem String $str den Bereich ab der Position $pos zurück, mit einer Länge von
        // $len, falls $len nicht angegeben, dann bis ans Ende des Strings $str.
        if ( $len != 0 )
            return substr($str, $pos-1, $len);
        else
            return substr($str, $pos-1);

    }
    
    //VB6 Equivalent of strlen
    function len($str) {
        return mb_strlen($str);
    }

    /**
     * Gibt aus einem String einen Teilstring zurück, unter Angabe einer zu
     * suchenden Stelle.
     *
     * @param string $p_sString Der Eingabestring.
     * 
     * @param string $p_sFind Dieser Text wird gesucht. Text, der zwischen
     *                        dem Zeichen ab der durch $p_sFind gefunden Stelle,
     *                        und der durch $p_sFindTo gefundenen Stelle steht,
     *                        wird zurückgegeben. Wenn $p_sFind nicht angegeben
     *                        ist, dann wird Text ab dem ersten Zeichen zurück-
     *                        gegeben.
     * 
     * @param string $p_sFindTo Wenn Angegeben: Gibt Text nur bis zum Auftreten
     *                          dieses Zeichens zurück oder bis zum Ende des Textes.
     * 
     * @param bool $p_blnCaseSensitive Vergleichsmethode: True = Case Sensitive,
     *                                 False = Case Insensitive (Standard)
     */
    function getPartOfString($p_sString, $p_sFind, $p_sFindTo = "", $p_blnCaseSensitive = false)
    {
        $lPos = 0;
        $lPos2 = 0;
        
        if ( $p_sString != "" )
        {
            // Suche das Vorkommen von Find im String:
            if ( $p_sFind == "" )
            {   // Wenn der Begin-String nicht angegeben ist, dann
                // bitte vom ersten Zeichen weg beginnen zu suchen...
                $lPos = 0;
            } else
            {
                if ( $p_blnCaseSensitive )
                {	// Case sensitive:
                    $lPos = strpos( $p_sString, $p_sFind );
                } else
                {	// Case insensitive:
                    $lPos = stripos( $p_sString, $p_sFind );
                }
            }
            
            if ( !($lPos === false ) )
            {
                if ( strlen($p_sFindTo) > 0 )
                {
                    // Suche das FindTo vom restlichen String:
                    if ( $p_blnCaseSensitive )
                    {	// Case sensitive:
                        $lPos2 = strpos( $p_sString, $p_sFindTo, $lPos + strlen($p_sFind) );
                    } else
                    {	// Case insensitive:
                        $lPos2 = stripos( $p_sString, $p_sFindTo, $lPos + strlen($p_sFind) );
                    }
                    
                    if ( !($lPos2 === false) )
                    {	
                        return substr( $p_sString, $lPos + strlen($p_sFind), $lPos2 - ($lPos + strlen($p_sFind)));
                    }
                    else
                    {   // Findto ist nicht vorhanden...
                        // -> Den String bis zum Ende zurückgeben...
                        return substr($p_sString, $lPos + strlen($p_sFind));
                    }
                }
                else 
                {	// Ein FindTo ist nicht angegeben - Rest-String zurückgeben:
                    return substr($p_sString, $lPos + strlen($p_sFind));
                }
            } else
            {
                // ula, 16.05.2019
                // Kein String gefunden!
                // -> Leerstring zurückgeben...
                return "";
            }
        } else
        {   // Kein String da!
            return "";
        }
    }

    /**
     * Lädt vor der Beendigung des Dokuments die als LateLoad noch zu ladenden Libraries
     * (JavaScript-Dateien). Diese Libraries wurden mit libraries() oder mit require_library()
     * hinzugefügt, sind aber noch nicht in das HTML-Dokument eingefügt, weil sie erst später
     * geladen werden sollen.
     * 
     * In der Datei '/lib/libraries.json' haben diese Libraries den Wert 'jsLateLoad': true.
     * 
     * shutdown() muss zwingend vor dem Body-Tag im HTML-Dokument aufgerufen werden.
     * 
     * Platzieren Sie die Funktion shutdown() vor dem schliessenden Body-Tag:
     * ...
     * ...
     *      <?php shutdown(); ?>
     * </body>
     * </html>
     */
    function shutdown() {
        // Diese Funktion wird am Ende des Dokuments aufgerufen.
        // -> Hier werden die Libraries geladen, die später geladen werden sollen.
        libraries_LateLoad();
    }

    function documentEndChecks() {
        // Diese Hook wird am Ende des Dokuments aufgerufen.
        // -> Hier prüfen wir ein paar Dinge, ob alles richtig gelaufen ist.

        // Initialisierung:
        // Hat der Benutzer die Funktion libraries() aufgerufen?
        global $globalLibrariesprocessed;

        // Falls nein, gehen wir gleich raus ohne weiteren Checks, denn dies
        // würde z.B. bei auf AJAX basierenden Seiten, die keine Libraries benötigen,
        // zu einem Fehler führen und könnte auch beim Debuggen störend sein...
        if ( !$globalLibrariesprocessed) exit;

        // 1. Der Benutzer hat die Funktion libraries_LateLoad() aufgerufen?
        global $globalLateLoadedLibrariesProcessed;
        $blnFound = false;
        if (isset($globalLateLoadedLibrariesProcessed)) {
            if ($globalLateLoadedLibrariesProcessed) {
                // Libraries wurden bereits geladen...
                $blnFound = true;
            }
        }
        if ( !$blnFound ) {
            // Libraries wurden nicht geladen...
            $ob = ob_get_contents();
            ob_clean();
            echo str_repeat("*", 80);
            $errText = "<pre>".htmlentities("Fehler: Platzieren Sie die Funktion shutdown() vor dem schliessenden Body-Tag:\n\n"
                . "...\n"
                . "...\n"
                . "...\n"
                . "     <?php shutdown(); ?>\n"
                . "</body>\n"
                . "</html>\n")."</pre>";
            echo $errText;

            echo str_repeat("*", 80);

            echo '<pre>'.htmlentities($ob).'</pre>';
            exit;
        }
    }

    function debug($mixed_var) {
        echo "<pre>";
        print_r($mixed_var);
        echo "</pre>";
    }

    /** kst, ula 28.06.2024
     * 
     *  Gibt einen String zwischen einem Open-Tag und einem Close-Tag zurück.
     * 
     *  Im Unterschied zu getPartOfString_OpenToClose() ist getStringToCloseTag() strikt.
     *  Das bedeutet, wenn das schliessende Tag nicht gefunden wird, dann gibt diese Funktion
     *  hier gar nichts zurück.
     * 
     **/
    function getStringToCloseTag($p_sString, $p_sOpenTag = "{{", $p_sCloseTag = "}}") {
        // Initiale Positionen und Zähler
        $startPos = strpos($p_sString, $p_sOpenTag);
        if ($startPos === false) {
            return ''; // Kein öffnendes Tag gefunden
        }
    
        $startPos += strlen($p_sOpenTag); // Position direkt nach dem offenen Tag
        $openTagCount = 1; // Zähler für offene Tags
        $currentPos = $startPos; // Startposition für die Suche nach schließenden Tags
    
        while ($openTagCount > 0) {
            $nextOpenPos = strpos($p_sString, $p_sOpenTag, $currentPos);
            $nextClosePos = strpos($p_sString, $p_sCloseTag, $currentPos);
    
            if ($nextClosePos === false) {
                return ''; // Kein schließendes Tag gefunden
            }
    
            if ($nextOpenPos !== false && $nextOpenPos < $nextClosePos) {
                $openTagCount++; // Ein neues offenes Tag innerhalb der Zeichenkette gefunden
                $currentPos = $nextOpenPos + strlen($p_sOpenTag);
            } else {
                $openTagCount--; // Ein schließendes Tag gefunden
                $currentPos = $nextClosePos + strlen($p_sCloseTag);
            }
        }
    
        $endPos = $currentPos - strlen($p_sCloseTag); // Position des letzten schließenden Tags
    
        // Extrahieren und Rückgabe der Zeichenkette zwischen den äußersten Tags
        return substr($p_sString, $startPos, $endPos - $startPos);
    }

    /**
     * Gibt aus einem String einen Teilstring zurück, beginnend mit einem öffnenden Tag
     * und endend mit einem sich schliessenden Tag. Im String verschachtelte Tags werden
     * im Resultat eingeschlossen, z.B. bei {{select '{{kunde.email}}'}} kann alles
     * zwischen {{ und }} zurückgegeben werden.
     * 
     * Wichtig:
     * =========
     * 
     * Es wird empfohlen, die strikte Variante getStringToCloseTag() zu verwenden.
     * Denn, wenn das schliessende Tag nicht gefunden wird, dann gibt die strikte Funktion
     * gar nichts zurück. Und getPartOfString_OpenToClose() gibt in diesem Fall den Text
     * bis zum Ende des Textes zurück, was zu unerwartetem Resultat führen könnte.
     *
     * @param  string    $p_sString    Der Eingabestring.
     * @param  string    $p_sOpenTag   Dieser Text wird gesucht. Text, der zwischen
     *                   	        dem Zeichen ab der durch p_sOpenTag gefunden Stelle,
     *                    	        und der durch p_sCloseTag gefundenen Stelle steht,
     *                    	        wird zurückgegeben. Wenn p_sOpenTag nicht angegeben ist,
     *                              wird Text ab dem ersten Zeichen zurückgegeben.
     * 
     * @param  string $p_sCloseTag  Wenn Angegeben: Gibt Text nur bis zum Auftreten
     *                    	        dieses Zeichens zurück oder bis zum Ende des Textes.
     *                              
     * @param  bool   $p_blnCaseSensitive Vergleichsmethode:
     *                                      - True = Case Sensitive
     *                                      - False = Case Insensitive (Standard)
     *                              
     * @return string    Der Text zwischen $p_sOpenTag und $p_sCloseTag
     * @author Urs Langmeier
     * 
     */
    function getPartOfString_OpenToClose($p_sString, $p_sOpenTag, $p_sCloseTag = "", $p_blnCaseSensitive = false) {
        $lPos = 0;
        $lPos2 = 0;
    
        if ($p_sString != "") {
            // Suche das Vorkommen von Find im String:
            if ($p_sOpenTag == "") {
                // Wenn der Begin-String nicht angegeben ist, dann
                // bitte vom ersten Zeichen weg beginnen zu suchen...
                $lPos = 0;
            } else {
                if ($p_blnCaseSensitive) {
                    // Case sensitive:
                    $lPos = strpos($p_sString, $p_sOpenTag);
                } else {
                    // Case insensitive:
                    $lPos = stripos($p_sString, $p_sOpenTag);
                }
            }
    
            if (!($lPos === false)) {
                if (strlen($p_sCloseTag) > 0) {
                    // Verschachtelte Tags berücksichtigen:
                    $openTagCount = 1;
                    $startPos = $lPos + strlen($p_sOpenTag);
    
                    while ($openTagCount > 0 && $startPos < strlen($p_sString)) {
                        if ($p_blnCaseSensitive) {
                            $nextOpenPos = strpos($p_sString, $p_sOpenTag, $startPos);
                            $nextClosePos = strpos($p_sString, $p_sCloseTag, $startPos);
                        } else {
                            $nextOpenPos = stripos($p_sString, $p_sOpenTag, $startPos);
                            $nextClosePos = stripos($p_sString, $p_sCloseTag, $startPos);
                        }
    
                        if ($nextClosePos === false) {
                            return substr($p_sString, $lPos + strlen($p_sOpenTag));
                        }
    
                        if ($nextOpenPos !== false && $nextOpenPos < $nextClosePos) {
                            $openTagCount++;
                            $startPos = $nextOpenPos + strlen($p_sOpenTag);
                        } else {
                            $openTagCount--;
                            $startPos = $nextClosePos + strlen($p_sCloseTag);
                        }
                    }
    
                    $lPos2 = $startPos - strlen($p_sCloseTag);
    
                    if ($openTagCount == 0) {
                        return substr($p_sString, $lPos + strlen($p_sOpenTag), $lPos2 - ($lPos + strlen($p_sOpenTag)));
                    } else {
                        return substr($p_sString, $lPos + strlen($p_sOpenTag));
                    }
                } else {
                    // Ein FindTo ist nicht angegeben - Rest-String zurückgeben:
                    return substr($p_sString, $lPos + strlen($p_sOpenTag));
                }
            } else {
                // Kein String gefunden! -> Leerstring zurückgeben...
                return "";
            }
        } else {
            // Kein String da! -> Leerstring zurückgeben...
            return "";
        }
    }

    /**
     * Formatiert einen Wert als Geldbetrag...
     *
     * @param  float $value       Der zu formatierende Wert
     * @param  mixed $mixed_currency_symbol_OR_round_precision
     * @return str 
     * @author Urs Langmeier
     */
    function money($value, $mixed_currency_symbol_OR_round_precision = null) {
        // Wird ein Währungssymbol übergeben?
        if ( is_null($mixed_currency_symbol_OR_round_precision) ) {
            $currency_symbol = "";
            $round_precision = 2;

        } elseif (is_numeric($mixed_currency_symbol_OR_round_precision)) {
            $currency_symbol = "";
            $round_precision = $mixed_currency_symbol_OR_round_precision;

        } else {
            $currency_symbol = $mixed_currency_symbol_OR_round_precision;
            $round_precision = 2;
        }

        if ( len($currency_symbol) == 1 ) {
            // € 36.50
            return $currency_symbol . " ". number_format($value, $round_precision, '.', ',');
        } elseif ( strlen($currency_symbol) > 1 ) {
            // 36.50 CHF
            return number_format($value, $round_precision, '.', ','). " " . $currency_symbol;
        } else {
            // 36.50
            return number_format($value, $round_precision, '.', ',');
        }
    }
