<?php
	/* For Newbies: Do not touch this file, it is part of the Vanilla PHP framework. */

	######################################################################################################
    # Project: Vanilla PHP Framework
    # Author: Urs Langmeier, Langmeier Software
    # Role: Founder and Initial Developer
    #
    # Description:  This lightweight vanilla PHP framework combines the power of Bootstrap and
    #               HTML5 Vanilla Coding, simplifying development by eliminating the need to manage
    #               external JavaScript libraries, CSS files, and web-browser specific hassles like
    #               fonts and meta tags.
    #
    #               It offers a quick and efficient start for PHP professionals launching new projects,
    #               while providing an easy, accessible entry point for beginners eager to build their
    #               own SaaS platforms. Designed for AI-driven app development, the framework helps
    #               streamline the process of creating intelligent, modern applications.
    #
    # Initial development started on October 12th, 2024.
    #
    # License: Boost Software License - Version 1.0 - August 17th, 2003
    #          https://www.boost.org/users/license.html
    #
    # Copyright (c) 2025, Urs Langmeier
    #
    ######################################################################################################

	// Define the Vanilla-Version:
	define("VANILLA_VERSION", "1.0.0");

	// Define Debug Mode: 0 = Off, 1 = On
	define("VANILLA_DEBUG_MODE", 0);

	// Include .css and .js libraries with path? (improves compatibility with some servers)
	define("VANILLA_INCLUDE_LIBRARIES_WITH_PATH", true);

	// Define the default intentation for the HTML output:
	define("VANILLA_HTML_INDENT", "    ");
	
	

	require_once('lib/mainfunctions.php');
	require_once('lib/errors.php');
	installWellErrorHandler(E_ALL, E_ALL);

	$blnBeginBusinessCalled = false;
	$blnVanillaBusinessStarted = false;

	// Adding some checks to ensure that the BeginBusiness() function is called
	register_shutdown_function('vanillaDocumentEndChecks');

	// Define the document root
	define("__DOCUMENT_ROOT__", $_SERVER['DOCUMENT_ROOT']);

	// Initialize variables
	$vanillaLateScriptsOutputBuffer = "";
	
	function BeginBusiness($name = "", $title = "", $description = "", $libraries = "", $refresh = false) {
		global $blnBeginBusinessCalled;

		if ($blnBeginBusinessCalled) {
			// BeginBusiness() already called
			// -> Error
			trigger_error("Error: BeginBusiness() called twice!", E_USER_ERROR);
			return;
		}

		$blnBeginBusinessCalled = true;

		define("__APPNAME__", $name);
		define("__APPTITLE__", ($title ? $title : $name));
		define("__APPDESCRIPTION__", $description);
		
		// Module, die vor dem Aufruf von BeginBusiness mit der ->libraries()-Funktion hinzugefügt
		// wurden, werden hier geladen:
        global $globalStrLibrariesPreannounced;
        if (!empty($globalStrLibrariesPreannounced)) {
			// Es wurde bereits vorher eine ->libraries()-Funktion aufgerufen
			// -> Diese Libraries wollen wir nun ebenfalls in den Header laden
			//    und zwar an erster Position...
			if ( $libraries != "" ) {
				$libraries = $globalStrLibrariesPreannounced.",".$libraries;
			} else {
				$libraries = $globalStrLibrariesPreannounced;
			}
        }

		// Libraries als Code umwandeln und Header rendern:
		$librariesCode = libraries_Ex($libraries, false, false);
		define("__LIBRARIES__", $librariesCode);
		include("etc/head.php");

		// Festhalten, dass das Business begonnen hat:
		global $blnVanillaBusinessStarted;
		$blnVanillaBusinessStarted = true;

		// Footer an das Ende der Seite setzen:
		register_shutdown_function("vanillaRenderFooter");
	
	}

	function vanillaRenderFooter() {
		include("etc/foot.php");
	}

	function vanillaDocumentEndChecks() {
		global $blnBeginBusinessCalled;
		if (!$blnBeginBusinessCalled) {
			ob_clean();
			echo("Error: No BeginBusiness() called!                                                           <br><br>\n\n");
			echo("⇨ Please start your code with the BeginBusiness() function!                                <br><br>\n\n");

			echo "Example:                                                                                    <br>\n";
			echo "========                                                                                    <br><br>\n\n";
			echo "require_once(\"main.php\");                                                                   <br>\n";
			echo "BeginBusiness(\"MyApp\");                                                                     <br>\n";
			echo "...                                                                                         <br><br>\n\n";

			echo "Why?                                                                                        <br>\n";
			echo "====                                                                                        <br><br>\n\n";
			echo "● BeginBusiness() will render the head and start the body (/etc/head.php)                   <br>\n";
			echo "● At the end of the script, the footer will be rendered (/etc/foot.php)                     <br>\n";
			echo "● Your css and javascript libraries, configured in /lib/libraries.json, will be loaded.     <br>\n";
			echo "● If you forget to call BeginBusiness(), the script will die here.";

			die();
		}
	}

	/**
	 * Removes the extension from a file path.
	 *
	 * @param  string $path
	 * @return string The path without the file extension.
	 * @author Urs Langmeier
	 */
	function vn_File_RemoveExtension($path) {
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		if ($ext) {
			$path = substr($path, 0, -strlen($ext)-1);
		}
		return $path;
	}

	/**
	 * Returns the extension of a file path.
	 *
	 * @param  string $path
	 * @return string The file extension.
	 * @author Urs Langmeier
	 */
	function vn_File_GetExtension($path) {
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		return $ext;
	}
	
	/**
	 * Refreshs the body of the site automatically after a given amount of seconds.
	 *
	 * @param  int $seconds
	 * @return string 
	 * @author Urs Langmeier
	 */
	function vn_Site_AutoRefresh($seconds = 1) {
		global $vanillaLateScriptsOutputBuffer;
		if ($seconds > 0) {
			$vanillaLateScriptsOutputBuffer .= VANILLA_HTML_INDENT . '<script>vn_Site_AutoRefresh('.$seconds.')</script>';
		}
	}