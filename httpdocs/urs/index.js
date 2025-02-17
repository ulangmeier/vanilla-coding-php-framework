/* Place javascript code that is loaded along with 
 * the PHP file that has the same name as this file.
 *
 * For example:
 * - peter.php loads peter.js automatically, if peter.js exists)
 * 
 * Hint: Same is for css-files the case.
 *
 **/

// Refresh the body of the site automatically
setInterval(function(){
    // Refresh the body of the site
    fetch('index.php')
        .then(response => response.text())
        .then(data => {
            // Getting the boody of data
            var body = new DOMParser().parseFromString(data, 'text/html').body;
            document.body.innerHTML = body.innerHTML;
        });
}, 1000);		
