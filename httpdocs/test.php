<?php
	require_once('vanilla.php');
	BeginBusiness(	"MyApp"
					,"My Application"
					,"You can do this and that with my app!"

                    /* Let's load some libraries, specified in /lib/libraries.json: */
                    ,"chartjs,animate"
                );

    // Let's produce an error, just to demonstrate how it will looks like:
    // Coming soon (in upcoming release): adding well-formed error handling...
    trigger_error("Whew!! That just blew my mind, for real! We have an error that we just produced", E_USER_ERROR);

?>

<div class="container text-center mt-5">
    <h1 class="display-4">Hello, World!</h1>
    <p class="lead">This is a simple Bootstrap example.</p>
    <button class="btn btn-primary">Click Me</button>
</div>