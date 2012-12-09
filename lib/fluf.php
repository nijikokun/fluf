<?php
/**
 * (fluf) php
 *
 * A Micro-Routing / Web Application Framework for PHP(5) - Without the excess "fluf"
 *
 * @version 1.1
 * @author Nijiko Yonskai <http://nexua.org>
 * @package fluf
 * @license AOL <http://aol.nexua.org/#!/fluf.php/Nijiko+Yonskai/nijikokun@gmail.com/nijikokun>
 */

class fluf {
  /**
   * Server based properties
   * @ignore
   */
  static $server, $env;

  /**
   * Request based properties
   * @ignore
   */
  static $request, $get, $post;

  /**
   * Storage based properties
   * @ignore
   */
  static $session, $cookie, $files;

  /**
   * Page based properties
   * @ignore
   */
  static $uri, $method, $script;

  /**
   * Key-value array store for information.
   * @var array
   */
  protected static $settings = array();

  /**
   * Route array store, holds routes and flufRoute reference.
   * @var array
   */
  private static $routes = array(), $included = array();

  /**
   * Creates session, delegates out globals to their respective properties, 
   * cleans the current URI, and sets script name / request method.
   */
  static function setup () {
    self::$session = new flufSession('fluf_session');

    foreach (array('request','post','get','server','env','files') as $method)
      self::${$method} = new flufArray($GLOBALS['_' . strtoupper($method)]);

    self::$uri = preg_replace('/\?.+/', '', self::$server->REQUEST_URI);
    self::$method = self::$server->REQUEST_METHOD;
    self::$script = self::$server->SCRIPT_NAME;
    self::set('base', dirname(__FILE__));
    self::set('autorun', true);
    self::sanitize();
  }

  /**
   * Remove script name from fluf#$uri
   */
  private static function sanitize () {
    $uri = explode('/', self::$uri); 
    $name = explode('/', self::$script);

    for ($i = 0; $i < count($name); $i++) 
      if ($uri[$i] == $name[$i]) 
        unset($uri[$i]);

    self::$uri = '/' . implode('/', $uri);
  }

  /**
   * Load dependancy script
   * @param  Mixed $dep List of dependancies, or single dependancy.
   */
  static function load ($dep) {
    if (!is_array($dep)) {
      if(!isset(self::$included[$dep]))
        include(self::get('base') . "/fluf.{$dep}.php");
      self::$included[$dep] = 1;
    } else {
      foreach($dep as $d) self::load($d);
    }
  }

  /**
   * Take route method `return` result and utilize it as a response. Any set headers will be 
   * set and sent along with the response, and then cleared as per `fluf::response()` method.
   * 
   * Allows for easy JSON / JSONP response depending on settings, and cors support as well.
   * 
   * @param  Mixed  $result JSON/JSONP Response will be array based, all others are string based.
   */
  static function handleResult ($result) {
    function is_valid_callback($subject) {
      $identifier_syntax = '/^[$_\p{L}][$_\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Pc}\x{200C}\x{200D}]*+$/u';
      $reserved_words = array('break', 'do', 'instanceof', 'typeof', 'case', 'else', 'new', 'var', 'catch', 'finally', 
        'return', 'void', 'continue', 'for', 'switch', 'while', 'debugger', 'function', 'this', 'with', 'default', 'if', 
        'throw', 'delete', 'in', 'try', 'class', 'enum', 'extends', 'super', 'const', 'export', 'import', 'implements', 
        'let', 'private', 'public', 'yield', 'interface', 'package', 'protected', 'static', 'null', 'true', 'false');
      return preg_match($identifier_syntax, $subject) && ! in_array(mb_strtolower($subject, 'UTF-8'), $reserved_words);
    }

    if (is_array($result)) { 
      $response = json_encode($result);
      self::set('header', 'Content-type', 'application/json; charset=utf-8');
      if (self::get('cors')) self::set('header', 'access-control-allow-origin', '*');
      if (self::get('jsonp') && self::$get->callback)
        if (is_valid_callback(self::$get->callback))
          $response = self::$get->callback . "($response)";
    } else $response = $result;

    if (isset($response)) self::response($response);
  }

