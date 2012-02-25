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

Ajax Support
-------
**http** can tell you whether or not a request came through as an ajax request like so:

``` php
echo http::ajax() ? 'true' : 'false';
```

API
------
http\get, 
http\put, 
http\post, 
http\delete

Changelog
-------
- **0.1**
-- Release

License
-------
Licensed under [AOL](http://aol.nexua.org/#!/http.php/Nijiko Yonskai/nijikokun@gmail.com/nijikokun)

Contributors
-------
- [Nijikokun](http://twitter.com/nijikokun)