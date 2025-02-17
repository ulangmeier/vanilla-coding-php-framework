<?php
	// This is a simple example of how to use Vanilla PHP
	require_once('vanilla.php');

	// Let's load some libraries, specified in /lib/libraries.json:
	libraries("chartjs,animate,jquery");

	// Let's begin our business
	BeginBusiness(
		name: "MyApp",
		title: "My Application",
		description: "A brief, catchy phrase that encapsulates the essence of your application"		
	);

	// We are ready!
	// Just place your code here...

?>

	<div class="container text-center mt-5 animate__animated animate__pulse">
		<h1 class="display-4">Hello, World!</h1>
		<p class="lead">This is a simple Bootstrap example.</p>
		<a class="btn btn-primary" href="chart-example.php">Go to Chart Example</a>
	</div>