  /**
   * Checks `headers_sent()` if headers have been sent, if not takes and sets any `header` 
   * keys that have been set using `fluf::set()`. Afterwards, removes and unsets all `header` 
   * keys.
   */
  static function headers () {
    if (headers_sent()) return;
    $headers = self::get('header');
    if (is_array($headers) && count($headers) > 0)
      foreach ($headers as $k => $v) header($k . ': ' . $v);
    self::set('header', null);
  }

  /**
   * Returns given response to the client, and sets any headers via `fluf::headers()`.
   *  
   * @param  Mixed  $response Response to send to client
   */
  static function response ($response) {
    self::headers();
    echo $response;
  }

  static function add ($rule, $method, $middleware = null, $callback = null, $cond = null) {
    if (!empty($middleware)) {
      if (empty($callback))
        if (is_callable($middleware)) {
          $callback = $middleware; $middleware = null;  
        }
      else if (!is_callable($callback)) {
        $cond = $callback; $callback = $middleware; $middleware = null;
      }
    }

    self::$routes[] = new flufRoute(self::$uri, $rule, $method, $middleware, $callback, $cond);
    if (self::get('autorun')) self::run();
  }

  /**
   * Retrieve key-value information from `fluf::$settings[$key]`
   * 
   *     fluf::get('key');
   *   
   * @param  String $key  Array key
   * @param  String $hkey Header-key (optional)
   * @return Mixed  Value of `fluf::$settings[$key]` or `fluf::$settings['header'][$hkey]`
   */
  static function get ($key, $hkey = null) {
    if (isset($hkey)) return isset(self::$settings[$key][$hkey]) ? self::$settings[$key][$hkey] : false;
    return isset(self::$settings[$key]) ? self::$settings[$key] : false;
  }

  /**
   * Set `fluf::$settings` value in our associative key-value store.
   * 
   *     fluf::set(key, value);
   *
   * Also, allows header settings to be created when `$key` is `header`
   *
   *     fluf::set('header', key, value);
   * 
   * @param String $key    Array key
   * @param Mixed  $value  Key value
   * @param Mixed  $hvalue Header-key value (optional)
   */
  static function set ($key, $value, $hvalue = false) {
    if ($key === 'header' && $hvalue !== false)
      if (!is_array(self::$settings[$key])) return self::$settings[$key] = array($value => $hvalue);
      else return self::$settings[$key][$value] = $hvalue;
    self::$settings[$key] = $value;
  }

  static function ajax ($x = 'HTTP_X_REQUESTED_WITH') {
    return isset($_SERVER[$x]) &&  $_SERVER[$x] === 'XMLHttpRequest';
  }

  static function redirect($path, $code = null, $exit = true) {
    $uri = "/" . str_replace($_SERVER['DOCUMENT_ROOT'], '', dirname($_SERVER['SCRIPT_FILENAME']));
    if($code != null && (int)$code == $code) header("Status: {$code}");
    header('Location: ' . ((preg_match('%^http\:\/\/|https\:\/\/%', $path) > 0) ? $path : $uri . $path)); 
    if($exit) exit;
  }

  static function run () {
    foreach (self::$routes as $i => $r) {
      if ($r->match && !$r->ran) {
        if (!empty($r->middleware) && count($r->middleware) > 0) {
          $next = function ($error = null) use (&$r, &$next) { $r->mwarecount++;
            if ($error = 'route' || $r->mwarecount >= count($r->middleware)) { 
              $r->mwarecount = 0; fluf::handleResult(call_user_func_array($r->callback, $r->params), $r->params);
            } else if (!empty($error) && is_string($error)) flufException::raise(new flufMiddlewareException($error));
            else call_user_func_array($r->middleware[$r->mwarecount], array(&$r->params, $next));
          };
          call_user_func_array($r->middleware[$r->mwarecount], array(&$r->params, $next));
        } else 
          fluf::handleResult(call_user_func_array($r->callback, $r->params), $r->params);
        $r->ran = true;
      } else 
        unset(self::$routes[$i]);
    }
  }
}

class flufArray {
  protected $a;
  public function __construct (&$a) { 
    $this->a = $a; 
  }

  public function __invoke ($k, $v) { 
    if (!isset($v)) return isset($this->a[$k]) ? $this->a[$k] : null; 
    else $this->a[$k] = $v; 
    return $v; 
  }

  public function __get ($k) { 
    return isset($this->a[$k]) ? $this->a[$k] : null; 
  }

  public function __set ($k, $v) { 
    $this->a[$k] = $v; return $v;
  }
}

