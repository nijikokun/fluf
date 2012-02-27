<?php
include '../lib/http.php';

http\get('/login', function() {
    if(http::$session->authd)
    	http::redirect('/');

    echo '<form method="post">';
    echo 'Password: <input type="password" />';
    echo '<input type="submit" value="Login!" />';
    echo '</form>';
});

http\post('/login', function() {
	if(http::$post->password != '1234')
		http::redirect('/login');

	http::$session->authd = true;
});

http\get('/', function () {
    if(!http::$session->authd)
    	echo 'Please <a href="login/">login</a>!';
    else
    	echo 'Hello there good sir!';
});