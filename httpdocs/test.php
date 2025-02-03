<?php
	require_once('vanilla.php');

    // Let's begin our business
	BeginBusiness(	"MyApp"
					,"My Application"
					,"You can do this and that with my app!"

                    /* Let's load some libraries, specified in /lib/libraries.json: */
                    ,"chartjs,animate"
                );

    // Let's produce an error, just to demonstrate how it will looks like:
    // Coming soon (in upcoming release): adding well-formatted error handling...
    trigger_error("Whew!! That just blew my mind, for real! We have an error that we just produced. Just close that thing if you want to proceed.", E_USER_WARNING);

?>

<div class="container text-center mt-5">
    <h1 class="display-4">Hello, World!</h1>
    <p class="lead">This is a simple Bootstrap example.</p>
    <button class="btn btn-primary">Click Me</button>
</div>