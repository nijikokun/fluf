     __     __    __          
    |  |--.|  |_ |  |_ .-----.
    |     ||   _||   _||  [] |
    |__|__||____||____||   __|
                       |__|
-----
A Super-Micro Routing Framework for PHP. **http** allows you to quickly start routing, 
keeps the namespace clean, and keeps your codebase sane. Best of all, it's less than 130 lines
of code!

## How it looks
``` php
<?
require_once 'lib/http.php';

http\get('/', function () {
    echo 'Hello World!';
});
```

All examples beyond this point assume you have included the http file.

## Usage
**http** supports a broad range of input, everything from anonymous functions to class methods. 

### Anonymous Functions
These are your basic inline functions, these allow you to create routes without any hassle.

``` php
<?
http\get('/', function () {
    echo 'Anonymous!';
});
```

#### Outside Variables
Let's say you want to use a template manager and it's not global or a static. Easy.

``` php
<?
// We are using Twig from Symfony
http\get('/', function () use ($twig) {
    $template = $twig->loadTemplate('index.html');
    echo $template->render(array());
});
```

### Functions
Maybe you want to have a little more control of the whitespace in your files and routes. 
Using outside functions allow you to manage that a little better.

``` php
<?
function hello_world () {
    echo 'Hello World!';
}

http\get('/', 'hello_world');
```

### Class Functions
Maybe you want a hierarchy type of control. Something with a little more zing, and readability. 
**http** allows you to use class methods to keep that OOP fanboy alive.

``` php
<?
class hello {
    function world () {
        echo 'Hello World!';
    }
}

http\get('/', array('hello', 'world'));
```

## Passing Parameters
**http** supports parameters, passing of url based data to your code, here is a basic example:

``` php
<?
http\get('/', function () {
    echo 'Hello World!';
});

http\get('/:who', function ($who) {
    // This is not being sanitized or validated so make sure
    // you do that before using in a live product!
    echo 'Viewing Profile of ' . $who;
});
```

Essentially to create parameters you simply prepend a `:` to the parameter name in the route and pass it to the function.
Note, the parameters are passed in order of appearance.

## Mapping
**http** allows you to have tons of control over your requests, 
so maybe you need a custom one or multiple requests for a single function / class.

Using `http\map` allows you to do just that:

``` php
<?
http\map('/', function () {
    // Post Request with the key 'post' sent will activate this.
    if(http::$post->post)
        echo 'Hello World! - by A POST Request.';
    else
        echo 'Hello World! - by A GET Request.';
})->via('GET','POST');
```

You can also define route mappings that don't trigger until you want them to by omitting the `via` method:

``` php
<?
$logged_in = false; // change this to true and see what happens!
$index = http\map('/', function () {
    echo "Hello World!";
});

if($logged_in) $index->via('GET');
```

## Redirection
**http** supports local and public redirection, as well as optional exit support so further code isn't executed.

``` php
<?
http\get('/logout', function () {
    http::redirect('/');
});

http\get('/', function () {
    echo 'Welcome!';
});
```

## Requests & Sessions
**http** can help you with requests, and sessions.

Sessions, and Requests (GET, POST, REQUEST) are handled the same way:

``` php
<?
http\get('/', function () {
    // ?first appended to url will activate this.
    if(http::$get->first)
        http::$session->get = true;
        
    if(http::$session->get)
        echo 'Hello World! - by A GET Request.';
        
    if(http::$session->post)
        echo 'Hello World! - by A POST Request.';
    
    if(!http::$session->get || !http::$session->post)
        echo 'Hello World!';
});

http\post('/', function () {
    // first sent as a POST request will activate this.
    if(http::$post->first)
        http::$session->post = true;
});
```

It not only accepts `GET` and `POST`, it accepts anything.

## Ajax Support
**http** can tell you whether or not a request came through as an ajax request like so:

``` php
<?
echo http::ajax() ? 'true' : 'false';
```

Want to return some data from that Ajax Callback? Easy. 
**http** supports json_encoding without any extra markup:

``` php
<?
http\get('/', function () {
    if(http::ajax())
        return array( 'Hello', 'World' ); // outputs: [ 'Hello', 'World' ]
});
```

API
------
`http\map`,
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
- **0.4**
 - Added More advanced routing control through `http\map`
 - Reduced load on requests.
- **0.3**
 - Added Optional ability to exit on `http::redirect()`
- **0.2** 
 - Added Support for Easy JSON output.
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