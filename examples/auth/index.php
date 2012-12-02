<?php
include '../lib/fluf.php';

// Our super-duper-secret password.
$password = '1234';

fluf\get('/login', function() {
    if(fluf::$session->logged_in)
    	fluf::redirect('/');

    echo '<form method="post">';
    echo 'Password: <input type="password" />';
    echo '<input type="submit" value="Login!" />';
    echo '</form>';
});

// Post request for login page, upon form submission
// the user will land here.
fluf\post('/login', function() use ($password) {
	// Check password against user input.
	if(fluf::$post->password != $password)
		fluf::redirect('/login');

	// We made it through the check, user is now authenticated.
	fluf::$session->logged_in = true;
	fluf::redirect('/');
});

// Index Request.
fluf\get('/', function () {
    if(!fluf::$session->logged_in)
    	echo 'Please <a href="login/">login</a> to see a great message!';
    else
    	echo 'Hello there good sir, glad to see you logged in!';
});