<?php
include '../lib/http.php';

// Our super-duper-secret password.
$password = '1234';

http\get('/login', function() {
    if(http::$session->logged_in)
    	http::redirect('/');

    echo '<form method="post">';
    echo 'Password: <input type="password" />';
    echo '<input type="submit" value="Login!" />';
    echo '</form>';
});

// Post request for login page, upon form submission
// the user will land here.
http\post('/login', function() use ($password) {
	// Check password against user input.
	if(http::$post->password != $password)
		http::redirect('/login');

	// We made it through the check, user is now authenticated.
	http::$session->logged_in = true;
	http::redirect('/');
});

// Index Request.
http\get('/', function () {
    if(!http::$session->logged_in)
    	echo 'Please <a href="login/">login</a> to see a great message!';
    else
    	echo 'Hello there good sir, glad to see you logged in!';
});