<?php
/**
 * (fluf) php
 *
 * A Micro-Routing / Web Application Framework for PHP(5) - Without the excess "fluf"
 *
 * @version 1.0.4
 * @author Nijiko Yonskai <http://nexua.org>
 * @package fluf
 * @license AOL <http://aol.nexua.org/#!/fluf.php/Nijiko+Yonskai/nijikokun@gmail.com/nijikokun>
 */

namespace {
  class fluf {
    static $server, $env, $files, $request, $get, $post, $session, $cookie, $uri, $method, $script, $autorun = true;
    private static $routes = array();

    static function setup () {
      self::$session = new \fluf\Session('fluf_session');
      foreach (array('request','post','get','server','env','files') as $method)
        self::${$method} = new \fluf\Arrays($GLOBALS['_'.strtoupper($method)]);
      self::$uri = preg_replace('/\?.+/', '', self::$server->REQUEST_URI);
      self::$method = self::$server->REQUEST_METHOD;
      self::$script = self::$server->SCRIPT_NAME;
      self::sanitize();
    }

    private static function sanitize () {
      $uri = explode('/', self::$uri); $name = explode('/', self::$script);
      for ($i = 0; $i < count($name); $i++) if ($uri[$i] == $name[$i]) unset($uri[$i]);
      self::$uri = '/' . implode('/', $uri);
    }

    static function handleResult ($result) {
      if (is_array($result)) { header('Content-type: application/json; charset=utf-8'); echo json_encode($result); }
    }

    static function add ($rule, $method, $middleware = null, $callback = null, $cond = null) {
      if (!empty($middleware)) { if (empty($callback)) { if (is_callable($middleware)) $callback = $middleware; $middleware = null;  } else if (!is_callable($callback)) { $cond = $callback; $callback = $middleware; $middleware = null; } }
      self::$routes[] = new \fluf\Route(self::$uri, $rule, $method, $middleware, $callback, $cond);
      if (self::$autorun) self::run();
    }

    static function ajax ($x = 'HTTP_X_REQUESTED_WITH') {
      return isset($_SERVER[$x]) &&  $_SERVER[$x] === 'XMLHttpRequest';
    }

    static function redirect($path, $exit = true) {
      $uri = "/" . str_replace($_SERVER['DOCUMENT_ROOT'], '', dirname($_SERVER['SCRIPT_FILENAME']));
      header('Location: ' . ((preg_match('%^http\:\/\/|https\:\/\/%', $path) > 0) ? $path : $uri . $path)); if($exit) exit;
    }

    static function run () {
      foreach (self::$routes as $i => $r) {
        if ($r->match && !$r->ran) {
          if (!empty($r->middleware) && count($r->middleware) > 0) {
            $next = function ($error = null) use (&$r, &$next) { $r->mwarecount++;
              if ($error = 'route' || $r->mwarecount >= count($r->middleware)) fluf::handleResult(call_user_func_array($r->callback, $r->params));
              else if (!empty($error) && is_string($error)) throw new Exception($error);
              else call_user_func_array($r->middleware[$r->mwarecount], array(&$r->params, $next));
            };
            call_user_func_array($r->middleware[$r->mwarecount], array($r->params, $next));
          } else fluf::handleResult(call_user_func_array($r->callback, $r->params));
          $r->ran = true;
        } else unset(self::$routes[$i]);
      }
    }
  }

  fluf::setup();
}

namespace fluf {
  class Arrays {
    protected $a;
    public function __construct (&$a) { $this->a = $a; }
    public function __invoke ($k, $v) { if(!isset($v)) return isset($this->a[$k]) ? $this->a[$k] : null; $this->a[$k] = $v; return $v; }
    public function __get ($k) { return isset($this->a[$k]) ? $this->a[$k] : null; }
    public function __set ($k, $v) { $this->a[$k] = $v; return $v; }
  }

  class Session extends Arrays {
    public function __construct ($name) { session_name($name); session_start(); parent::__construct($GLOBALS['_SESSION']); }
    public function __unset ($k) { if (isset($this->a[$k])) unset($this->a[$k]); }
    public function destroy ($unset = false) { if($unset) session_unset(); return session_destroy(); }
  }

  class Cookie extends Arrays {
    public function __construct () { parent::__construct($GLOBALS['_COOKIE']); }
    public function __invoke ($k, $v, $expires = '+30 Days', $path = null, $domain = null, $secure = false, $httponly = false) { if (!isset($v)) (isset($this->a[$k]) ? $this->a[$k] : null); else return $this->set($k, $v, $expires, $path, $domain, $secure, $httponly); }
    public function __set ($k, $v) { return $this->set($k, $v); }
    public function __unset ($k) { if (isset($this->a[$k])) unset($this->a[$k]); return setcookie($k, null, -1); }
    public function set ($k, $v, $expires = '+30 Days', $path = null, $domain = null, $secure = false, $httponly = false) { return setcookie($k, $v, is_string($expires) ? strtotime($expires) : $expires, $path, $domain, $secure, $httponly); }
  }

  class Route {
    public $url, $callback, $middleware = array(), $mwarecount = 0, $match = false, $ran = false, $params = array();
    private $conditions, $uri;

    function __construct ($uri, $url, $method, $middleware = null, $callback = null, $cond = null) {
      if (empty($uri) || empty($url) || empty($method) || empty($callback)) { echo 'empty parameter'; return; }
      if (is_callable($callback)) $this->callback = $callback;
      if (!empty($middleware)) if (is_string($middleware) && is_callable($middleware)) $this->middleware[] = $middleware;
      else if (is_array($middleware)) foreach ($middleware as $key => $value) if (is_callable($value)) $this->middleware[] = $value;
      $this->method = is_array($method) ? $method : array( $method );
      $this->conditions = is_array($cond) ? $cond : array(); $this->url = $url; $this->uri = $uri;
      $this->compile();
    }

    function compile () {
      $names = $values = array(); 
      preg_match_all('@:([\w]+)@', $this->url, $names, PREG_PATTERN_ORDER); $names = $names[0];
      $regex = (preg_replace_callback('@:[\w]+@', array($this, 'regex'), $this->url)) . '/?';
      
      if (preg_match('@^' . $regex . '$@', $this->uri, $values) && in_array(\fluf::$method, $this->method) && array_shift($values)) {
        foreach ($names as $i => $v) $this->params[substr($v,1)] = urldecode($values[$i]);
        $this->match = true;
      }
    }

    function regex ($matches) {
      $key = str_replace(':', '', $matches[0]);
      return array_key_exists($key, $this->conditions) ?  '('.$this->conditions[$key].')' : '([a-zA-Z0-9_\+\-%]+)';
    }
  }

  function map ($rule, $middleware = null, $callback = null, $cond = null) {
    return new \fluf\Map($rule, $middleware, $callback, is_array($cond) ? $cond : array()); 
  }
  
  foreach (array('get','post','put','delete','patch','head','options') as $method) 
    eval('namespace fluf; function '.$method.' ($rule, $middleware = null, $callback = null, $cond = null) { \fluf::add($rule, "'.strtoupper($method).'", $middleware, $callback, $cond); }');

  class Map {
    function __construct ($rule, $middleware, $callback, $cond = array()) { $this->r = $rule; $this->middleware = $middleware; $this->cb = $callback; $this->cd = $cond; return $this; }
    public function via () { if (func_num_args() !== 0) foreach (func_get_args() as $a) \fluf::add($this->r, strtoupper($a), $this->middleware, $this->cb, $this->cd); }
  }
}