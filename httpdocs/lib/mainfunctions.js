
function httpsPost(url, postData, debug = false)
{
    fetch (url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams(postData)
    })
    .then(response => {
        if (response.status === 404) {
            console.log('Error 404: Resource not found: ' + url);
            throw new Error('Resource not found: ' + url);
        }
        return response.text();
    })
    .then(data => {
        if (debug) {
            console.log('HTTPSPost Success.\nResult: ', data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function httpsPost_JSON(url, postData, debug = false)
{
    fetch (url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(postData)
    })
    .then(response => {
        if (response.status === 404) {
            console.log('Error 404: Resource not found: ' + url);
            throw new Error('Resource not found: ' + url);
        }
        return response.text();
    })
    .then(data => {
        if (debug) {
            console.log('HTTPSPost Success.\nResult: ', data);
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

