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
    @trigger_error("Whew!! That just blew my mind, for real! We produced an error just for fun. Just close that thing with the 'X' if you want to proceed.", E_USER_WARNING);
                vn_Site_AutoRefresh(1);
?>

<div class="container text-center mt-5">
    <h1 class="display-4">Well done!</h1>
    <p class="lead">You have closed the error message!</p>
    <p class="display-7">But ... here is one last thing!</p>
    <a href="api-example.php" class="btn btn-primary">Go to API example</a>
</div>