class flufSession extends flufArray {
  public function __construct ($name) { 
    session_name($name); session_start(); 
    parent::__construct($GLOBALS['_SESSION']);
  }

  public function __unset ($k) { 
    if (isset($this->a[$k])) unset($this->a[$k]); 
  }

  public function destroy ($unset = false) { 
    if ($unset) session_unset(); 
    return session_destroy(); 
  }
}

class flufCookie extends flufArray {
  public function __construct () { 
    parent::__construct($GLOBALS['_COOKIE']); 
  }

  public function __invoke ($k, $v, $expires = '+30 Days', $path = null, $domain = null, $secure = false, $httponly = false) { 
    if (!isset($v)) isset($this->a[$k]) ? $this->a[$k] : null; 
    else return $this->set($k, $v, $expires, $path, $domain, $secure, $httponly); 
  }

  public function __set ($k, $v) { 
    return $this->set($k, $v); 
  }

  public function __unset ($k) { 
    if (isset($this->a[$k])) unset($this->a[$k]); 
    return setcookie($k, null, -1); 
  }

  public function set ($k, $v, $expires = '+30 Days', $path = null, $domain = null, $secure = false, $httponly = false) { 
    return setcookie($k, $v, is_string($expires) ? strtotime($expires) : $expires, $path, $domain, $secure, $httponly); 
  }
}

class flufRoute {
  public $url, $callback, $middleware = array(), $mwarecount = 0, $match = false, $ran = false, $params = array();
  private $conditions, $uri;

  function __construct ($uri, $url, $method, $middleware = null, $callback = null, $cond = null) {
    if (empty($uri) || empty($url) || empty($method) || empty($callback)) return;
    if (is_callable($callback)) $this->callback = $callback;
    if (!empty($middleware)) if (is_string($middleware) && is_callable($middleware)) $this->middleware[] = $middleware;
    else if (is_array($middleware)) foreach ($middleware as $key => $value) if (is_callable($value)) $this->middleware[] = $value;
    $this->method = is_array($method) ? $method : array( $method );
    $this->conditions = is_array($cond) ? $cond : array(); $this->url = $url; $this->uri = $uri;
    $this->compile();
  }

  function compile () {
    $names = $values = array(); 
    preg_match_all('@:([\w]+)@', $this->url, $names, PREG_PATTERN_ORDER); 
    $names = $names[0];
    $regex = (preg_replace_callback('@:[\w]+@', array($this, 'regex'), $this->url)) . '/?';
    
    if (preg_match('@^' . $regex . '$@', $this->uri, $values) && in_array(fluf::$method, $this->method) && array_shift($values)) {
      foreach ($names as $i => $v) 
        $this->params[substr($v,1)] = urldecode($values[$i]);
      $this->match = true;
    }
  }

  function regex ($matches) {
    $key = str_replace(':', '', $matches[0]);
    return array_key_exists($key, $this->conditions) ?  '('.$this->conditions[$key].')' : '([a-zA-Z0-9_\+\-%]+)';
  }
}

class flufMap {
  function __construct ($rule, $middleware, $callback, $cond = array()) { 
    $this->r = $rule;
    $this->mw = $middleware; 
    $this->cb = $callback; 
    $this->cd = $cond; 
    return $this; 
  }
  
  public function via () { 
    if (func_num_args() !== 0) 
      foreach (func_get_args() as $a) 
        fluf::add($this->r, strtoupper($a), $this->mw, $this->cb, $this->cd); 
  }
}

class flufException extends Exception {
  public static function raise ($exception) {
    $useExceptions = fluf::get('exceptions');
    if($useExceptions) throw new $exception($exception->getMessage(), $exception->getCode());
  }
}

class flufTypeException extends flufException {}
class flufSessionException extends flufException {}
class flufMiddlewareException extends flufException {}

// Time to startup fluf
fluf::setup();

// Create Request function
function request ($rule, $middleware = null, $callback = null, $cond = null) { return new flufMap($rule, $middleware, $callback, is_array($cond) ? $cond : array()); }

// Create Request Functions
foreach (array('get','post','put','delete','patch','head','options') as $method) 
  eval('function '.$method.' ($rule, $middleware = null, $callback = null, $cond = null) { fluf::add($rule, "'.strtoupper($method).'", $middleware, $callback, $cond); }');