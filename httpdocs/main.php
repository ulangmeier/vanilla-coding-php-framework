<?php
	require_once('lib/mainfunctions.php');
	
	function BeginBusiness($appname, $title, $description) {
		define("__APPNAME__", $appname);
		define("__APPTITLE__", $title);
		define("__APPDESCRIPTION__", $description);
		include("etc/head.php");

		// Footer an das Ende der Seite setzen:
		register_shutdown_function('renderFooter');
		
	}

	function renderFooter() {
		include("etc/footer.php");
	}
	
?>