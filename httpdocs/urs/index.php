<?php
	// This is a simple example of how to use Vanilla PHP
	require_once('../vanilla.php');

	// Let's load some libraries, specified in /lib/libraries.json:
	libraries("chartjs,animate");

	// Let's begin our business
	BeginBusiness(	"MyApp"
					,"My Application"
					,"You can do this and that with my app!");				

	// We are ready!
	// Just place your code here...

?>

	<div class="container text-center mt-5 animate__animated animate__pulse">
		<h1 class="display-4">Hello, World!</h1>
		<p class="lead">This is a simple Bootstrap example.</p>
		<button class="btn btn-primary">Click Me</button>
	</div>
