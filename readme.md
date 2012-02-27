     __     __    __          
    |  |--.|  |_ |  |_ .-----.
    |     ||   _||   _||  _  |
    |__|__||____||____||   __|
                       |__|   
-----
A Super-Micro Routing Framework for PHP. **http** allows you to quickly start routing, 
keeps the namespace clean, and keeps your codebase sane. Best of all, it's less than 130 lines
of code.

How it looks
------
``` php
<?
require_once 'lib/http.php';

http\get('/', function () {
    echo 'Hello World!';
});
?>
```

Parameters
------
**http** supports parameters, here is a basic example:

``` php
<?
require_once 'lib/http.php';

http\get('/', function () {
    echo 'Hello World!';
});

http\get('/profile', function () {
    header('Location: /');
});

http\get('/profile/:who', function ($who) {
    echo 'Viewing Profile of ' . $who;
});
?>
```

Request & Sessions
-------
**http** can help you with requests, and sessions.

Sessions, and Requests (GET, POST, REQUEST) are handled the same way:

``` php
<?php
require_once 'lib/http.php';

http\get('/', function () {
    // ?first appended to url will activate this.
    if(http::$get->first)
        http::$session->get = true;
        
    // first sent as a POST request will activate this.
    if(http::$post->first)
        http::$session->post = true;
        
    if(http::$session->get)
        echo 'Hello World! - by A GET Request.';
        
    if(http::$session->post)
        echo 'Hello World! - by A POST Request.';
    
    if(!http::$session->get || !http::$session->post)
        echo 'Hello World!';
});
```

Ajax Support
-------
**http** can tell you whether or not a request came through as an ajax request like so:

``` php
echo http::ajax() ? 'true' : 'false';
```

API
------
`http\get`, 
`http\put`, 
`http\post`, 
`http\delete`, 
`http::redirect`, 
`http::ajax`, 
`http::$session`, 
`http::$request`, 
`http::$get`, 
`http::$post`

Changelog
-------
- **0.2** 
 - Added Support for Redirection `http::redirect()`
 - Added, Request / Get / Post, Objects `http::$request` `http::$get` `http::$post`
 - Fixed Url Params preventing routing.
- **0.1**
 - Release

License
-------
Licensed under [AOL](http://aol.nexua.org/#!/http.php/Nijiko Yonskai/nijikokun@gmail.com/nijikokun)

Contributors
-------
- [Nijikokun](http://twitter.com/nijikokun)