![fluf logo](http://cl.ly/image/3D0l1e2p3W1j/flufphp.logo.png)

  A Fast, Minimalistic, Micro Routing / Web Application Framework for PHP5.

  **fluf** allows you to quickly start routing, simplify code, keep the namespace clean, and keeps you + your codebase sane.

``` php
<?
require_once 'lib/fluf.php';

fluf\get('/', function () {
  echo 'Hello World!';
});
```

## Features

  * Utilizes own namespace to keep application namespace clean.
  * Robust Routing system with Request management.
  * Simplifies Session, Cookie, and File coding.
  * HTTP Helpers; (Ajax, Redirection, etc...)
  * Small footprint, and minimalistic codebase.

## Usage

  **fluf** supports a broad range of input, everything from anonymous functions to class methods. 

### Anonymous Functions

  These are your basic inline functions, these allow you to create routes without any hassle.

``` php
<?
fluf\get('/', function () {
  echo 'Anonymous!';
});
```

#### Outside Variables

  Let's say you want to use a template manager and it's not global or a static. Easy.

``` php
<?
// We are using Twig from Symfony
fluf\get('/', function () use ($twig) {
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

fluf\get('/', 'hello_world');
```

### Class Functions

  Maybe you want a heirarchy type of control. Something with a little more zing, and readability. 
  **fluf** allows you to use class methods to keep that OOP fanboy alive.

``` php
<?
class hello {
  function world () {
    echo 'Hello World!';
  }
}

fluf\get('/', array('hello', 'world'));
```

## Passing Parameters

  **fluf** supports parameters, passing of url based data to your code, here is a basic example:

``` php
<?
fluf\get('/', function () {
  echo 'Hello World!';
});

fluf\get('/:who', function ($who) {
  // This is not being sanitized or validated so make sure
  // you do that before using in a live product!
  echo 'Viewing Profile of ' . $who;
});
```

  Essentially to create parameters you simply prepend a `:` to the parameter name in the route and pass it to the function.

  Note, the parameters are passed in order of appearance.

## Mapping

  **fluf** allows you to have tons of control over your requests, so maybe you need a custom one or multiple requests for a single function / class.

  Using `fluf\map` allows you to do just that:

``` php
<?
fluf\map('/', function () {
  // Post Request with the key 'post' sent will activate this.
  if (fluf::$post->post)
    echo 'Hello World! - by A POST Request.';
  else
    echo 'Hello World! - by A GET Request.';
})->via('GET', 'POST');
```

  You can also define route mappings that don't trigger until you want them to by omitting the `via` method:

``` php
<?
$logged_in = false; // change this to true and see what happens!
$index = fluf\map('/', function () {
  echo "Hello World!";
});

if ($logged_in) $index->via('GET');
```

## Redirection

  **fluf** supports local and public redirection, as well as optional exit support so further code isn't executed.

``` php
<?
fluf\get('/logout', function () {
  fluf::redirect('/');
});

fluf\get('/', function () {
  echo 'Welcome!';
});
```

## Requests & Sessions

  **fluf** can help you with requests, and sessions.

  Sessions, and Requests (GET, POST, REQUEST) are handled the same way:

``` php
<?
fluf\get('/', function () {
  // ?first appended to url will activate this.
  if (fluf::$get->first)
    fluf::$session->get = true;
    
  if (fluf::$session->get)
    echo 'Hello World! - by A GET Request.';
    
  if (fluf::$session->post)
    echo 'Hello World! - by A POST Request.';
  
  if (!fluf::$session->get || !fluf::$session->post)
    echo 'Hello World!';
});

fluf\post('/', function () {
  // first sent as a POST request will activate this.
  if (fluf::$post->first)
    fluf::$session->post = true;
});
```

  It not only accepts `GET` and `POST`, it accepts anything.

## Ajax Support

  **fluf** can tell you whether or not a request came through as an ajax request like so:

``` php
<?
echo fluf::ajax() ? 'true' : 'false';
```

  Want to return some data from that Ajax Callback? Easy. **fluf** supports json_encoding without any extra markup:

``` php
<?
fluf\get('/', function () {
  if (fluf::ajax())
    return array( 'Hello', 'World' ); // outputs: [ 'Hello', 'World' ]
});
```

API
------
`fluf\map`,
`fluf\get`, 
`fluf\put`, 
`fluf\post`, 
`fluf\delete`, 
`fluf::redirect`, 
`fluf::ajax`, 
`fluf::$session`,
`fluf::$cookie`,
`fluf::$env`, 
`fluf::$request`, 
`fluf::$get`, 
`fluf::$post`

Changelog
-------
- **1.0** 12/02/2012
 - Changed name from `http` to `fluf` for marketing / visibility reasons.
 - Implemented `fluf::$cookie`, `fluf::$files`, `fluf::$env` support.
 - Fixed issue with `fluf\map` not directing to `fluf\Map`.
 - Cleaned up codebase and simplified some areas that were robust for no reason.
 - Implemented `fluf::$autorun` for environments that wish to choose when to run `fluf::run()` after setting up routes.
- **0.6**
 - Use `$_GLOBALS[$v]` instead of `${$v}` for "Superglobals" which really aren't superglobals.
- **0.5**
 - Removed redundant code.
 - Utilize eval for better efficiency where no user input is ever utilized for security reasons.
 - Changed `$$v` to `${$v}` and `$$methods` to `${$methods}` to prevent issues.
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
Licensed under [AOL](http://aol.nexua.org/#!/fluf.php/Nijiko Yonskai/nijikokun@gmail.com/nijikokun)

Contributors
-------
- [Nijikokun](http://twitter.com/nijikokun)